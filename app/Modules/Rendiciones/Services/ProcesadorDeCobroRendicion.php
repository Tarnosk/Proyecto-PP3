<?php
namespace App\Modules\Rendiciones\Services;

class ProcesadorDeCobroRendicion 
{
    /**
     * Registra un cobro realizado durante un reparto, asociándolo a una rendición y validando sus reglas de negocio.
     * 
     * @param array $datos Datos provenientes del request (id_rendicion, id_cliente, id_factura, monto, medio_pago)
     * @return array Resultado de la operación con código HTTP y mensaje descriptivo
     */
    public function registrarCobro(array $datos): array 
    {
        // 1. Valido que los datos principales e imprescindibles no falten
        $errorValidacion = $this->validarCamposObligatorios($datos);
        if ($errorValidacion) {
            return $errorValidacion;
        }

        // 2. Valido que el monto sea un número válido y mayor a cero
        if (!is_numeric($datos['monto']) || $datos['monto'] <= 0) {
            return [
                "error" => true,
                "codigo" => 400,
                "mensaje" => "El monto abonado es inválido. Debe ser mayor a cero."
            ];
        }

        // 3. Valido que el medio de pago esté entre los permitidos por el negocio
        $mediosPermitidos = ['efectivo', 'transferencia', 'cheque'];
        $medioPago = strtolower(trim($datos['medio_pago']));
        
        if (!in_array($medioPago, $mediosPermitidos)) {
            return [
                "error" => true,
                "codigo" => 400,
                "mensaje" => "El medio de pago seleccionado no es válido. Opciones permitidas: efectivo, transferencia, cheque."
            ];
        }

        // 4. (Simulación de validación con dependencias externas / Mocks):
        // Aquí en el futuro validaremos contra el módulo de Ventas que la factura exista y tenga saldo pendiente,
        // y contra Clientes que el cliente esté activo. Por ahora, simulamos que el chequeo es exitoso.

        // 5. Si todo pasa de forma correcta, retornamos el éxito con el detalle del registro
        return [
            "error" => false,
            "codigo" => 201,
            "mensaje" => "Cobro registrado correctamente, asociado a la rendición e impactado en la cuenta del cliente.",
            "data" => [
                "id_rendicion" => $datos['id_rendicion'],
                "id_cliente" => $datos['id_cliente'],
                "id_factura" => $datos['id_factura'],
                "monto" => $datos['monto'],
                "medio_pago" => $medioPago,
                "fecha_registro" => date('Y-m-d H:i:s')
            ]
        ];
    }

    /**
     * Método auxiliar privado para validar la presencia de los campos obligatorios.
     */
    private function validarCamposObligatorios(array $datos): ?array 
    {
        $camposRequeridos = ['id_rendicion', 'id_cliente', 'id_factura', 'monto', 'medio_pago'];

        foreach ($camposRequeridos as $campo) {
            if (!isset($datos[$campo]) || $datos[$campo] === '') {
                return [
                    "error" => true,
                    "codigo" => 400,
                    "mensaje" => "Falta el campo obligatorio: {$campo}."
                ];
            }
        }

        return null;
    }
}