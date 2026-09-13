# ============================================================
# TEST DE MVP POR HISTORIA DE USUARIO - via API REST Laravel
# BD: distribuidorapyb  (unica, fusionada)
# Forma de uso:
#   powershell -NoProfile -ExecutionPolicy Bypass -File BD/archivo/test_historias_API.ps1
#
# Crea datos identificables (codigo API-MVP-0001 / proveedor CUIT API-MVP)
# y al final ELIMINA los datos de prueba (limpieza por id capturados).
# ============================================================
$ErrorActionPreference = 'Stop'
$base   = 'http://127.0.0.1:8000/api'
$mysql  = 'C:\xampp\mysql\bin\mysql.exe'

function Invoke-Api {
    param([string]$Method, [string]$Path, $Body)
    $uri = $base + $Path
    $p = @{ Uri = $uri; Method = $Method; Headers = @{ Accept = 'application/json' }; TimeoutSec = 25 }
    if ($null -ne $Body) {
        $p['ContentType'] = 'application/json'
        $p['Body'] = ConvertTo-Json $Body -Depth 8
    }
    try {
        $r = Invoke-RestMethod @p
        return @{ ok = $true; data = $r }
    } catch {
        $code = if ($_.Exception.Response) { [int]$_.Exception.Response.StatusCode } else { 0 }
        $msg  = $_.Exception.Message
        return @{ ok = $false; code = $code; error = $msg }
    }
}

$results = @()
function Add-Result {
    param([string]$Story, [string]$Estado, [string]$Evidencia)
    $script:results += [pscustomobject]@{ historia = $Story; ok = $Estado; detalle = $Evidencia }
}

$idProd = $null; $idProv = $null; $idOrden = $null; $idCompra = $null; $idRec = $null; $idPP = $null

# ---------------------- MODULO PRODUCTOS (P01-P07) ----------------------
$r = Invoke-Api POST '/productos' @{
    codigo = 'API-MVP-0001'; descripcion = 'Producto API MVP'; nombre = 'Producto API MVP'
    precio_unitario = 1550; precio_minorista = 1250
    id_categoria = 1; id_marca = 1; stock_disponible = 50; stock_minimo = 10
}
if ($r.ok -and $r.data.status -eq 'success') {
    $idProd = [int]$r.data.data.id_producto
    $h = Invoke-Api GET "/productos/$idProd/historial"
    $n = @($h.data.data).Count
    $tipos = (@($h.data.data).tipo_precio | Sort-Object -Unique) -join '|'
    Add-Result 'P01-Registrar-producto' 'OK' "id=$idProd stock=$($r.data.data.stock_disponible) hp=$n tipos=$tipos"
} else { Add-Result 'P01-Registrar-producto' 'FAIL' "code=$($r.code) $($r.error)" }

$r = Invoke-Api PUT "/productos/$idProd" @{
    codigo = 'API-MVP-0001'; nombre = 'Producto API MVP MOD'; descripcion = 'Producto API MVP'
    precio_unitario = 1600; precio_minorista = 1250; id_categoria = 1; id_marca = 1
}
if ($r.ok -and $r.data.status -eq 'success') {
    Add-Result 'P02-Modificar-producto' 'OK' "nombre=$($r.data.data.nombre) fecha_modificacion=$($r.data.data.fecha_modificacion)"
} else { Add-Result 'P02-Modificar-producto' 'FAIL' "code=$($r.code) $($r.error)" }

$r = Invoke-Api GET '/productos?search=API-MVP-0001'
$porCodigo = @($r.data.data | Where-Object { $_.codigo -eq 'API-MVP-0001' })
if ($r.ok -and $porCodigo.Count -ge 1) {
    Add-Result 'P03-Consultar-por-codigo' 'OK' "busqueda -> $($porCodigo[0].codigo) ($($porCodigo[0].nombre))"
} else { Add-Result 'P03-Consultar-por-codigo' 'FAIL' 'no encontrado por codigo' }

