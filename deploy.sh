#!/bin/bash
PROJECT_NAME="contacolegio"

# ============================================
# Script de Deploy Automático para Laravel
# ============================================

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
PROJECT_DIR="$SCRIPT_DIR"
SRC_DIR="$PROJECT_DIR/src"

echo -e "${BLUE}=========================================="
echo "  🚀 Deploy Laravel: $PROJECT_NAME"
echo "==========================================${NC}"
echo ""

# ── PRE-CHECK: Verificar que el .env existe ──
if [ ! -f "$SRC_DIR/.env" ]; then
    echo -e "${RED}✗ ERROR: No se encontró el archivo .env en $SRC_DIR${NC}"
    echo -e "${RED}  Crea el .env antes de hacer deploy.${NC}"
    exit 1
fi

# ── PRE-CHECK: Verificar que los contenedores están corriendo ──
if ! docker ps --format '{{.Names}}' | grep -q "${PROJECT_NAME}_php"; then
    echo -e "${RED}✗ ERROR: El contenedor ${PROJECT_NAME}_php no está corriendo.${NC}"
    echo -e "${RED}  Ejecuta 'docker compose up -d' primero.${NC}"
    exit 1
fi

if ! docker ps --format '{{.Names}}' | grep -q "${PROJECT_NAME}_db\|${PROJECT_NAME}_mysql\|${PROJECT_NAME}_mariadb"; then
    echo -e "${YELLOW}⚠️  AVISO: No se detectó contenedor de BD corriendo. Verifica que la BD esté activa.${NC}"
fi

# ── PRE-CHECK: Verificar que la BD es accesible ──
echo -e "${YELLOW}[0/8] 🔍 Verificando conexión a BD...${NC}"
if docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan db:monitor 2>/dev/null; then
    echo -e "${GREEN}✓ Base de datos accesible${NC}"
else
    # Fallback: intentar una query simple
    if docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan tinker --execute="DB::select('SELECT 1')" 2>/dev/null; then
        echo -e "${GREEN}✓ Base de datos accesible${NC}"
    else
        echo -e "${RED}✗ ERROR: No se pudo conectar a la base de datos.${NC}"
        echo -e "${RED}  Verifica las credenciales en .env y que el contenedor de BD esté corriendo.${NC}"
        exit 1
    fi
fi

cd "$SRC_DIR"

# ── PASO 1: Descargar cambios desde Git ──
echo ""
echo -e "${YELLOW}[1/8] ⬇️  Descargando cambios desde Git...${NC}"

# Guardar cambios locales si los hay
STASH_RESULT=$(git stash --quiet 2>&1)
STASHED=$?

BRANCH=$(git rev-parse --abbrev-ref HEAD)
if git pull origin "$BRANCH" 2>&1; then
    echo -e "${GREEN}✓ Cambios descargados desde rama '$BRANCH'${NC}"
else
    echo -e "${RED}✗ Error al descargar cambios${NC}"
    # Restaurar stash si hubo error
    if [ $STASHED -eq 0 ]; then
        git stash pop --quiet 2>/dev/null || true
    fi
    exit 1
fi

# Restaurar cambios locales (si había stash)
if [ $STASHED -eq 0 ] && git stash list 2>/dev/null | grep -q "stash@{0}"; then
    git stash pop --quiet 2>/dev/null || {
        echo -e "${YELLOW}⚠️  Conflicto al restaurar cambios locales. Revisa: git stash show${NC}"
    }
fi

LAST_COMMIT=$(git log -1 --pretty=format:'%h - %s (%ar) por %an')
echo -e "${BLUE}    📝 Último commit: $LAST_COMMIT${NC}"

# ── PASO 2: Backup de BD antes de migrar ──
echo ""
echo -e "${YELLOW}[2/8] 💾 Creando backup de base de datos...${NC}"
BACKUP_DIR="$PROJECT_DIR/backups"
mkdir -p "$BACKUP_DIR"
BACKUP_FILE="$BACKUP_DIR/backup_$(date +%Y%m%d_%H%M%S).sql"

# Intentar backup usando el contenedor de BD
DB_CONTAINER=$(docker ps --format '{{.Names}}' | grep -E "${PROJECT_NAME}_(db|mysql|mariadb)" | head -1)
if [ -n "$DB_CONTAINER" ]; then
    # Leer credenciales del .env
    DB_DATABASE=$(grep -E "^DB_DATABASE=" "$SRC_DIR/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")
    DB_USERNAME=$(grep -E "^DB_USERNAME=" "$SRC_DIR/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")
    DB_PASSWORD=$(grep -E "^DB_PASSWORD=" "$SRC_DIR/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")

    if docker exec "$DB_CONTAINER" mysqldump -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null; then
        BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
        echo -e "${GREEN}✓ Backup creado: $BACKUP_FILE ($BACKUP_SIZE)${NC}"

        # Mantener solo los últimos 10 backups
        ls -t "$BACKUP_DIR"/backup_*.sql 2>/dev/null | tail -n +11 | xargs -r rm --
    else
        echo -e "${YELLOW}⚠️  No se pudo crear backup automático. Continuando...${NC}"
        rm -f "$BACKUP_FILE" 2>/dev/null
    fi
