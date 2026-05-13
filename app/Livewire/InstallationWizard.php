<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('layouts.installation')]
class InstallationWizard extends Component
{
    // Steps: 1=DB config, 2=Migrate, 3=Seeders, 4=Admin user, 5=Done
    public int $currentStep = 1;

    // Step 1 – Database
    public string $db_host = '127.0.0.1';
    public string $db_port = '3306';
    public string $db_database = '';
    public string $db_username = '';
    public string $db_password = '';
    public ?bool $connectionTested = null;
    public string $connectionMessage = '';

    // Step 2 – Migrations
    public bool $migrationsRun = false;
    public string $migrationsOutput = '';

    // Step 3 – Seeders
    public bool $seedersRun = false;
    public array $seederResults = [];

    // Step 4 – Admin user
    public string $admin_name = '';
    public string $admin_surname = '';
    public string $admin_email = '';
    public string $admin_password = '';
    public string $admin_password_confirmation = '';
    public bool $adminCreated = false;

    public array $steps = [
        1 => 'Base de Datos',
        2 => 'Migraciones',
        3 => 'Datos Iniciales',
        4 => 'Usuario Admin',
        5 => 'Completado',
    ];

    public function mount(): void
    {
        // Pre-fill from current .env
        $this->db_host     = env('DB_HOST', '127.0.0.1');
        $this->db_port     = env('DB_PORT', '3306');
        $this->db_database = env('DB_DATABASE', '');
        $this->db_username = env('DB_USERNAME', '');
    }

    // ─── Step 1 ────────────────────────────────────────────────────────────────

    public function testConnection(): void
    {
        $this->connectionTested = null;
        $this->connectionMessage = '';

        try {
            Config::set('database.connections.install_test', [
                'driver'    => 'mysql',
                'host'      => $this->db_host,
                'port'      => $this->db_port,
                'database'  => $this->db_database,
                'username'  => $this->db_username,
                'password'  => $this->db_password,
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'strict'    => true,
                'engine'    => null,
            ]);

            DB::connection('install_test')->getPdo();

            $this->connectionTested = true;
            $this->connectionMessage = 'Conexión exitosa a la base de datos.';
        } catch (\Exception $e) {
            $this->connectionTested = false;
            $this->connectionMessage = 'Error: ' . $e->getMessage();
        } finally {
            // Purge the test connection to avoid memory leaks
            DB::purge('install_test');
        }
    }

    public function saveDbConfig(): void
    {
        $this->validate([
            'db_host'     => 'required|string',
            'db_port'     => 'required|numeric|min:1|max:65535',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
        ], [
            'db_host.required'     => 'El host es requerido.',
            'db_port.required'     => 'El puerto es requerido.',
            'db_database.required' => 'El nombre de la base de datos es requerido.',
            'db_username.required' => 'El usuario es requerido.',
        ]);

        if (!$this->connectionTested) {
            $this->addError('db_connection', 'Debes probar y confirmar la conexión antes de continuar.');
            return;
        }

        $this->writeEnvValues([
            'DB_HOST'     => $this->db_host,
            'DB_PORT'     => $this->db_port,
            'DB_DATABASE' => $this->db_database,
            'DB_USERNAME' => $this->db_username,
            'DB_PASSWORD' => $this->db_password,
        ]);

        $this->currentStep = 2;
    }

    // ─── Step 2 ────────────────────────────────────────────────────────────────

