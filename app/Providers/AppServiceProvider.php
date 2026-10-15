<?php

namespace App\Providers;

use App\Contracts\SalesGateway;
use App\Models\Cliente;
use App\Models\Reclamo;
use App\Policies\ClientePolicy;
use App\Policies\ReclamoPolicy;
use App\Services\Gateways\HttpSalesGateway;
use App\Services\Gateways\LocalSalesGateway;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SalesGateway::class, fn ($app) => $app->make(match (config('services.sales.driver')) {
            'local' => LocalSalesGateway::class,
            'http' => HttpSalesGateway::class,
            default => throw new \InvalidArgumentException('SALES_SERVICE_DRIVER debe ser local o http.'),
        }));

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
