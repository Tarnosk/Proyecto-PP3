<?php

namespace App\Modules\Caja\Models;

use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    protected $table = 'CAJA';
    protected $primaryKey = 'id_caja';
    public $timestamps = false;
    protected $dateFormat = 'Y-m-d H:i:s';

    public const ESTADO_ABIERTA = 'abierta';
    public const ESTADO_CERRADA = 'cerrada';

    public const ROL_ADMIN = 'admin';
    public const ROL_REPARTIDOR = 'repartidor';

    protected $fillable = [
        'id_usuario',
        'fecha',
        'monto_inicial',
        'monto_final',
        'monto_esperado',
        'diferencia',
        'cierre_automatico',
        'estado',
        'id_usuario_cierre',
        'fecha_hora_apertura',
        'fecha_hora_vencimiento',
        'fecha_hora_cierre',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto_inicial' => 'decimal:2',
        'monto_final' => 'decimal:2',
        'monto_esperado' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'cierre_automatico' => 'boolean',
        'fecha_hora_apertura' => 'datetime',
        'fecha_hora_vencimiento' => 'datetime',
        'fecha_hora_cierre' => 'datetime',
    ];

    public function movimientos()
    {
        return $this->hasMany(CajaMovimiento::class, 'id_caja', 'id_caja');
    }

    public function estaAbierta(): bool
    {
        return $this->estado === self::ESTADO_ABIERTA;
    }

    public function perteneceA(int $idUsuario): bool
    {
        return (int) $this->id_usuario === $idUsuario;
    }

    /**
     * Caja cerrada sola por vencimiento (72 h) a la que todavía no se le
     * cargó el monto contado: no tiene monto final ni diferencia.
     */
    public function estaPendienteDeConteo(): bool
    {
        return $this->estado === self::ESTADO_CERRADA
            && (bool) $this->cierre_automatico
            && $this->monto_final === null;
    }

    /**
     * Solo admin y repartidor manejan caja. El vendedor no.
     */
    public static function rolManejaCaja(string $rol): bool
    {
        return in_array($rol, [self::ROL_ADMIN, self::ROL_REPARTIDOR], true);
    }
}
