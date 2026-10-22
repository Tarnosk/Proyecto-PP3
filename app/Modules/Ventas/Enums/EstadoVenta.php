<?php

namespace App\Modules\Ventas\Enums;

/**
 * ESTADOS (FASES) DE UNA VENTA
 *
 * Un "enum" es una lista cerrada de valores posibles. Una venta SOLO puede
 * estar en uno de estos 7 estados; cualquier otro texto es un error.
 *
 * Camino normal de una venta (siempre hacia adelante, se pueden saltear fases):
 *
 *   pendiente → confirmada → preparada → entregada → pagada → facturada
 *        └──────────┴────────────┴───────────┴──→ cancelada (anulada)
 *
 * ESTE ARCHIVO ES LA ÚNICA FUENTE DE VERDAD de las reglas de estados.
 * Lo usan: el controlador de la API (para responder 409), el historial
 * de ventas (para mostrar u ocultar los botones) y los tests.
 * Si mañana cambia una regla, se cambia SOLO acá.
 */
enum EstadoVenta: string
{
    // El texto entre comillas es lo que se guarda en la columna venta.estado.
    case Pendiente = 'pendiente';
    case Confirmada = 'confirmada';
    case Preparada = 'preparada';
    case Entregada = 'entregada';
    case Pagada = 'pagada';
    case Facturada = 'facturada';
    case Cancelada = 'cancelada';

    /**
     * Nombre lindo para mostrar en pantalla (ej: "Confirmada").
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Confirmada => 'Confirmada',
            self::Preparada => 'Preparada',
            self::Entregada => 'Entregada',
            self::Pagada => 'Pagada',
            self::Facturada => 'Facturada',
            self::Cancelada => 'Cancelada (anulada)',
        };
    }

    /**
     * ¿Se puede MODIFICAR una venta en este estado?
     * Sí mientras el cliente todavía no pagó. Una vez pagada, facturada
     * o anulada, la venta queda "cerrada" y no se toca más.
     */
    public function permiteEdicion(): bool
    {
        return in_array($this, self::estadosAbiertos(), true);
    }

    /**
     * ¿Se puede ANULAR (botón "Eliminar") una venta en este estado?
     * Misma regla que modificar: solo antes del pago.
     */
    public function permiteAnulacion(): bool
    {
        return in_array($this, self::estadosAbiertos(), true);
    }

    /**
     * ¿Se puede RESTAURAR (des-anular)? Solo si está anulada.
     */
    public function permiteRestauracion(): bool
    {
        return $this === self::Cancelada;
    }

    /**
     * Estados a los que se puede AVANZAR desde el estado actual con
     * PATCH /api/ventas/{id}/estado.
     *
     * Reglas:
     *  - Siempre hacia adelante (nunca volver atrás).
     *  - Se pueden saltear fases (ej: cobrar en el mostrador: confirmada → pagada).
     *  - "facturada" solo se alcanza después de "pagada".
     *  - "cancelada" NO está acá: para anular se usa DELETE (pide motivo
     *    y corrige la cuenta corriente).
     *
     * @return list<self>
     */
    public function siguientes(): array
    {
        return match ($this) {
            self::Pendiente => [self::Confirmada, self::Preparada, self::Entregada, self::Pagada],
            self::Confirmada => [self::Preparada, self::Entregada, self::Pagada],
            self::Preparada => [self::Entregada, self::Pagada],
            self::Entregada => [self::Pagada],
            self::Pagada => [self::Facturada],
            self::Facturada, self::Cancelada => [], // estados finales
        };
    }

    /**
     * ¿Se puede pasar de este estado al estado $destino?
     */
    public function puedePasarA(self $destino): bool
    {
        return in_array($destino, $this->siguientes(), true);
    }

    /**
     * Convierte el texto guardado en la base al enum.
     * Si el texto no es un estado conocido (dato viejo o roto) devuelve null
     * en vez de explotar; quien lo llame decide qué hacer.
     */
    public static function desdeTexto(?string $texto): ?self
    {
        return self::tryFrom(strtolower(trim((string) $texto)));
    }

    /**
     * Todos los valores en texto, ej: ['pendiente', 'confirmada', ...].
     * Sirve para las reglas de validación.
     *
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Ficha completa del estado, para que el front sepa qué botones mostrar
     * (la devuelve GET /api/ventas/estados).
     */
    public function aArray(): array
    {
        return [
            'valor' => $this->value,
            'etiqueta' => $this->etiqueta(),
            'permite_modificar' => $this->permiteEdicion(),
            'permite_eliminar' => $this->permiteAnulacion(),
            'permite_restaurar' => $this->permiteRestauracion(),
            'siguientes' => array_map(fn (self $estado) => $estado->value, $this->siguientes()),
        ];
    }

    /**
     * FLAGS PARA EL FRONT a partir del texto guardado en venta.estado.
     * Los usan la API (VentaResource) y el historial web (VentaController@historial),
     * así los dos muestran SIEMPRE los mismos botones.
     *
     * Si el estado guardado es desconocido, todo queda en false (no se muestra ningún botón).
     *
     * @return array{anulada: bool, puede_modificar: bool, puede_eliminar: bool, puede_restaurar: bool}
     */
    public static function accionesPara(?string $estadoGuardado): array
    {
        $estado = self::desdeTexto($estadoGuardado);

        return [
            'anulada' => $estado === self::Cancelada,          // el front la muestra en gris
            'puede_modificar' => $estado?->permiteEdicion() ?? false,
            'puede_eliminar' => $estado?->permiteAnulacion() ?? false,
            'puede_restaurar' => $estado?->permiteRestauracion() ?? false,
        ];
    }

    /**
     * Estados "abiertos": la venta todavía se puede cambiar.
     *
     * @return list<self>
     */
    private static function estadosAbiertos(): array
    {
        return [self::Pendiente, self::Confirmada, self::Preparada, self::Entregada];
    }
}
