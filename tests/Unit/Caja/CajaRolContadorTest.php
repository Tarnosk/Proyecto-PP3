<?php

namespace Tests\Unit\Caja;

use App\Modules\Caja\Models\Caja;
use App\Modules\Shared\Http\Middleware\G2Access;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Rol contador: solo lectura y exportación a PDF de cierres.
 * No abre ni cierra cajas, no carga movimientos ni conteos.
 */
class CajaRolContadorTest extends TestCase
{
    public function test_contador_no_maneja_caja(): void
    {
        $this->assertFalse(Caja::rolManejaCaja(Caja::ROL_CONTADOR));
        $this->assertTrue(Caja::rolManejaCaja(Caja::ROL_ADMIN));
        $this->assertTrue(Caja::rolManejaCaja(Caja::ROL_REPARTIDOR));
    }

    public function test_admin_y_contador_ven_todas_las_cajas_pero_el_repartidor_no(): void
    {
        $this->assertTrue(Caja::rolVeTodasLasCajas(Caja::ROL_ADMIN));
        $this->assertTrue(Caja::rolVeTodasLasCajas(Caja::ROL_CONTADOR));
        $this->assertFalse(Caja::rolVeTodasLasCajas(Caja::ROL_REPARTIDOR));
    }

    public function test_solo_admin_y_contador_exportan_pdf(): void
    {
        $this->assertTrue(Caja::rolExportaPdf(Caja::ROL_ADMIN));
        $this->assertTrue(Caja::rolExportaPdf(Caja::ROL_CONTADOR));
        $this->assertFalse(Caja::rolExportaPdf(Caja::ROL_REPARTIDOR));
    }

    /** @return list<string> */
    private function middlewareDe(string $nombreRuta): array
    {
        $ruta = Route::getRoutes()->getByName($nombreRuta);
        $this->assertNotNull($ruta, "No existe la ruta {$nombreRuta}");

        return $ruta->gatherMiddleware();
    }

    public function test_las_rutas_de_lectura_admiten_contador(): void
    {
        foreach (['caja.cierres.index', 'caja.cierres.show'] as $nombre) {
            $this->assertContains(
                G2Access::class . ':admin,repartidor,contador',
                $this->middlewareDe($nombre),
                $nombre
            );
        }
    }

    public function test_las_rutas_de_pdf_son_solo_para_admin_y_contador(): void
    {
        foreach (['caja.cierres.pdf', 'caja.cierres.pdf.detalle'] as $nombre) {
            $middleware = $this->middlewareDe($nombre);

            $this->assertContains(G2Access::class . ':admin,contador', $middleware, $nombre);
            $this->assertNotContains(G2Access::class . ':admin,repartidor,contador', $middleware, $nombre);
        }
    }

    public function test_las_rutas_de_operacion_no_admiten_contador(): void
    {
        $rutas = ['caja.dia', 'caja.abrir', 'caja.movimiento', 'caja.cerrar', 'caja.conteo', 'caja.pendiente'];

        foreach ($rutas as $nombre) {
            $unido = implode('|', $this->middlewareDe($nombre));

            $this->assertStringNotContainsString('contador', $unido, "{$nombre} no debe admitir al contador");
        }
    }

    public function test_la_api_de_cierres_admite_contador_y_el_resto_no(): void
    {
        $permitidas = [];
        $prohibidas = [];

        foreach (Route::getRoutes() as $ruta) {
            if (!str_starts_with($ruta->uri(), 'g2/api/caja')) {
                continue;
            }

            $conContador = str_contains(implode('|', $ruta->gatherMiddleware()), 'contador');
            $esLecturaDeCierres = in_array('GET', $ruta->methods(), true)
                && str_starts_with($ruta->uri(), 'g2/api/caja/cierres');

            if ($conContador) {
                $permitidas[] = $ruta->uri();
            } else {
                $prohibidas[] = $ruta->uri();
            }

            if ($esLecturaDeCierres) {
                $this->assertTrue($conContador, $ruta->uri() . ' debería admitir al contador');
            } else {
                $this->assertFalse($conContador, $ruta->uri() . ' no debería admitir al contador');
            }
        }

        $this->assertNotEmpty($permitidas, 'Debe haber rutas de lectura de cierres para el contador');
        $this->assertNotEmpty($prohibidas, 'Debe haber rutas de operación sin contador');
    }
}
