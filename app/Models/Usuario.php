<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Usuario extends Model
{
    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'user',
        'usuario',
        'password',
        'password_hash',
        'nombre',
        'rol',
        'estado',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
    ];

    public static function create(array $attributes = [])
    {
        $attributes = static::normalizarAtributos($attributes);

        $idUsuario = isset($attributes['id_usuario']) && (int) $attributes['id_usuario'] > 0
            ? (int) $attributes['id_usuario']
            : static::proximoIdUsuario();

        $attributes['id_usuario'] = $idUsuario;

        if (Schema::hasTable('cliente') && $idUsuario > 0) {
            try {
                if (!DB::table('cliente')->where('id_cliente', $idUsuario)->exists()) {
                    $colsCliente = Schema::getColumnListing('cliente');
                    $clienteData = [
                        'id_cliente' => $idUsuario,
                        'nombre' => $attributes['nombre'] ?? $attributes['user'] ?? $attributes['usuario'] ?? 'Cliente',
                        'estado' => 'activo',
                    ];
                    if (in_array('apellido_razon_social', $colsCliente, true)) {
                        $clienteData['apellido_razon_social'] = $clienteData['nombre'];
                    }
                    if (in_array('dni_cuit', $colsCliente, true)) {
                        $clienteData['dni_cuit'] = '99-' . str_pad((string)$idUsuario, 8, '0', STR_PAD_LEFT) . '-9';
                    }
                    if (in_array('telefono', $colsCliente, true)) {
                        $clienteData['telefono'] = '000-000000';
                    }
                    if (in_array('email', $colsCliente, true)) {
                        $clienteData['email'] = 'user' . $idUsuario . '@distribuidora.local';
                    }
                    if (in_array('direccion', $colsCliente, true)) {
                        $clienteData['direccion'] = 'S/D';
                    }
                    if (in_array('tipo_cliente', $colsCliente, true)) {
                        $clienteData['tipo_cliente'] = 'minorista';
                    }
                    if (in_array('creado_por', $colsCliente, true)) {
                        $clienteData['creado_por'] = 1;
                    }
                    DB::table('cliente')->insert($clienteData);
                }
            } catch (\Throwable $e) {
                // Sincronización secundaria opcional
            }
        }

        DB::table('usuario')->insert($attributes);

        return new static($attributes);
    }

    protected static function proximoIdUsuario(): int
    {
        $maxUsuario = (int) (DB::table('usuario')->max('id_usuario') ?? 0);

        if (Schema::hasTable('cliente')) {
            $maxCliente = (int) (DB::table('cliente')->max('id_cliente') ?? 0);
            return max($maxUsuario, $maxCliente) + 1;
        }

        return $maxUsuario + 1;
    }

    protected static function normalizarAtributos(array $attributes): array
    {
        $columnas = Schema::getConnection()->getSchemaBuilder()->getColumnListing('usuario');
        $normalizados = [];

        if (!array_key_exists('id_usuario', $attributes) && in_array('id_usuario', $columnas, true)) {
            $maxId = (int) (Schema::getConnection()->table('usuario')->max('id_usuario') ?? 0);
            $attributes['id_usuario'] = $maxId + 1;
        }

        foreach ($attributes as $clave => $valor) {
            $claveNormalizada = $clave;

            if ($clave === 'usuario' && in_array('user', $columnas, true) && !in_array('usuario', $columnas, true)) {
                $claveNormalizada = 'user';
            }

            if ($clave === 'user' && in_array('usuario', $columnas, true) && !in_array('user', $columnas, true)) {
                $claveNormalizada = 'usuario';
            }

            if ($clave === 'password_hash' && in_array('password', $columnas, true) && !in_array('password_hash', $columnas, true)) {
                $claveNormalizada = 'password';
            }

            if ($clave === 'password' && in_array('password_hash', $columnas, true) && !in_array('password', $columnas, true)) {
                $claveNormalizada = 'password_hash';
            }

            if ($claveNormalizada === 'nombre' && !in_array('nombre', $columnas, true)) {
                continue;
            }

            if (in_array($claveNormalizada, $columnas, true) || in_array($claveNormalizada, ['user', 'usuario', 'password', 'password_hash', 'nombre', 'rol', 'estado'], true)) {
                $normalizados[$claveNormalizada] = $valor;
            }
        }

        return $normalizados;
    }
}