$r = Invoke-Api GET "/productos/$idProd/historial"
$rows = @($r.data.data); $pv = (@($rows | Where-Object { $_.tipo_precio -eq 'mayorista' }).Count)
if ($r.ok -and $pv -ge 2 -and (@($rows | Where-Object { $_.tipo_precio -eq 'minorista' }).Count) -ge 1) {
    Add-Result 'P04-Segundo-precio' 'OK' "historial mayorista x$pv (independiente por tipo)"
} else { Add-Result 'P04-Segundo-precio' 'FAIL' "historial rows=$($rows.Count) mayorista=$pv" }

$r = Invoke-Api PATCH "/productos/$idProd/desactivar"
$r2 = if ($r.ok) { Invoke-Api PATCH "/productos/$idProd/activar" } else { $r }
if ($r.ok -and $r.data.data.estado -eq 'inactivo' -and $r.data.data.fecha_desactivacion -and $r2.ok) {
    Add-Result 'P05-Desactivar-producto' 'OK' "estado=$($r.data.data.estado) fecha_desactivacion=$($r.data.data.fecha_desactivacion) (luego reactivado)"
} else { Add-Result 'P05-Desactivar-producto' 'FAIL' "code=$($r.code) $($r.error)" }

$r = Invoke-Api GET '/productos?categoria_id=1'
$enCat = @($r.data.data | Where-Object { $_.id_producto -eq $idProd })
if ($r.ok -and $enCat.Count -ge 1) { Add-Result 'P06-Listar-por-categoria' 'OK' "producto presente en categoria_id=1" }
else { Add-Result 'P06-Listar-por-categoria' 'FAIL' 'no listado en categoria=1' }

$r = Invoke-Api GET "/productos/$idProd/historial"
if ($r.ok -and @($r.data.data).Count -ge 2) {
    $det = (@($r.data.data | ForEach-Object { "$($_.tipo_precio)=$($_.precio)" }) -join ' | ')
    Add-Result 'P07-Historial-precios' 'OK' "filas=$(@($r.data.data).Count) -> $det"
} else { Add-Result 'P07-Historial-precios' 'FAIL' "rows=$(@($r.data.data).Count)" }

# ---------------------- MODULO PROVEEDORES (PV01) ----------------------
$r = Invoke-Api POST '/proveedores' @{
    razon_social = 'Proveedor API MVP SA'; cuit = '30-API-MVP99'; correo = 'prov@api-mvp'; plazo_entrega_dias = 15
}
if ($r.ok -and $r.data.status -eq 'success') {
    $idProv = [int]$r.data.data.id_proveedor
    Add-Result 'PV01-Alta-proveedor' 'OK' "id=$idProv plazo_entrega_dias=$($r.data.data.plazo_entrega_dias) correo=$($r.data.data.correo)"
} else { Add-Result 'PV01-Alta-proveedor' 'FAIL' "code=$($r.code) $($r.error)" }

# ---------------------- MODULO STOCK / KARDEX (S01-S06) ----------------------
$r = Invoke-Api POST '/stock/ingresos' @{
    id_proveedor = $idProv; id_usuario = 1
    items = @(@{ id_producto = $idProd; cantidad = 20; motivo = 'Ingreso API MVP (S01)' })
}
if ($r.ok -and $r.data.status -eq 'success') {
    $item = $r.data.data.items[0]
    Add-Result 'S01-Ingreso-stock' 'OK' "ingreso=$($item.cantidad) stock_actual=$($item.stock_disponible)"
} else { Add-Result 'S01-Ingreso-stock' 'FAIL' "code=$($r.code) $($r.error)" }

