<?php

use App\Modules\Caja\Services\CajaService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('caja:cerrar-vencidas', function () {
    $cantidad = app(CajaService::class)->cerrarCajasVencidas();
    $this->info("Cajas cerradas automaticamente: {$cantidad}");
})->purpose('Cierra las cajas de repartidor vencidas (72 h), sin contar');

Schedule::command('caja:cerrar-vencidas')->everyFifteenMinutes()->withoutOverlapping();