    public function runMigrations(): void
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->migrationsOutput = Artisan::output();
            $this->migrationsRun = true;
            $this->dispatch('notify', message: 'Migraciones ejecutadas correctamente.', type: 'success');
        } catch (\Exception $e) {
            $this->migrationsOutput = 'Error: ' . $e->getMessage();
            $this->dispatch('notify', message: 'Error al ejecutar migraciones.', type: 'error');
        }
    }

    public function goToSeeders(): void
    {
        if (!$this->migrationsRun) {
            $this->dispatch('notify', message: 'Debes ejecutar las migraciones primero.', type: 'error');
            return;
        }
        $this->currentStep = 3;
    }

    // ─── Step 3 ────────────────────────────────────────────────────────────────

    protected array $globalSeeders = [
        \Database\Seeders\DepartmentSeeder::class              => 'Departamentos',
        \Database\Seeders\MunicipalitySeeder::class            => 'Municipios',
        \Database\Seeders\ModulePermissionSeeder::class        => 'Módulos y permisos base',
        \Database\Seeders\BudgetPermissionSeeder::class        => 'Permisos de presupuesto',
        \Database\Seeders\BudgetItemPermissionSeeder::class    => 'Permisos de rubros',
        \Database\Seeders\BudgetTransferPermissionSeeder::class => 'Permisos de traslados',
        \Database\Seeders\BudgetModificationPermissionSeeder::class => 'Permisos de modificaciones',
        \Database\Seeders\FundingSourcePermissionSeeder::class  => 'Permisos de fuentes de financiación',
        \Database\Seeders\IncomePermissionSeeder::class         => 'Permisos de ingresos',
        \Database\Seeders\ExpensePermissionSeeder::class        => 'Permisos de gastos',
        \Database\Seeders\ExpenseCodePermissionSeeder::class    => 'Permisos de códigos de gasto',
        \Database\Seeders\PrecontractualPermissionSeeder::class => 'Permisos precontractuales',
        \Database\Seeders\ContractualPermissionSeeder::class    => 'Permisos contractuales',
        \Database\Seeders\PostcontractualPermissionSeeder::class => 'Permisos postcontractuales',
        \Database\Seeders\BankPermissionSeeder::class           => 'Permisos de bancos',
        \Database\Seeders\ReportPermissionSeeder::class         => 'Permisos de reportes',
        \Database\Seeders\NewsPermissionSeeder::class           => 'Permisos de noticias',
        \Database\Seeders\RetentionConfigPermissionSeeder::class => 'Permisos de retenciones',
        \Database\Seeders\InventoryAccountingAccountPermissionSeeder::class => 'Permisos cuentas inventario',
        \Database\Seeders\InventoryItemPermissionSeeder::class   => 'Permisos ítems inventario',
        \Database\Seeders\InventoryEntryPermissionSeeder::class  => 'Permisos entradas inventario',
        \Database\Seeders\InventoryDischargePermissionSeeder::class => 'Permisos bajas inventario',
        \Database\Seeders\AccountingAccountSeeder::class         => 'Cuentas contables',
        \Database\Seeders\ExpenseAccountingAccountSeeder::class  => 'Cuentas contables de gastos',
        \Database\Seeders\RubrosFuentesSeeder::class             => 'Rubros y fuentes de financiación',
        \Database\Seeders\RefreshExpenseCodesSeeder::class       => 'Códigos de gasto',
        \Database\Seeders\InventoryAccountingAccountSeeder::class => 'Cuentas contables de inventario',
        \Database\Seeders\RetentionConfigSeeder::class           => 'Configuración de retenciones',
    ];

    public function runSeeders(): void
    {
        $this->seederResults = [];

        foreach ($this->globalSeeders as $class => $label) {
            try {
                // Use firstOrCreate-safe approach: just call seeder directly
                app($class)->run();
                $this->seederResults[] = ['label' => $label, 'status' => 'ok', 'error' => null];
            } catch (\Exception $e) {
                $this->seederResults[] = ['label' => $label, 'status' => 'error', 'error' => $e->getMessage()];
            }
        }

        // Check if any failed
        $failed = array_filter($this->seederResults, fn($r) => $r['status'] === 'error');

        if (empty($failed)) {
            $this->seedersRun = true;
            $this->dispatch('notify', message: 'Datos iniciales cargados correctamente.', type: 'success');
        } else {
            $this->dispatch('notify', message: count($failed) . ' seeder(s) fallaron. Revisa los detalles.', type: 'error');
        }
    }

    public function goToAdmin(): void
    {
        if (!$this->seedersRun) {
            $this->dispatch('notify', message: 'Debes cargar los datos iniciales primero.', type: 'error');
            return;
        }
        $this->currentStep = 4;
    }

    // ─── Step 4 ────────────────────────────────────────────────────────────────

    public function createAdminUser(): void
    {
        $this->validate([
            'admin_name'                  => 'required|string|max:100',
            'admin_surname'               => 'required|string|max:100',
            'admin_email'                 => 'required|email|unique:users,email',
            'admin_password'              => 'required|string|min:8|confirmed',
            'admin_password_confirmation' => 'required|string',
        ], [
            'admin_name.required'     => 'El nombre es requerido.',
            'admin_surname.required'  => 'El apellido es requerido.',
            'admin_email.required'    => 'El correo es requerido.',
            'admin_email.email'       => 'Ingresa un correo válido.',
            'admin_email.unique'      => 'Este correo ya está registrado.',
            'admin_password.required' => 'La contraseña es requerida.',
            'admin_password.min'      => 'La contraseña debe tener al menos 8 caracteres.',
            'admin_password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        $admin = User::create([
            'name'            => $this->admin_name,
            'surname'         => $this->admin_surname,
            'email'           => $this->admin_email,
            'password'        => Hash::make($this->admin_password),
            'is_system_admin' => true,
        ]);

        $admin->assignRole($adminRole);

        // Mark as installed
        $this->markAsInstalled();

        $this->adminCreated = true;
        $this->currentStep = 5;
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    protected function writeEnvValues(array $values): void
    {
        $envPath = base_path('.env');
        $content = File::get($envPath);

        foreach ($values as $key => $value) {
            // If value contains spaces or special chars, wrap in quotes
            $escapedValue = preg_match('/\s/', $value) ? '"' . $value . '"' : $value;

            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$escapedValue}", $content);
            } else {
                $content .= "\n{$key}={$escapedValue}";
            }
        }

        File::put($envPath, $content);

        // Clear config cache so new values take effect immediately
        Artisan::call('config:clear');
    }

    protected function markAsInstalled(): void
    {
        $lockFile = storage_path('app/installed.lock');
        File::put($lockFile, now()->toIso8601String());

        $this->writeEnvValues(['APP_INSTALLED' => 'true']);
    }

    public function render()
    {
        return view('livewire.installation.wizard');
    }
}
