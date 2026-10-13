<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Usuario;
use App\Modules\Productos\Models\Categoria;
use App\Modules\Productos\Models\Marca;
use App\Modules\Productos\Models\Producto;
use App\Modules\Stock\Models\MovimientoStock;
use App\Modules\Stock\Models\SesionConteo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ConteoFisicoTest extends TestCase
{
    use DatabaseTransactions;

    private function crearUsuarioTest(string $nombre = 'usuario-conteo'): Usuario
    {
        return Usuario::create([
            'user' => $nombre . '-' . uniqid(),
            'password' => password_hash('secret', PASSWORD_DEFAULT),
            'nombre' => 'Usuario Conteo',
            'rol' => 'deposito',
            'estado' => 'activo',
        ]);
    }

    private function crearProductoTest(string $codigo = 'PROD-CONT'): Producto
    {
        $categoria = Categoria::first() ?? Categoria::create([
            'nombre' => 'Cat Conteo ' . uniqid(),
            'activo' => true,
        ]);

        $marca = Marca::first() ?? Marca::create([
            'nombre' => 'Marca Conteo ' . uniqid(),
            'activo' => true,
        ]);

        return Producto::create([
            'codigo' => $codigo . '-' . uniqid(),
            'nombre' => 'Producto Conteo ' . uniqid(),
            'descripcion' => 'Producto para prueba de conteo físico',
            'id_categoria' => $categoria->id_categoria,
            'id_marca' => $marca->id_marca,
            'precio_unitario' => 100.00,
            'stock' => 50,
            'stock_minimo' => 10,
            'estado' => 'activo',
            'fecha_alta' => Carbon::now(),
            'id_usuario_carga' => 1,
        ]);
    }

    public function test_iniciar_sesion_conteo(): void
    {
        $usuario = $this->crearUsuarioTest('inicio-conteo');

        $response = $this->postJson('/api/stock/conteos/sesiones', [
            'fecha_conteo' => '2026-10-11',
            'observaciones' => 'Auditoría mensual pasillo A',
            'id_usuario' => $usuario->id_usuario,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.estado', 'en_proceso')
            ->assertJsonPath('data.total_productos', 0)
            ->assertJsonPath('data.total_diferencias', 0);
    }

    public function test_registrar_item_conteo_coincidente(): void
    {
        $usuario = $this->crearUsuarioTest('item-coincidente');
        $producto = $this->crearProductoTest('PROD-COIN');

        $sesion = SesionConteo::create([
            'estado' => 'en_proceso',
            'fecha_conteo' => Carbon::today(),
            'observaciones' => 'Conteo de coincidencia',
            'total_productos' => 0,
            'total_diferencias' => 0,
            'id_usuario' => $usuario->id_usuario,
        ]);

        $response = $this->postJson("/api/stock/conteos/sesiones/{$sesion->id_sesion}/items", [
            'id_producto' => $producto->id_producto,
            'cantidad_fisica' => (int) $producto->stock_disponible,
            'observaciones' => 'Coincide con stock del sistema',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.diferencia', 0)
            ->assertJsonPath('data.diagnostico', 'coincidente');

        $sesion->refresh();
        $this->assertSame(1, $sesion->total_productos);
        $this->assertSame(0, $sesion->total_diferencias);
    }

    public function test_registrar_item_conteo_con_discrepancia(): void
    {
        $usuario = $this->crearUsuarioTest('item-discrepancia');
        $producto = $this->crearProductoTest('PROD-DIFF');
        $producto->stock = 50;
        $producto->save();

        $sesion = SesionConteo::create([
            'estado' => 'en_proceso',
            'fecha_conteo' => Carbon::today(),
            'observaciones' => 'Conteo con diferencia',
            'total_productos' => 0,
            'total_diferencias' => 0,
            'id_usuario' => $usuario->id_usuario,
        ]);

        $response = $this->postJson("/api/stock/conteos/sesiones/{$sesion->id_sesion}/items", [
            'id_producto' => $producto->id_producto,
            'cantidad_fisica' => 45,
            'observaciones' => 'Falta de 5 unidades',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.diferencia', -5)
            ->assertJsonPath('data.diagnostico', 'faltante');

        $sesion->refresh();
        $this->assertSame(1, $sesion->total_productos);
        $this->assertSame(1, $sesion->total_diferencias);
    }

    public function test_bloquear_edicion_en_sesion_finalizada(): void
    {
        $usuario = $this->crearUsuarioTest('item-finalizado');
        $producto = $this->crearProductoTest('PROD-FINAL');
        $sesion = SesionConteo::create([
            'estado' => 'finalizado',
            'fecha_conteo' => Carbon::today(),
            'observaciones' => 'Sesión cerrada',
            'total_productos' => 0,
            'total_diferencias' => 0,
            'id_usuario' => $usuario->id_usuario,
        ]);

        $response = $this->postJson("/api/stock/conteos/sesiones/{$sesion->id_sesion}/items", [
            'id_producto' => $producto->id_producto,
            'cantidad_fisica' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_resumen_inconsistencias(): void
    {
        $usuario = $this->crearUsuarioTest('resumen-conteo');
        $productoA = $this->crearProductoTest('PROD-RES-A');
        $productoB = $this->crearProductoTest('PROD-RES-B');
        $productoC = $this->crearProductoTest('PROD-RES-C');

        $sesion = SesionConteo::create([
            'estado' => 'en_proceso',
            'fecha_conteo' => Carbon::today(),
            'observaciones' => 'Resumen con inconsistencias',
            'total_productos' => 0,
            'total_diferencias' => 0,
            'id_usuario' => $usuario->id_usuario,
        ]);

        $this->postJson("/api/stock/conteos/sesiones/{$sesion->id_sesion}/items", [
            'id_producto' => $productoA->id_producto,
            'cantidad_fisica' => 10,
        ]);
        $this->postJson("/api/stock/conteos/sesiones/{$sesion->id_sesion}/items", [
            'id_producto' => $productoB->id_producto,
            'cantidad_fisica' => $productoB->stock_disponible + 5,
        ]);
        $this->postJson("/api/stock/conteos/sesiones/{$sesion->id_sesion}/items", [
            'id_producto' => $productoC->id_producto,
            'cantidad_fisica' => $productoC->stock_disponible,
        ]);

        $response = $this->getJson("/api/stock/conteos/sesiones/{$sesion->id_sesion}");
        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.resumen.total_contados', 3)
            ->assertJsonPath('data.resumen.inconsistencias', 2);
    }

    public function test_finalizar_sesion_con_regularizacion(): void
    {
        $usuario = $this->crearUsuarioTest('finaliza-regula');
        $producto = $this->crearProductoTest('PROD-REG');
        $producto->stock = 20;
        $producto->save();

        $sesion = SesionConteo::create([
            'estado' => 'en_proceso',
            'fecha_conteo' => Carbon::today(),
            'observaciones' => 'Regularización final',
            'total_productos' => 0,
            'total_diferencias' => 0,
            'id_usuario' => $usuario->id_usuario,
        ]);

        $this->postJson("/api/stock/conteos/sesiones/{$sesion->id_sesion}/items", [
            'id_producto' => $producto->id_producto,
            'cantidad_fisica' => 15,
            'observaciones' => 'Falta de 5 unidades',
        ]);

        $response = $this->postJson("/api/stock/conteos/sesiones/{$sesion->id_sesion}/finalizar", [
            'aplicar_ajustes' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.estado', 'finalizado');

        $producto->refresh();
        $this->assertSame(15, $producto->stock_disponible);

        $this->assertDatabaseHas('movimiento_stock', [
            'id_producto' => $producto->id_producto,
            'tipo' => 'ajuste',
        ]);
    }
}
