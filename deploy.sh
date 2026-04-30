#!/bin/bash
PROJECT_NAME="demo-colegio"

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

cd "$SRC_DIR"

echo -e "${YELLOW}[1/8] ⬇️  Descargando cambios desde Git...${NC}"
git stash --quiet 2>/dev/null || true

BRANCH=$(git rev-parse --abbrev-ref HEAD)
if git pull origin "$BRANCH" 2>&1; then
    echo -e "${GREEN}✓ Cambios descargados desde rama '$BRANCH'${NC}"
else
    echo -e "${RED}✗ Error al descargar cambios${NC}"
    exit 1
fi

LAST_COMMIT=$(git log -1 --pretty=format:'%h - %s (%ar) por %an')
echo -e "${BLUE}    📝 Último commit: $LAST_COMMIT${NC}"

echo ""
echo -e "${YELLOW}[2/8] 📦 Composer install...${NC}"
docker exec -w /var/www/html ${PROJECT_NAME}_php composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -5
echo -e "${GREEN}✓ Dependencias actualizadas${NC}"

echo ""
echo -e "${YELLOW}[3/8] 🗄️  Migraciones...${NC}"
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan migrate --force 2>&1
echo -e "${GREEN}✓ Migraciones ejecutadas${NC}"

echo ""
echo -e "${YELLOW}[4/8] 🌱 Seeders de producción...${NC}"
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan db:seed-once --force 2>&1
echo -e "${GREEN}✓ Datos base sincronizados${NC}"

echo ""
echo -e "${YELLOW}[4.5/8] 🔐 Sincronizando permisos...${NC}"
# Resetear todos los seeders de permisos para que se re-ejecuten (son idempotentes con updateOrCreate)
PERMISSION_SEEDERS=(
    "ModulePermissionSeeder"
    "BudgetPermissionSeeder"
    "BudgetItemPermissionSeeder"
    "BudgetTransferPermissionSeeder"
    "BudgetModificationPermissionSeeder"
    "FundingSourcePermissionSeeder"
    "IncomePermissionSeeder"
    "ExpensePermissionSeeder"
    "ExpenseCodePermissionSeeder"
    "PrecontractualPermissionSeeder"
    "ContractualPermissionSeeder"
    "PostcontractualPermissionSeeder"
    "BankPermissionSeeder"
    "ReportPermissionSeeder"
    "NewsPermissionSeeder"
    "InventoryAccountingAccountPermissionSeeder"
    "InventoryItemPermissionSeeder"
    "InventoryEntryPermissionSeeder"
    "InventoryDischargePermissionSeeder"
    "InventoryAccountingAccountSeeder"
)
for SEEDER in "${PERMISSION_SEEDERS[@]}"; do
    docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan db:seed-once --reset="$SEEDER" 2>&1 | grep -v "^$"
done
echo -e "${BLUE}    Ejecutando seeders de permisos...${NC}"
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan db:seed-once --force 2>&1
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan permission:cache-reset 2>&1
echo -e "${GREEN}✓ Permisos sincronizados${NC}"

echo ""
echo -e "${YELLOW}[5/8] 📦 Verificando assets...${NC}"
if docker exec -w /var/www/html ${PROJECT_NAME}_php test -f public/build/manifest.json; then
    echo -e "${GREEN}✓ Assets encontrados en el repositorio${NC}"
else
    echo -e "${RED}✗ ADVERTENCIA: No se encontró public/build/manifest.json${NC}"
    echo -e "${RED}  Ejecuta 'npm run build' localmente y haz push antes de deploy${NC}"
fi

echo ""
echo -e "${YELLOW}[6/8] 🔐 Ajustando permisos...${NC}"
docker exec ${PROJECT_NAME}_php mkdir -p /var/www/html/storage/app/livewire-tmp
docker exec ${PROJECT_NAME}_php chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker exec ${PROJECT_NAME}_php chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
docker exec ${PROJECT_NAME}_php chmod 666 /var/www/html/.env 2>/dev/null || true
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan storage:link --force 2>/dev/null || true
echo -e "${GREEN}✓ Permisos ajustados${NC}"

echo ""
echo -e "${YELLOW}[6.5/8] 📦 Publicando assets (Livewire, etc.)...${NC}"
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan vendor:publish --force --tag=livewire:assets 2>/dev/null || true
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan livewire:publish --assets 2>/dev/null || true
echo -e "${GREEN}✓ Assets publicados${NC}"

echo ""
echo -e "${YELLOW}[7/8] ⚡ Limpiando y recacheando...${NC}"
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan cache:clear 2>/dev/null || true
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan view:clear 2>/dev/null || true
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan route:clear 2>/dev/null || true
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan config:clear 2>/dev/null || true
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan event:clear 2>/dev/null || true
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan config:cache
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan route:cache
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan view:cache
docker exec -w /var/www/html ${PROJECT_NAME}_php php artisan event:cache 2>/dev/null || true
echo -e "${GREEN}✓ Cache reconstruida${NC}"

echo ""
echo -e "${YELLOW}[8/8] 🔄 Reiniciando servicios...${NC}"
cd "$PROJECT_DIR"
docker compose restart php nginx
sleep 3
echo -e "${GREEN}✓ Servicios reiniciados${NC}"

echo ""
echo -e "${GREEN}=========================================="
echo "  ✅ Deploy completado exitosamente"
echo "==========================================${NC}"
echo ""
echo -e "${BLUE}📅 Fecha: $(date '+%Y-%m-%d %H:%M:%S')${NC}"
echo -e "${BLUE}🔀 Rama: $BRANCH${NC}"
echo -e "${BLUE}📝 Commit: $LAST_COMMIT${NC}"
echo ""