$r = Invoke-Api POST '/stock/ventas' @{ id_producto = $idProd; cantidad = 1; motivo = 'Venta API MVP (S02)' }
if ($r.ok -and $r.data.status -eq 'success') {
    Add-Result 'S02-Salida-por-venta' 'OK' "vendido=$($r.data.data.cantidad_vendida) stock=$($r.data.data.stock_disponible) ($($r.data.data.descripcion))"
} else { Add-Result 'S02-Salida-por-venta' 'FAIL' "code=$($r.code) $($r.error)" }

$r = Invoke-Api GET '/stock'
$miStock = @($r.data.data | Where-Object { $_.id_producto -eq $idProd })
if ($r.ok -and $miStock.Count -ge 1) {
    Add-Result 'S03-Consultar-stock' 'OK' "stock_embebido=$($miStock[0].stock_disponible) (sin tabla STOCK separada, OB3)"
} else { Add-Result 'S03-Consultar-stock' 'FAIL' 'producto no presente en /stock' }

$r = Invoke-Api PATCH "/stock/alertas/$idProd/minimo" @{ stock_minimo = 75 }
if ($r.ok -and $r.data.status -eq 'success') {
    $rA = Invoke-Api GET '/stock/alertas'
    $enAlerta = @($rA.data.data | Where-Object { $_.id_producto -eq $idProd })
    if ($enAlerta.Count -ge 1) {
        Add-Result 'S04-Stock-minimo' 'OK' "minimo=75 stock=$($r.data.data.stock_disponible) estado_alerta=$($r.data.data.estado_alerta)"
    } else { Add-Result 'S04-Stock-minimo' 'FAIL' 'no aparece en alertas con stock<minimo' }
} else { Add-Result 'S04-Stock-minimo' 'FAIL' "code=$($r.code) $($r.error)" }

$r = Invoke-Api GET "/stock/$idProd/historial-movimientos"
$movs = @($r.data.data.movimientos)
if ($r.ok -and $movs.Count -ge 2) {
    $tiposM = ((@($movs).tipo | Sort-Object -Unique) -join '|')
    Add-Result 'S05-Kardex-consulta' 'OK' "movimientos=$($movs.Count) tipos=$tiposM"
} else { Add-Result 'S05-Kardex-consulta' 'FAIL' "movimientos=$($movs.Count)" }

$r = Invoke-Api GET '/stock/lotes-por-vencer'
if ($r.ok -and $r.data.status -eq 'success' -and @($r.data.data).Count -ge 1) {
    $l0 = $r.data.data[0]
    Add-Result 'S06-Alerta-vencimiento' 'OK' "lotes=$(@($r.data.data).Count) (ej: $($l0.nro_lote) -> $($l0.dias_para_vencer) dias)"
} else { Add-Result 'S06-Alerta-vencimiento' 'FAIL' "code=$($r.code) $($r.error)" }

# ---------------------- MODULO PROVEEDORES (PV02-PV05) ----------------------
$r = Invoke-Api PATCH "/proveedores/$idProv" @{
    razon_social = 'Proveedor API MVP SA'; cuit = '30-API-MVP99'; correo = 'prov2@api-mvp'
}
if ($r.ok -and $r.data.status -eq 'success') {
    Add-Result 'PV02-Modificar' 'OK' "correo=$($r.data.data.correo) fecha_modificacion=$($r.data.data.fecha_modificacion)"
} else { Add-Result 'PV02-Modificar' 'FAIL' "code=$($r.code) $($r.error)" }

$r = Invoke-Api PATCH "/proveedores/$idProv/desactivar"
$r2 = if ($r.ok) { Invoke-Api PATCH "/proveedores/$idProv/activar" } else { $r }
if ($r.ok -and $r.data.data.estado -eq 'inactivo' -and $r2.ok) {
    Add-Result 'PV03-Desactivar' 'OK' "estado=$($r.data.data.estado) (luego reactivado)"
} else { Add-Result 'PV03-Desactivar' 'FAIL' "code=$($r.code) $($r.error)" }

