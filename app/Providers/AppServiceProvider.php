<?php

namespace App\Providers;

use App\Contracts\SalesGateway;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Clientes\Policies\ClientePolicy;
use App\Modules\Reclamos\Models\Reclamo;
use App\Modules\Reclamos\Policies\ReclamoPolicy;
use App\Services\Gateways\HttpSalesGateway;
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
