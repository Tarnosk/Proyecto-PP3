<?php

namespace App\Modules\ConciliacionBancaria\Services;

use PhpOffice\PhpSpreadsheet\Reader\Xls;
use DateTime;
use Exception;

class BankColumnMapper
{
    private BankStatementParserService $parser;

    public function __construct(BankStatementParserService $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Parsea un archivo de extracto bancario (.xls o .txt).
     *
     * @return array{
     *   saldo_inicial: ?float,
     *   saldo_final: ?float,
     *   fecha_desde: ?string,
     *   fecha_hasta: ?string,
     *   movimientos: array<array>,
     *   errores: array<string>
     * }
     */
    public function parsear(string $rutaArchivo, string $extension): array
    {
        $extension = strtolower($extension);
        if ($extension === 'xls') {
            return $this->parsearXls($rutaArchivo);
        } elseif ($extension === 'txt') {
            return $this->parsearTxt($rutaArchivo);
        }

        throw new Exception("Formato de archivo no soportado: {$extension}. Únicamente se aceptan .xls y .txt.");
    }

    /**
     * Parsea extracto en formato XLS de Banco Provincia.
     */
    private function parsearXls(string $ruta): array
    {
        $reader = new Xls();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($ruta);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $movimientos = [];
        $errores = [];
        $headerFound = false;
        $saldoInicial = null;
        $saldoFinal = null;
        $fechaDesde = null;
        $fechaHasta = null;

        $meses = [
            'ene' => '01', 'enero' => '01',
            'feb' => '02', 'febrero' => '02',
            'mar' => '03', 'marzo' => '03',
            'abr' => '04', 'abril' => '04',
            'may' => '05', 'mayo' => '05',
            'jun' => '06', 'junio' => '06',
            'jul' => '07', 'julio' => '07',
            'ago' => '08', 'agosto' => '08',
            'sep' => '09', 'sept' => '09', 'set' => '09', 'septiembre' => '09',
            'oct' => '10', 'octubre' => '10',
            'nov' => '11', 'noviembre' => '11',
            'dic' => '12', 'diciembre' => '12'
        ];

        foreach ($rows as $nroFila => $cols) {
            $filaTexto = implode(' ', array_filter(array_map('strval', $cols)));
            if (empty(trim($filaTexto))) {
                continue;
            }

            // Buscar cabecera de columnas: Fecha, Descripción, Importe, Saldo
            if (!$headerFound) {
                if (stripos($filaTexto, 'Fecha') !== false && stripos($filaTexto, 'Importe') !== false) {
                    $headerFound = true;
                }
                continue;
            }

            // Pie de página o aviso legal de Banco Provincia
            if (stripos($filaTexto, 'Esta información') !== false || stripos($filaTexto, 'supeditada a los ajustes') !== false) {
                break;
            }

            // Col 1: Fecha, Col 2: Descripción, Col 3: Importe, Col 4: Saldo
            $valFecha = trim((string)($cols[1] ?? ''));
            $valDesc = trim((string)($cols[2] ?? ''));
            $valImporte = trim((string)($cols[3] ?? ''));
            $valSaldo = trim((string)($cols[4] ?? ''));

            if (empty($valFecha) || empty($valImporte)) {
                $errores[] = "Fila " . ($nroFila + 1) . ": Fecha o importe vacíos.";
                continue;
            }

            // Parsear fecha formato ej: '28-sept-2026' o '28/09/2026'
            $fechaOperacion = $this->normalizarFechaEsp($valFecha, $meses);
            if (!$fechaOperacion) {
                $errores[] = "Fila " . ($nroFila + 1) . ": Formato de fecha inválido ('{$valFecha}').";
                continue;
            }

            // Parsear importe numérico
            $montoLimpio = str_replace(['$', ' '], '', $valImporte);
            $montoFloat = (float)$montoLimpio;
            $signo = $montoFloat < 0 ? 'egreso' : 'ingreso';
            $montoAbs = abs($montoFloat);

            $descNorm = $this->parser->normalizarDescripcion($valDesc);
            $cuit = $this->parser->extraerCuit($valDesc);
            $comprobante = $this->parser->extraerComprobante($valDesc);
            $clasificacion = $this->parser->clasificar($descNorm, $montoFloat, $signo);

            if ($saldoFinal === null && is_numeric($valSaldo)) {
                $saldoFinal = (float)$valSaldo;
            }
            if (is_numeric($valSaldo)) {
                $saldoInicial = (float)$valSaldo;
            }

            if ($fechaHasta === null || $fechaOperacion > $fechaHasta) {
                $fechaHasta = $fechaOperacion;
            }
            if ($fechaDesde === null || $fechaOperacion < $fechaDesde) {
                $fechaDesde = $fechaOperacion;
            }

            $movimientos[] = [
                'nro_fila' => $nroFila + 1,
                'fecha_operacion' => $fechaOperacion,
                'monto' => $montoAbs,
                'signo_monto' => $signo,
                'descripcion_original' => $valDesc,
                'descripcion_normalizada' => substr($descNorm, 0, 255),
                'nro_comprobante_extraido' => $comprobante,
                'cuit_extraido' => $cuit,
                'tipo_movimiento' => $clasificacion['tipo_movimiento'],
                'estado_conciliacion' => $clasificacion['estado_conciliacion'],
            ];
        }

        return [
            'saldo_inicial' => $saldoInicial,
            'saldo_final' => $saldoFinal,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'movimientos' => $movimientos,
            'errores' => $errores,
        ];
    }

    /**
     * Parsea extracto en formato TXT posicional de Banco Provincia.
     */
    private function parsearTxt(string $ruta): array
    {
        $lines = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $movimientos = [];
        $errores = [];
        $saldoInicial = null;
        $saldoFinal = null;
        $fechaDesde = null;
        $fechaHasta = null;

        foreach ($lines as $idx => $line) {
            $line = rtrim($line, "\r\n");
            $tipoRegistro = substr($line, 0, 1);

            if ($tipoRegistro === 'H') {
                // Cabecera: H20-10-2026...
                continue;
            }
            if ($tipoRegistro === 'T') {
                // Trailer: T         3
                continue;
            }
            if ($tipoRegistro !== 'D') {
                continue;
            }

            // Registro D: Detalle
            // Pos 7..16: Fecha DD-MM-YYYY
            $strFecha = trim(substr($line, 7, 10));
            // Pos 17..96: Descripción (80 chars)
            $strDesc = trim(substr($line, 17, 80));
            // Pos 97..116: Comprobante (20 chars)
            $strComp = trim(substr($line, 97, 20));
            // Pos 130..152: CUIT emisor si viene posicional
            $posCuit = trim(substr($line, 130, 23));
            // Pos 158..159: Signo (+0 o -0)
            $signoChar = substr($line, 158, 1);
            // Pos 160..176: Monto en centavos (ej: 000000007000000 = 70000.00)
            $montoRaw = substr($line, 159, 17);
            // Pos 176..190: Saldo en centavos
            $saldoRaw = substr($line, 176);

            $fechaDt = DateTime::createFromFormat('d-m-Y', $strFecha);
            if (!$fechaDt) {
                $errores[] = "Línea " . ($idx + 1) . ": Fecha inválida ('{$strFecha}').";
                continue;
            }
            $fechaOperacion = $fechaDt->format('Y-m-d');

            $montoCentavos = (float)ltrim($montoRaw, '0');
            $montoFloat = $montoCentavos / 100.0;
            $signo = ($signoChar === '-') ? 'egreso' : 'ingreso';

            $descNorm = $this->parser->normalizarDescripcion($strDesc);
            $cuit = $this->parser->extraerCuit($strDesc) ?: $this->parser->extraerCuit($posCuit);
            $comprobante = !empty($strComp) ? $strComp : $this->parser->extraerComprobante($strDesc);

            $clasificacion = $this->parser->clasificar(
                $descNorm,
                $signo === 'egreso' ? -$montoFloat : $montoFloat,
                $signo
            );

            if ($fechaHasta === null || $fechaOperacion > $fechaHasta) {
                $fechaHasta = $fechaOperacion;
            }
            if ($fechaDesde === null || $fechaOperacion < $fechaDesde) {
                $fechaDesde = $fechaOperacion;
            }

            $movimientos[] = [
                'nro_fila' => $idx + 1,
                'fecha_operacion' => $fechaOperacion,
                'monto' => $montoFloat,
                'signo_monto' => $signo,
                'descripcion_original' => $strDesc,
                'descripcion_normalizada' => substr($descNorm, 0, 255),
                'nro_comprobante_extraido' => $comprobante,
                'cuit_extraido' => $cuit,
                'tipo_movimiento' => $clasificacion['tipo_movimiento'],
                'estado_conciliacion' => $clasificacion['estado_conciliacion'],
            ];
        }

        return [
            'saldo_inicial' => $saldoInicial,
            'saldo_final' => $saldoFinal,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'movimientos' => $movimientos,
            'errores' => $errores,
        ];
    }

    private function normalizarFechaEsp(string $texto, array $meses): ?string
    {
        $texto = trim(str_replace('/', '-', $texto));
        $partes = explode('-', $texto);
        if (count($partes) === 3) {
            $dia = str_pad($partes[0], 2, '0', STR_PAD_LEFT);
            $mesStr = strtolower(trim($partes[1]));
            $anio = $partes[2];

            $mesNum = $meses[$mesStr] ?? (is_numeric($mesStr) ? str_pad($mesStr, 2, '0', STR_PAD_LEFT) : null);
            if ($mesNum && checkdate((int)$mesNum, (int)$dia, (int)$anio)) {
                return sprintf('%04d-%02d-%02d', (int)$anio, (int)$mesNum, (int)$dia);
            }
        }
        return null;
    }
}