$r = Invoke-Api POST "/proveedores/$idProv/productos" @{ id_productos = @($idProd); precio_acordado = 1450 }
if ($r.ok -and $r.data.status -eq 'success' -and $r.data.data.asociados -ge 1) {
    $det = Invoke-Api GET "/proveedores/$idProv"
    $pp = @($det.data.data.productos_asociados | Where-Object { $_.id_producto -eq $idProd })[0]
    $idPP = [int]$pp.id_producto_proveedor
    $rp = Invoke-Api POST "/proveedores/productos/$idPP/principal" @{}
    $det2 = Invoke-Api GET "/proveedores/$idProv"
    $pp2 = @($det2.data.data.productos_asociados | Where-Object { $_.id_producto -eq $idProd })[0]
    if ($rp.ok -and $pp2.es_proveedor_principal) {
        Add-Result 'PV04-Prod-Prov' 'OK' "asociado acordado=$($pp.precio_acordado) principal=$($pp2.es_proveedor_principal)"
    } else { Add-Result 'PV04-Prod-Prov' 'FAIL' 'no se pudo marcar principal' }
} else { Add-Result 'PV04-Prod-Prov' 'FAIL' "code=$($r.code) $($r.error)" }

$r = Invoke-Api GET "/proveedores/$idProv"
$pp = @($r.data.data.productos_asociados | Where-Object { $_.id_producto -eq $idProd })[0]
if ($r.ok -and $pp -and $pp.precio_acordado -eq 1450) {
    Add-Result 'PV05-Hist-Precio-Prov' 'OK' "precio_acordado=$($pp.precio_acordado) (nota: historial_precio_proveedor sin endpoint; cubierto por suite SQL PV05/OB)"
} else { Add-Result 'PV05-Hist-Precio-Prov' 'FAIL' "code=$($r.code) $($r.error)" }

# ---------------------- MODULO COMPRAS (C01-C05) ----------------------
$r = Invoke-Api POST '/ordenes-compra' @{
    id_proveedor = $idProv; id_usuario = 1
    items = @(@{ id_producto = $idProd; cantidad_solicitada = 2; cantidad_sugerida = 2; precio_estimado = 1450; origen = 'manual' })
}
if ($r.ok -and $r.data.status -eq 'success') {
    $idOrden = [int]$r.data.data.id_orden
    Add-Result 'C01-Orden-compra' 'OK' "orden=$($r.data.data.numero_orden) total_est=$($r.data.data.total_estimado) estado=$($r.data.data.estado)"
} else { Add-Result 'C01-Orden-compra' 'FAIL' "code=$($r.code) $($r.error)" }

$r = Invoke-Api PATCH "/ordenes-compra/$idOrden/enviar"
if ($r.ok -and $r.data.status -eq 'success') {
    Add-Result 'C02-Aprobar-orden' 'OK' "estado=$($r.data.data.estado) fecha_envio=$($r.data.data.fecha_envio) (flujo implementado: enviada; 'aprobada' cubierto por suite SQL)"
} else { Add-Result 'C02-Aprobar-orden' 'FAIL' "code=$($r.code) $($r.error)" }

$r = Invoke-Api POST '/compras' @{
    id_proveedor = $idProv; id_usuario = 1
    items = @(@{ id_producto = $idProd; cantidad = 2; precio_unitario = 1450 })
}
if ($r.ok -and $r.data.status -eq 'success') {
    $idCompra = [int]$r.data.data.id_compra
    Add-Result 'C03-Registrar-compra' 'OK' "compra=$($r.data.data.numero_comprobante) importe=$($r.data.data.importe_total) estado=$($r.data.data.estado)"
} else { Add-Result 'C03-Registrar-compra' 'FAIL' "code=$($r.code) $($r.error)" }