else
    echo -e "${YELLOW}⚠️  No se detectó contenedor de BD para backup. Continuando...${NC}"
fi

# ── PASO 3: Composer install ──
echo ""
echo -e "${YELLOW}[3/8] 📦 Composer install...${NC}"
docker exec -w /var/www/html ${PROJECT_NAME}_php composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -5
echo -e "${GREEN}✓ Dependencias actualizadas${NC}"

# ── PASO 4: Migraciones ──
echo ""
echo -e "${YELLOW}[4/8] 🗄️  Migraciones...${NC}"
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan migrate --force 2>&1
echo -e "${GREEN}✓ Migraciones ejecutadas${NC}"

# ── PASO 4.5: Seeders de datos base ──
echo ""
echo -e "${YELLOW}[4.5/8] 🌱 Seeders de datos base...${NC}"
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan db:seed --class=DeploySeeder --force 2>&1
echo -e "${GREEN}✓ Datos base sincronizados${NC}"

# ── PASO 5: Compilar assets ──
echo ""
echo -e "${YELLOW}[5/8] 📦 Compilando assets...${NC}"
if [ -f "$SRC_DIR/package.json" ]; then
    docker exec -w /var/www/html ${PROJECT_NAME}_php npm install 2>&1 | tail -3
    docker exec -w /var/www/html ${PROJECT_NAME}_php npm run build 2>&1 | tail -5
    echo -e "${GREEN}✓ Assets compilados${NC}"
else
    echo -e "${BLUE}⏭️  Sin package.json, saltando${NC}"
fi

# ── PASO 6: Permisos ──
echo ""
echo -e "${YELLOW}[6/8] 🔐 Ajustando permisos...${NC}"
docker exec ${PROJECT_NAME}_php chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker exec ${PROJECT_NAME}_php chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
echo -e "${GREEN}✓ Permisos ajustados${NC}"

# ── PASO 6.5: Publicar assets de Livewire ──
echo ""
echo -e "${YELLOW}[6.5/8] 📦 Publicando assets (Livewire, etc.)...${NC}"
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan vendor:publish --force --tag=livewire:assets 2>/dev/null || true
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan livewire:publish --assets 2>/dev/null || true
echo -e "${GREEN}✓ Assets publicados${NC}"

# ── PASO 7: Cache (IMPORTANTE: limpiar ANTES de recachear) ──
echo ""
echo -e "${YELLOW}[7/8] ⚡ Limpiando y recacheando...${NC}"
# Limpiar TODA la cache primero para evitar datos obsoletos
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan cache:clear
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan config:clear
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan route:clear
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan view:clear
# Ahora recachear con datos frescos
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan config:cache
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan route:cache
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan view:cache
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan event:cache 2>/dev/null || true
echo -e "${GREEN}✓ Cache reconstruida${NC}"

# ── PASO 8: Reload en lugar de restart (preserva sesiones) ──
echo ""
echo -e "${YELLOW}[8/8] 🔄 Recargando servicios (sin perder sesiones)...${NC}"
# Usar reload en vez de restart para PHP-FPM (no destruye sesiones)
docker exec ${PROJECT_NAME}_php kill -USR2 1 2>/dev/null || {
    # Si PHP-FPM no responde a USR2, intentar reload directo
    docker exec ${PROJECT_NAME}_php php-fpm -t 2>/dev/null && \
    docker exec ${PROJECT_NAME}_php kill -USR2 $(docker exec ${PROJECT_NAME}_php cat /var/run/php-fpm.pid 2>/dev/null || echo 1) 2>/dev/null || {
        # Último recurso: restart (pero perderá sesiones)
        echo -e "${YELLOW}⚠️  No se pudo hacer reload, haciendo restart...${NC}"
        cd "$PROJECT_DIR"
        docker compose restart php
    }
}
# Nginx reload (no restart)
docker exec ${PROJECT_NAME}_nginx nginx -s reload 2>/dev/null || {
    cd "$PROJECT_DIR"
    docker compose restart nginx
}
sleep 2
echo -e "${GREEN}✓ Servicios recargados${NC}"

# ── POST-CHECK: Verificar que la app responde ──
echo ""
echo -e "${YELLOW}🔍 Verificación post-deploy...${NC}"
if docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan about 2>/dev/null | head -5; then
    echo -e "${GREEN}✓ Aplicación funcionando correctamente${NC}"
else
    echo -e "${YELLOW}⚠️  No se pudo verificar el estado de la app. Revisa manualmente.${NC}"
fi

echo ""
echo -e "${GREEN}=========================================="
echo "  ✅ Deploy completado exitosamente"
echo "==========================================${NC}"
echo ""
echo -e "${BLUE}📅 Fecha: $(date '+%Y-%m-%d %H:%M:%S')${NC}"
echo -e "${BLUE}🔀 Rama: $BRANCH${NC}"
echo -e "${BLUE}📝 Commit: $LAST_COMMIT${NC}"
echo ""
