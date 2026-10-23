<?php

namespace App\Providers;

use App\Contracts\SalesGateway;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Clientes\Policies\ClientePolicy;
use App\Modules\Reclamos\Models\Reclamo;
use App\Modules\Reclamos\Policies\ReclamoPolicy;
use App\Modules\Ventas\Contracts\ClienteGateway;
use App\Modules\Ventas\Contracts\ProductoGateway;
use App\Modules\Ventas\Contracts\StockGateway;
use App\Modules\Ventas\Integraciones\HttpClienteApi;
use App\Modules\Ventas\Integraciones\HttpProductoApi;
use App\Modules\Ventas\Integraciones\HttpStockApi;
use App\Modules\Ventas\Integraciones\MockStockApi;
use App\Services\Gateways\HttpSalesGateway;
use App\Services\MockClienteApi;
use App\Services\MockProductoApi;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SalesGateway::class, HttpSalesGateway::class);

        // Registro original de G4: una instancia por petición, según config/ventas.php.
        $usarApisReales = fn (): bool => config('ventas.integracion') === 'http';

        $this->app->scoped(ClienteGateway::class, fn ($app) => $usarApisReales()
            ? $app->make(HttpClienteApi::class)
            : $app->make(MockClienteApi::class));

        $this->app->scoped(ProductoGateway::class, fn ($app) => $usarApisReales()
            ? $app->make(HttpProductoApi::class)
            : $app->make(MockProductoApi::class));

        $this->app->scoped(StockGateway::class, fn ($app) => $usarApisReales()
            ? $app->make(HttpStockApi::class)
            : $app->make(MockStockApi::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Cliente::class, ClientePolicy::class);
        Gate::policy(Reclamo::class, ReclamoPolicy::class);
    }
}
