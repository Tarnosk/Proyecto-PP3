<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class G1ClientMigrationTest extends TestCase
{
    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_27_000001_complete_g1_clientes_schema.php');
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            throw new RuntimeException('Esta prueba requiere SQLite :memory: aislado.');
        }
    }

    private function createBase(): void
    {
        $base = require database_path('migrations/0001_01_01_000000_create_erp_tables.php');
        $base->up();
    }

    public function test_it_completes_a_fresh_g1_schema_without_creating_external_tables(): void
    {
        $this->createBase();
        $this->migration()->up();

        $this->assertEqualsCanonicalizing(['usuarios', 'clientes', 'login_logs', 'reclamos', 'zonas'], Schema::getTableListing(schemaQualified: false));
        foreach (['localidad', 'zona_id', 'condicion_iva', 'saldo', 'tipo_documento', 'direccion_calle', 'direccion_numero'] as $column) {
            $this->assertTrue(Schema::hasColumn('clientes', $column), $column);
        }
        $foreignKeys = Schema::getForeignKeys('clientes');
        $zoneKey = collect($foreignKeys)->first(fn (array $key): bool => $key['columns'] === ['zona_id']);
        $this->assertNotNull($zoneKey);
        $this->assertSame('zonas', $zoneKey['foreign_table']);
        $this->assertSame('set null', $zoneKey['on_delete']);
    }

    public function test_it_preserves_existing_clients_zones_and_columns_on_repeated_runs(): void
    {
        $this->createBase();
        Schema::create('zonas', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('nombre', 120)->unique();
            $table->string('descripcion', 255)->nullable();
        });
        Schema::table('clientes', function (Blueprint $table): void {
            $table->string('localidad', 120)->nullable();
            $table->string('tipo_documento', 4)->nullable();
            $table->unsignedInteger('zona_id')->nullable();
            $table->foreign('zona_id')->references('id')->on('zonas')->nullOnDelete();
        });
        $userId = DB::table('usuarios')->insertGetId([
            'usuario' => 'migration-test', 'password_hash' => 'unused', 'nombre' => 'Prueba', 'rol' => 'administrativo',
        ]);
        $zoneId = DB::table('zonas')->insertGetId(['nombre' => 'Norte', 'descripcion' => 'Zona existente']);
        DB::table('clientes')->insert([
            'nombre' => 'María', 'apellido_razon_social' => 'Pérez', 'dni_cuit' => '12345678',
            'tipo_documento' => 'dni', 'telefono' => '2923123456', 'email' => 'cliente@empresa.com',
            'direccion' => 'San Martín 123', 'localidad' => 'Pigüé', 'zona_id' => $zoneId,
            'tipo_cliente' => 'minorista', 'creado_por' => $userId,
            'created_at' => '2026-10-01 12:00:00', 'updated_at' => '2026-10-01 12:00:00',
        ]);
        $beforeClient = (array) DB::table('clientes')->first();
        $beforeZone = (array) DB::table('zonas')->first();
        $this->migration()->up();
        $columns = Schema::getColumnListing('clientes');
        $this->migration()->up();

        $this->assertSame($columns, Schema::getColumnListing('clientes'));
        $after = (array) DB::table('clientes')->first();
        foreach ($beforeClient as $column => $value) {
            $this->assertSame($value, $after[$column], $column);
        }
        $this->assertSame($beforeZone, (array) DB::table('zonas')->first());
        $this->assertNull($after['direccion_calle']);
        $this->assertNull($after['direccion_numero']);
        $this->assertEquals(0, $after['saldo']);
        $this->assertCount(2, Schema::getForeignKeys('clientes'));
    }

    public function test_it_fails_before_changing_anything_if_the_g1_base_is_missing(): void
    {
        try {
            $this->migration()->up();
            $this->fail('Debía requerir la tabla clientes de G1.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('falta la tabla clientes', $exception->getMessage());
            $this->assertSame([], Schema::getTableListing());
        }
    }

    public function test_automatic_rollback_refuses_to_delete_preexisting_g1_data(): void
    {
        $this->createBase();
        $this->migration()->up();
        try {
            $this->migration()->down();
            $this->fail('Debía impedir la eliminación automática de campos existentes.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('no admite rollback automático', $exception->getMessage());
            $this->assertTrue(Schema::hasTable('zonas'));
            $this->assertTrue(Schema::hasColumn('clientes', 'direccion_calle'));
        }
    }
}