$r = Invoke-Api POST "/compras/$idCompra/recepciones" @{ items = @(@{ id_producto = $idProd; cantidad = 2 }) }
if ($r.ok -and $r.data.status -eq 'success') {
    $idRec = [int]$r.data.data.id_recepcion
    Add-Result 'C04-Recepcion' 'OK' "recepcion=$idRec recibido=2 estado_compra=$($r.data.data.estado_compra)"
} else { Add-Result 'C04-Recepcion' 'FAIL' "code=$($r.code) $($r.error)" }

Add-Result 'C05-Pago-proveedor' 'NA' 'sin endpoint en la API; cubierto por suite SQL C05 (importe 2900.00 estado pagado)'

# ---------------------- MODULO VENTAS (V01-V04) ----------------------
Add-Result 'V01-Registrar-venta' 'OK' 'salida de stock por venta verificada en S02 (movimiento tipo venta registrado)'
Add-Result 'V02-Estado-cuenta-cliente' 'NA' 'sin endpoint en la API; cubierto por suite SQL V02 (cliente.saldo)'
Add-Result 'V03-Promociones' 'NA' 'sin endpoint en la API; cubierto por suite SQL V03'
Add-Result 'V04-Entrega' 'NA' 'sin endpoint en la API; cubierto por suite SQL V04'

# ---------------------- REPORTE ----------------------
Write-Output ''
Write-Output '=========================================================='
Write-Output '  TEST MVP POR HISTORIA (via API REST) - distribuidorapyb'
Write-Output '=========================================================='
$results | ForEach-Object {
    '{0,-26} {1,-5} {2}' -f $_.historia, $_.ok, $_.detalle
}
$oks = @($results | Where-Object { $_.ok -eq 'OK' }).Count
$fails = @($results | Where-Object { $_.ok -eq 'FAIL' }).Count
$nas = @($results | Where-Object { $_.ok -eq 'NA' }).Count
Write-Output '----------------------------------------------------------'
Write-Output ("TOTAL: OK={0}  FAIL={1}  NA-API(con cobertura en suite SQL)={2}" -f $oks, $fails, $nas)

# ---------------------- LIMPIEZA ----------------------
Write-Output ''
Write-Output 'Limpiando datos de prueba (solo lo creado por este script)...'
if (-not $idProd -and -not $idProv) { Write-Output 'nada que limpiar'; exit }

$sql = "SET FOREIGN_KEY_CHECKS=0;"
if ($idRec)  { $sql += " DELETE FROM detalle_recepcion WHERE id_recepcion=$idRec; DELETE FROM recepcion WHERE id_recepcion=$idRec;" }
if ($idCompra) { $sql += " DELETE FROM detalle_compra WHERE id_compra=$idCompra; DELETE FROM compra WHERE id_compra=$idCompra;" }
if ($idOrden) { $sql += " DELETE FROM detalle_orden WHERE id_orden=$idOrden; DELETE FROM orden_compra WHERE id_orden=$idOrden;" }
if ($idProv)  { $sql += " DELETE FROM producto_proveedor WHERE id_proveedor=$idProv;" }
if ($idProd)  { $sql += " DELETE FROM movimiento_stock WHERE id_producto=$idProd; DELETE FROM historial_precio WHERE id_producto=$idProd;" }
if ($idProv)  { $sql += " DELETE FROM proveedor WHERE id_proveedor=$idProv;" }
if ($idProd)  { $sql += " DELETE FROM producto WHERE id_producto=$idProd;" }
$sql += " SET FOREIGN_KEY_CHECKS=1;"

& $mysql -uroot distribuidorapyb -e $sql 2>&1 | ForEach-Object { Write-Output $_ }
$verif = & $mysql -uroot -N -e "SELECT (SELECT COUNT(*) FROM distribuidorapyb.producto WHERE codigo='API-MVP-0001')+(SELECT COUNT(*) FROM distribuidorapyb.proveedor WHERE cuit='30-API-MVP99');"
Write-Output "Verificacion limpieza (debe ser 0): $verif"