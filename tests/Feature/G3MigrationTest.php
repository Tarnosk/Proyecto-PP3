<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Tests\TestCase;

class G3MigrationTest extends TestCase
{
    public function test_g3_foreign_keys_migration_instantiates_and_executes_safely(): void
    {
        $migrationFile = base_path('app/Modules/Productos/database/migrations/2026_10_26_000001_migrate_g3_foreign_keys_to_usuarios_table.php');
        $this->assertFileExists($migrationFile);

        /** @var Migration $migration */
        $migration = require $migrationFile;
        $this->assertInstanceOf(Migration::class, $migration);

        // En SQLite (entorno de tests) debe retornar sin error
        $migration->up();
        $migration->down();
        $this->assertTrue(true);
    }
}
