<?php

namespace Tests\Feature;

use App\Modules\ConciliacionBancaria\Services\BankStatementParserService;
use App\Modules\ConciliacionBancaria\Services\BankColumnMapper;
use Tests\TestCase;

class BankStatementParserTest extends TestCase
{
    public function test_normaliza_descripcion_correctamente(): void
    {
        $parser = new BankStatementParserService();
        $normalizada = $parser->normalizarDescripcion('Transferencia  recibida  de  JUAN  PÉREZ');
        $this->assertEquals('TRANSFERENCIA RECIBIDA DE JUAN PEREZ', $normalizada);
    }

    public function test_extrae_cuit_valido(): void
    {
        $parser = new BankStatementParserService();
        $cuit = $parser->extraerCuit('TRANSF DE ESTEFANIA GIANOVICH (27408744739) VAR');
        $this->assertEquals('27408744739', $cuit);

        $cuitConGuiones = $parser->extraerCuit('PAGO 30-11223344-5 PROV');
        $this->assertEquals('30112233445', $cuitConGuiones);
    }

    public function test_clasifica_cobranza_cliente(): void
    {
        $parser = new BankStatementParserService();
        $clasif = $parser->clasificar('TRANSF DE CLIENTE CUIT 27408744739', 70000.0, 'ingreso');
        $this->assertEquals('cobranza_cliente', $clasif['tipo_movimiento']);
        $this->assertEquals('pendiente', $clasif['estado_conciliacion']);
    }

    public function test_clasifica_impuestos_y_gastos_bancarios(): void
    {
        $parser = new BankStatementParserService();
        $impuesto = $parser->clasificar('DB.LEY 25413 CRED', -500.0, 'egreso');
        $this->assertEquals('impuesto_bancario', $impuesto['tipo_movimiento']);
        $this->assertEquals('no_aplica', $impuesto['estado_conciliacion']);

        $gasto = $parser->clasificar('COMISION MANT CUENTA', -1200.0, 'egreso');
        $this->assertEquals('gasto_bancario', $gasto['tipo_movimiento']);
        $this->assertEquals('no_aplica', $gasto['estado_conciliacion']);
    }

    public function test_clasifica_contracargos_en_disputa(): void
    {
        $parser = new BankStatementParserService();
        $disputa = $parser->clasificar('RETENCION PREVENTIVA CONTRACARGO', -15000.0, 'egreso');
        $this->assertEquals('otro', $disputa['tipo_movimiento']);
        $this->assertEquals('en_disputa', $disputa['estado_conciliacion']);
    }
}
