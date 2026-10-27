<?php

namespace Tests\Feature;

use App\Modules\Login\Enums\UserRole;
use App\Modules\Login\Enums\UserStatus;
use App\Modules\Login\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

/** Only G4 tables in an isolated, in-memory SQLite database. */
class G4SalesSchemaTest extends TestCase
{
    private const TABLES = ['venta', 'detalle_venta', 'cuenta_corriente_movimientos'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Http::preventStrayRequests();
    }

    private function baseMigration(): Migration
    {
        return require base_path('app/Modules/Ventas/database/migrations/2026_10_22_000000_create_g4_sales_base_tables.php');
    }

    private function accountsMigration(): Migration
    {
        return require base_path('app/Modules/CuentaCorriente/database/migrations/2026_10_07_000000_create_cuenta_corriente_movimientos_table.php');
    }

    private function installG4(): void
    {
        $this->accountsMigration()->up();
        $this->baseMigration()->up();
    }

    private function sessionAsAdmin(): void
    {
        $token = 'g4-schema-test';
        $user = new User;
        $user->forceFill([
            'id' => 1, 'usuario' => 'schema-test', 'nombre' => 'Prueba de esquema G4',
            'rol' => UserRole::ADMINISTRATIVO, 'estado' => UserStatus::ACTIVO,
            'current_session_id' => hash('sha256', $token),
        ]);
        $this->actingAs($user)->withSession([
            'session_token' => $token, 'last_activity' => now()->timestamp,
        ]);
    }

    public function test_official_g4_schema_allows_real_get_requests_without_database_mocks(): void
    {
        $this->installG4();
        $this->sessionAsAdmin();
        $this->assertSame([
            'id_venta', 'id_cliente', 'fecha', 'total', 'descuento_global',
            'numFactura', 'estado', 'observaciones', 'id_usuario',
        ], Schema::getColumnListing('venta'));
        $foreignKeys = Schema::getForeignKeys('detalle_venta');
        $this->assertCount(1, $foreignKeys);
        $this->assertSame('venta', $foreignKeys[0]['foreign_table']);

        $this->get('/ventas')->assertOk()->assertViewIs('ventas');
        $this->get('/historial-ventas')->assertOk()->assertViewIs('historial-ventas')
            ->assertViewHas('ventas', fn ($ventas) => $ventas->isEmpty());
        $this->get('/saldo')->assertOk()->assertViewIs('saldo')
            ->assertSee('Supermercado El Norte SRL');
        foreach (self::TABLES as $table) {
            $this->assertSame(0, DB::table($table)->count(), 'Un GET no debe crear operaciones.');
        }
        Http::assertNothingSent();
    }

    public function test_empty_installation_can_be_reverted_in_dependency_order(): void
    {
        $this->installG4();
        $this->baseMigration()->down();
        $this->accountsMigration()->down();
        foreach (self::TABLES as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }
    }

    public function test_rollback_stops_before_deleting_tables_if_any_g4_movements_exist(): void
    {
        $this->installG4();
        DB::table('cuenta_corriente_movimientos')->insert([
            'cliente_id' => 472891, 'tipo' => 'pago', 'descripcion' => 'Fixture aislado',
            'importe' => 100, 'created_at' => now(), 'updated_at' => now(),
        ]);
        try {
            $this->baseMigration()->down();
            $this->fail('La reversión debe rechazar una instalación con datos.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('contiene datos', $exception->getMessage());
        }
        foreach (self::TABLES as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
        $this->assertSame(1, DB::table('cuenta_corriente_movimientos')->count());
    }

    public function test_installation_refuses_to_replace_an_existing_g4_table(): void
    {
        $this->installG4();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ya existe');
        $this->baseMigration()->up();
    }
}
