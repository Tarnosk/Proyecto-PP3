<?php
declare(strict_types=1);

namespace App\Modules\Proveedores\Services;

use App\Modules\Proveedores\Models\HistorialPlazoProveedor;
use App\Modules\Proveedores\Models\Proveedor;
use App\Modules\Proveedores\Repositories\HistorialPlazoProveedorRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * PV07 - Registrar plazos de entrega y conservar su historial.
 *
 * PROVEEDOR.plazo_entrega_dias guarda el valor vigente. Cada cambio queda
 * registrado en HISTORIAL_PLAZO_PROVEEDOR con el valor anterior, el nuevo, la
 * fecha y el usuario, que es lo que pedía el criterio de aceptación.
 *
 * Los cambios que pasan por acá (alta, modificación y endpoint dedicado) quedan
 * auditados. Un alta con plazo deja el primer registro con plazo_anterior_dias
 * en null.
 *
 * No se puede "quitar" el plazo dejando historial porque la columna
 * plazo_nuevo_dias es NOT NULL en el diseño: un registro sin plazo no se podría
 * representar sin mentir sobre el valor.
 */
class PlazoEntregaService
{
    public function __construct(private HistorialPlazoProveedorRepository $historial)
    {
    }

    /**
     * Registra el plazo con el que se da de alta un proveedor.
     *
     * El alta de POST /api/proveedores ya guarda el plazo en PROVEEDOR, así que
     * acá solo se escribe el primer renglón del historial, con el plazo anterior
     * en null porque antes no había plazo.
     */
    public function registrarAlta(Proveedor $proveedor, ?int $plazo, int $idUsuario): void
    {
        if ($plazo === null) {
            return;
        }

        $this->historial->registrar([
            'id_proveedor' => $proveedor->id_proveedor,
            'plazo_anterior_dias' => null,
            'plazo_nuevo_dias' => $plazo,
            'fecha_cambio' => now(),
            'id_usuario' => $idUsuario,
        ]);
    }

    /**
     * Fija el plazo de un proveedor y deja el cambio en el historial.
     */
    public function cambiarPlazo(Proveedor $proveedor, int $plazo, int $idUsuario): array
    {
        $anterior = $proveedor->plazo_entrega_dias === null ? null : (int) $proveedor->plazo_entrega_dias;

        if ($anterior === $plazo) {
            return [
                'cambiado' => false,
                'mensaje' => 'El plazo ya era ese, no se registró ningún cambio.',
                'plazo_anterior_dias' => $anterior,
                'plazo_nuevo_dias' => $plazo,
            ];
        }

        DB::transaction(function () use ($proveedor, $plazo, $anterior, $idUsuario) {
            $proveedor->plazo_entrega_dias = $plazo;
            $proveedor->save();

            $this->historial->registrar([
                'id_proveedor' => $proveedor->id_proveedor,
                'plazo_anterior_dias' => $anterior,
                'plazo_nuevo_dias' => $plazo,
                'fecha_cambio' => now(),
                'id_usuario' => $idUsuario,
            ]);
        });

        return [
            'cambiado' => true,
            'mensaje' => 'Plazo de entrega actualizado.',
            'plazo_anterior_dias' => $anterior,
            'plazo_nuevo_dias' => $plazo,
        ];
    }

    /**
     * Historial de cambios del plazo, del más reciente al más antiguo.
     */
    public function historial(int $idProveedor, int $limite = 0): array
    {
        $proveedor = Proveedor::find($idProveedor);
        if (!$proveedor) {
            throw new RuntimeException('El proveedor no existe.');
        }

        return [
            'id_proveedor' => $proveedor->id_proveedor,
            'razon_social' => $proveedor->razon_social,
            'plazo_vigente_dias' => $proveedor->plazo_entrega_dias === null
                ? null
                : (int) $proveedor->plazo_entrega_dias,
            'total_cambios' => $this->historial->contar($idProveedor),
            'historial' => $this->historial->listarPorProveedor($idProveedor, $limite)
                ->map(fn (HistorialPlazoProveedor $h) => self::formatear($h))
                ->toArray(),
        ];
    }

    public static function formatear(HistorialPlazoProveedor $h): array
    {
        return [
            'id_historial_plazo' => $h->id_historial_plazo,
            'id_proveedor' => $h->id_proveedor,
            'plazo_anterior_dias' => $h->plazo_anterior_dias,
            'plazo_nuevo_dias' => $h->plazo_nuevo_dias,
            'fecha_cambio' => $h->fecha_cambio?->format('Y-m-d H:i:s'),
            'id_usuario' => $h->id_usuario,
        ];
    }
}