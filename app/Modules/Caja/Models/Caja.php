<?php

namespace App\Modules\Caja\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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

    /** Horas antes del vencimiento desde las que se avisa que la caja está por vencer. */
    public const HORAS_AVISO_VENCIMIENTO = 24;

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

    /**
     * Campos derivados que viajan en la API y en las vistas (no se guardan).
     * El estado guardado sigue siendo solo abierta/cerrada.
     */
    protected $appends = ['vencida', 'por_vencer', 'horas_restantes'];

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
     * Abierta y pasada su fecha de vencimiento. Aplica a repartidor (72 h)
     * y a admin (7 días o fin de mes).
     */
    public function estaVencida(?Carbon $ahora = null): bool
    {
        $ahora ??= Carbon::now();

        return $this->estaAbierta()
            && $this->fecha_hora_vencimiento !== null
            && $ahora->greaterThan($this->fecha_hora_vencimiento);
    }

    /**
     * Abierta, todavía no vencida, y faltan 24 horas o menos para el
     * vencimiento. Es el aviso previo; no cambia el estado de la caja.
     */
    public function estaPorVencer(?Carbon $ahora = null): bool
    {
        $ahora ??= Carbon::now();

        return $this->estaAbierta()
            && $this->fecha_hora_vencimiento !== null
            && !$this->estaVencida($ahora)
            && $this->fecha_hora_vencimiento->lte($ahora->copy()->addHours(self::HORAS_AVISO_VENCIMIENTO));
    }

    /**
     * Horas que faltan para el vencimiento (1 decimal), o null si la caja no
     * está abierta, no tiene vencimiento o ya venció.
     */
    public function horasRestantes(?Carbon $ahora = null): ?float
    {
        $ahora ??= Carbon::now();

        if (!$this->estaAbierta() || $this->fecha_hora_vencimiento === null || $this->estaVencida($ahora)) {
            return null;
        }

        return round($ahora->diffInMinutes($this->fecha_hora_vencimiento, false) / 60, 1);
    }

    public function getVencidaAttribute(): bool
    {
        return $this->estaVencida();
    }

    public function getPorVencerAttribute(): bool
    {
        return $this->estaPorVencer();
    }

    public function getHorasRestantesAttribute(): ?float
    {
        return $this->horasRestantes();
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
