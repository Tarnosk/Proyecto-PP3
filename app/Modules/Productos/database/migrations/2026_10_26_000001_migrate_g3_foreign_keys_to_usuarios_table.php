<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mapeo de claves foráneas de Grupo 3 que apuntaban a la tabla histórica `usuario(id_usuario)`
     * y ahora deben apuntar a la tabla canónica de login `usuarios(id)`.
     */
    private array $foreignKeys = [
        [
            'table' => 'producto',
            'column' => 'id_usuario_carga',
            'old_fk' => 'fk_producto_usuario_carga',
            'new_fk' => 'fk_producto_usuario_carga_usuarios',
            'nullable' => false,
            'onDelete' => 'restrict',
        ],
        [
            'table' => 'producto',
            'column' => 'id_usuario_modificacion',
            'old_fk' => 'fk_producto_usuario_mod',
            'new_fk' => 'fk_producto_usuario_mod_usuarios',
            'nullable' => true,
            'onDelete' => 'set null',
        ],
        [
            'table' => 'proveedor',
            'column' => 'id_usuario_carga',
            'old_fk' => 'fk_proveedor_usuario_carga',
            'new_fk' => 'fk_proveedor_usuario_carga_usuarios',
            'nullable' => false,
            'onDelete' => 'restrict',
        ],
        [
            'table' => 'proveedor',
            'column' => 'id_usuario_modificacion',
            'old_fk' => 'fk_proveedor_usuario_mod',
            'new_fk' => 'fk_proveedor_usuario_mod_usuarios',
            'nullable' => true,
            'onDelete' => 'set null',
        ],
        [
            'table' => 'producto_ubicacion',
            'column' => 'id_usuario',
            'old_fk' => 'fk_prod_ubic_usuario',
            'new_fk' => 'fk_prod_ubic_usuario_usuarios',
            'nullable' => true,
            'onDelete' => 'set null',
        ],
        [
            'table' => 'movimiento_stock',
            'column' => 'id_usuario',
            'old_fk' => 'fk_mov_stock_usuario',
            'new_fk' => 'fk_mov_stock_usuario_usuarios',
            'nullable' => false,
            'onDelete' => 'restrict',
        ],
        [
            'table' => 'sesion_conteo',
            'column' => 'id_usuario',
            'old_fk' => 'fk_conteo_usuario',
            'new_fk' => 'fk_conteo_usuario_usuarios',
            'nullable' => false,
            'onDelete' => 'restrict',
        ],
        [
            'table' => 'historial_precio',
            'column' => 'id_usuario',
            'old_fk' => 'fk_hist_precio_usuario',
            'new_fk' => 'fk_hist_precio_usuario_usuarios',
            'nullable' => false,
            'onDelete' => 'restrict',
        ],
        [
            'table' => 'producto_proveedor',
            'column' => 'id_usuario',
            'old_fk' => 'fk_prod_prov_usuario',
            'new_fk' => 'fk_prod_prov_usuario_usuarios',
            'nullable' => true,
            'onDelete' => 'set null',
        ],
        [
            'table' => 'historial_precio_proveedor',
            'column' => 'id_usuario',
            'old_fk' => 'fk_hist_prov_usuario',
            'new_fk' => 'fk_hist_prov_usuario_usuarios',
            'nullable' => true,
            'onDelete' => 'set null',
        ],
        [
            'table' => 'historial_plazo_proveedor',
            'column' => 'id_usuario',
            'old_fk' => 'historial_plazo_proveedor_id_usuario_foreign',
            'new_fk' => 'fk_hist_plazo_prov_usuarios',
            'nullable' => false,
            'onDelete' => 'restrict',
        ],
        [
            'table' => 'orden_compra',
            'column' => 'id_usuario',
            'old_fk' => 'fk_orden_usuario',
            'new_fk' => 'fk_orden_usuario_usuarios',
            'nullable' => false,
            'onDelete' => 'restrict',
        ],
        [
            'table' => 'compra',
            'column' => 'id_usuario',
            'old_fk' => 'fk_compra_usuario',
            'new_fk' => 'fk_compra_usuario_usuarios',
            'nullable' => false,
            'onDelete' => 'restrict',
        ],
        [
            'table' => 'compra',
            'column' => 'id_usuario_modificacion',
            'old_fk' => 'fk_compra_usuario_mod',
            'new_fk' => 'fk_compra_usuario_mod_usuarios',
            'nullable' => true,
            'onDelete' => 'set null',
        ],
        [
            'table' => 'recepcion',
            'column' => 'id_usuario',
            'old_fk' => 'fk_recepcion_usuario',
            'new_fk' => 'fk_recepcion_usuario_usuarios',
            'nullable' => false,
            'onDelete' => 'restrict',
        ],
        [
            'table' => 'pago_proveedor',
            'column' => 'id_usuario',
            'old_fk' => 'fk_pago_usuario',
            'new_fk' => 'fk_pago_usuario_usuarios',
            'nullable' => false,
            'onDelete' => 'restrict',
        ],
        [
            'table' => 'sugerencia_compra',
            'column' => 'id_usuario_resolucion',
            'old_fk' => 'sugerencia_compra_id_usuario_resolucion_foreign',
            'new_fk' => 'fk_sug_compra_usuario_res_usuarios',
            'nullable' => true,
            'onDelete' => 'set null',
        ],
        [
            'table' => 'log_auditoria',
            'column' => 'id_usuario',
            'old_fk' => 'fk_log_auditoria_usuario',
            'new_fk' => 'fk_log_auditoria_usuario_usuarios',
            'nullable' => true,
            'onDelete' => 'set null',
        ],
    ];

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        if (! Schema::hasTable('usuarios')) {
            return;
        }

        $defaultUserId = (int) (DB::table('usuarios')->value('id') ?? 1);

        foreach ($this->foreignKeys as $fk) {
            $table = $fk['table'];
            $column = $fk['column'];

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            // 1. Dropear clave foránea anterior si existe
            $existingOldFk = DB::selectOne(
                "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
                [$table, $fk['old_fk']]
            );
            if ($existingOldFk) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk['old_fk']}`");
            }

            // 2. Alinear valores huérfanos que no existan en `usuarios`
            DB::statement("UPDATE `{$table}` SET `{$column}` = {$defaultUserId} WHERE `{$column}` IS NOT NULL AND `{$column}` NOT IN (SELECT `id` FROM `usuarios`)");

            // 3. Modificar columna para que coincida exactamente con `usuarios.id` (INT UNSIGNED)
            $nullSql = $fk['nullable'] ? 'NULL DEFAULT NULL' : 'NOT NULL';
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` INT(10) UNSIGNED {$nullSql}");

            // 4. Crear la nueva clave foránea hacia `usuarios(id)`
            $existingNewFk = DB::selectOne(
                "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
                [$table, $fk['new_fk']]
            );
            if (! $existingNewFk) {
                $onDelete = strtoupper($fk['onDelete']);
                DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$fk['new_fk']}` FOREIGN KEY (`{$column}`) REFERENCES `usuarios` (`id`) ON DELETE {$onDelete} ON UPDATE CASCADE");
            }
        }

        // Se mantiene la tabla `usuario` sin alterar para no afectar claves foráneas de otros grupos (ej. G2 caja_movimiento)
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        foreach ($this->foreignKeys as $fk) {
            $table = $fk['table'];

            if (! Schema::hasTable($table)) {
                continue;
            }

            $existingNewFk = DB::selectOne(
                "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
                [$table, $fk['new_fk']]
            );
            if ($existingNewFk) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk['new_fk']}`");
            }
        }
    }
};
