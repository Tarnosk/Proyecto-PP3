<?php
namespace App\Modules\Rendiciones\Services;

class ProcesadorDeValidacionRendicion 
{
    public function validarRendicion(array $datos): array 
    {
        // 1. Valido que vengan los campos obligatorios para la revisión
        if (!isset($datos['id_rendicion']) || !isset($datos['accion']) || !isset($datos['id_usuario_validador'])) {
            return [
                "error" => true,
                "codigo" => 400,
                "mensaje" => "Faltan datos obligatorios (id_rendicion, accion o id_usuario_validador)."
            ];
        }

        $accion = strtolower(trim($datos['accion'])); // 'aprobar' o 'rechazar'
        $estadosPermitidos = ['aprobada', 'rechazada'];

        if (!in_array($accion, $estadosPermitidos)) {
            return [
                "error" => true,
                "codigo" => 400,
                "mensaje" => "La acción realizada no es válida. Opciones permitidas: aprobada, rechazada."
            ];
        }

        // 2. Si se rechaza, exigimos obligatoriamente un motivo de rechazo
        if ($accion === 'rechazada' && empty($datos['motivo_rechazo'])) {
            return [
                "error" => true,
                "codigo" => 400,
                "mensaje" => "Debe especificar un motivo de rechazo obligatoriamente."
            ];
        }

        // 3. Estructuro la respuesta de validación y cambio de estado
        return [
            "error" => false,
            "codigo" => 200,
            "mensaje" => "La rendición ha sido " . $accion . " exitosamente.",
            "data" => [
                "id_rendicion" => $datos['id_rendicion'],
                "estado_actual" => ucfirst($accion),
                "id_usuario_validador" => $datos['id_usuario_validador'],
                "fecha_validacion" => date('Y-m-d H:i:s'),
                "motivo_rechazo" => ($accion === 'rechazada') ? $datos['motivo_rechazo'] : null,
                "observaciones" => $datos['observaciones'] ?? 'Sin observaciones adicionales'
            ]
        ];
    }
}