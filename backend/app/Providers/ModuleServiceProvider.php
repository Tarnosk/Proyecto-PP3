<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     * Carga automáticamente rutas, migraciones y vistas de cada módulo en app/Modules/
     */
    public function boot(): void
    {
        $modulesPath = app_path('Modules');

        if (! is_dir($modulesPath)) {
            return;
        }

        $modules = array_filter(scandir($modulesPath) ?: [], function ($item) use ($modulesPath) {
            return ! in_array($item, ['.', '..'], true) && is_dir($modulesPath . DIRECTORY_SEPARATOR . $item);
        });

        foreach ($modules as $module) {
            $moduleDir = $modulesPath . DIRECTORY_SEPARATOR . $module;

            // 1. Cargar rutas web del módulo si existen (app/Modules/{Modulo}/routes/web.php)
            $webRoutes = $moduleDir . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'web.php';
            if (file_exists($webRoutes)) {
                Route::middleware('web')->group($webRoutes);
            }

            // 2. Cargar rutas API del módulo si existen (app/Modules/{Modulo}/routes/api.php)
            $apiRoutes = $moduleDir . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'api.php';
            if (file_exists($apiRoutes)) {
                Route::prefix('api')->middleware('api')->group($apiRoutes);
            }

            // 3. Cargar migraciones del módulo si existen (app/Modules/{Modulo}/database/migrations/)
            $migrationsDir = $moduleDir . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
            if (is_dir($migrationsDir)) {
                $this->loadMigrationsFrom($migrationsDir);
            }

            // 4. Cargar vistas del módulo si existen (app/Modules/{Modulo}/resources/views/)
            $viewsDir = $moduleDir . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views';
            if (is_dir($viewsDir)) {
                $this->loadViewsFrom($viewsDir, strtolower($module));
            }
        }
    }
}
