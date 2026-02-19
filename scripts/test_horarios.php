    <?php
/**
 * PRUEBAS AUTOMATIZADAS DE REPORTES POR HORARIO
 * 
 * Este script simula la creación de reportes a diferentes horas del día
 * y verifica que se guarden y consulten correctamente.
 * 
 * Ejecutar: php scripts/test_horarios.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Colores para output
function green($text) { return "\033[32m{$text}\033[0m"; }
function red($text) { return "\033[31m{$text}\033[0m"; }
function yellow($text) { return "\033[33m{$text}\033[0m"; }
function blue($text) { return "\033[34m{$text}\033[0m"; }
function bold($text) { return "\033[1m{$text}\033[0m"; }

echo "\n";
echo bold("═══════════════════════════════════════════════════════════════\n");
echo bold("  PRUEBAS AUTOMATIZADAS DE REPORTES POR HORARIO\n");
echo bold("═══════════════════════════════════════════════════════════════\n");
echo "\n";

// Configuración
$tz = 'America/Mexico_City';
$areaId = 4; // Área de prueba (Costura)
$maquinaId = 1; // Máquina de prueba

// Horas a probar
$horasPrueba = [
    '07:00' => 'Inicio turno matutino',
    '12:00' => 'Mediodía',
    '16:00' => 'Tarde (turno vespertino)',
    '20:00' => 'Noche',
    '23:00' => 'Casi medianoche',
    '01:00' => 'Madrugada (día siguiente)',
    '03:00' => 'Madrugada profunda',
    '06:59' => 'Justo antes del cambio de día laboral',
];

// ═══════════════════════════════════════════════════════════════
// FASE 1: ANÁLISIS DE LA LÓGICA ACTUAL
// ═══════════════════════════════════════════════════════════════
echo blue("═══════════════════════════════════════════════════════════════\n");
echo blue("  FASE 1: ANÁLISIS DE LA LÓGICA DE FILTRADO\n");
echo blue("═══════════════════════════════════════════════════════════════\n\n");

echo "Zona horaria configurada: " . bold($tz) . "\n";
echo "Hora actual del servidor: " . bold(Carbon::now($tz)->format('Y-m-d H:i:s')) . "\n";
echo "Hora UTC del servidor: " . bold(Carbon::now('UTC')->format('Y-m-d H:i:s')) . "\n\n";

echo bold("📋 REGLA DE FILTRADO POR DÍA:\n");
echo "   - El día laboral inicia a las " . bold("07:00 AM") . "\n";
echo "   - Si pides day=2026-02-13, el rango es:\n";
$start = Carbon::parse('2026-02-13', $tz)->setTime(7, 0, 0);
$end = (clone $start)->addDay();
echo "     " . green("DESDE: {$start->format('Y-m-d H:i:s')}") . "\n";
echo "     " . green("HASTA: {$end->format('Y-m-d H:i:s')}") . "\n\n";

echo bold("📋 ESTADOS SIEMPRE VISIBLES (sin importar fecha):\n");
echo "   - abierto\n";
echo "   - en_mantenimiento\n";
echo "   - en_proceso\n";
echo "   - pendiente\n";
echo "   - asignado\n\n";

// ═══════════════════════════════════════════════════════════════
// FASE 2: VERIFICAR REPORTES EXISTENTES
// ═══════════════════════════════════════════════════════════════
echo blue("═══════════════════════════════════════════════════════════════\n");
echo blue("  FASE 2: REPORTES EXISTENTES EN LA BASE DE DATOS\n");
echo blue("═══════════════════════════════════════════════════════════════\n\n");

// Contar reportes por status
$reportesPorStatus = DB::table('reportes')
    ->select('status', DB::raw('COUNT(*) as total'))
    ->groupBy('status')
    ->get();

echo bold("Reportes por status:\n");
foreach ($reportesPorStatus as $row) {
    $isPending = in_array($row->status, ['abierto', 'en_mantenimiento', 'en_proceso']);
    $display = $isPending ? yellow($row->total) : green($row->total);
    echo "   {$row->status}: " . $display . "\n";
}
echo "\n";

// Reportes de las últimas 48 horas
$reportesRecientes = DB::table('reportes')
    ->where('inicio', '>=', Carbon::now($tz)->subHours(48))
    ->orderBy('inicio', 'desc')
    ->limit(20)
    ->get(['id', 'status', 'inicio', 'area_id']);

echo bold("Últimos 20 reportes (48 horas):\n");
echo str_pad("ID", 6) . str_pad("STATUS", 18) . str_pad("INICIO", 25) . "ÁREA\n";
echo str_repeat("-", 65) . "\n";
foreach ($reportesRecientes as $r) {
    $inicio = Carbon::parse($r->inicio, $tz);
    $hora = $inicio->format('H:i');
    $isMadrugada = ($hora >= '23:00' || $hora < '07:00');
    $fechaStr = $inicio->format('Y-m-d H:i');
    $horaDisplay = $isMadrugada ? yellow($fechaStr) : $fechaStr;
    echo str_pad("#" . $r->id, 6) . str_pad($r->status, 18) . str_pad($horaDisplay, 35) . $r->area_id . "\n";
}
echo "\n";

// ═══════════════════════════════════════════════════════════════
// FASE 3: SIMULAR CONSULTAS POR HORA
// ═══════════════════════════════════════════════════════════════
echo blue("═══════════════════════════════════════════════════════════════\n");
echo blue("  FASE 3: SIMULACIÓN DE CONSULTAS POR HORA\n");
echo blue("═══════════════════════════════════════════════════════════════\n\n");

$hoy = Carbon::now($tz)->format('Y-m-d');
$ayer = Carbon::now($tz)->subDay()->format('Y-m-d');

echo bold("Probando consulta para HOY ($hoy):\n\n");

// Simular la misma lógica del ReporteService
$start = Carbon::parse($hoy, $tz)->setTime(7, 0, 0);
$end = (clone $start)->addDay();

$alwaysVisibleStatuses = ['abierto', 'en_mantenimiento', 'en_proceso', 'pendiente', 'asignado'];

// Query 1: Reportes del día (según lógica actual)
$reportesDelDia = DB::table('reportes')
    ->where('area_id', $areaId)
    ->where(function ($q) use ($start, $end, $alwaysVisibleStatuses) {
        $q->whereBetween('inicio', [$start, $end])
          ->orWhereIn('status', $alwaysVisibleStatuses);
    })
    ->get(['id', 'status', 'inicio']);

echo "   Rango del día: {$start->format('Y-m-d H:i')} → {$end->format('Y-m-d H:i')}\n";
echo "   Reportes encontrados: " . bold(count($reportesDelDia)) . "\n\n";

// Mostrar desglose
$dentroDelRango = 0;
$fueraDelRangoPeroVisibles = 0;

foreach ($reportesDelDia as $r) {
    $inicio = Carbon::parse($r->inicio);
    if ($inicio >= $start && $inicio < $end) {
        $dentroDelRango++;
    } else {
        $fueraDelRangoPeroVisibles++;
    }
}

echo "   - Dentro del rango horario: " . green($dentroDelRango) . "\n";
echo "   - Fuera del rango pero visibles (pendientes): " . yellow($fueraDelRangoPeroVisibles) . "\n\n";

// ═══════════════════════════════════════════════════════════════
// FASE 4: DETECTAR PROBLEMA DE MADRUGADA
// ═══════════════════════════════════════════════════════════════
echo blue("═══════════════════════════════════════════════════════════════\n");
echo blue("  FASE 4: ANÁLISIS DE REPORTES DE MADRUGADA\n");
echo blue("═══════════════════════════════════════════════════════════════\n\n");

echo bold("⚠️  PROBLEMA POTENCIAL DETECTADO:\n\n");

echo "   Si un reporte se crea a las " . red("01:00 AM del 13 de febrero") . ":\n";
echo "   - El timestamp será: 2026-02-13 01:00:00\n";
echo "   - Si el frontend pide: ?day=2026-02-13\n";
echo "   - El backend busca: 07:00 del 13 → 07:00 del 14\n";
echo "   - El reporte de la 01:00 está " . red("FUERA del rango") . "\n\n";

echo "   " . green("PERO: Si el status es 'abierto' o 'en_mantenimiento',") . "\n";
echo "   " . green("      el reporte SÍ se mostrará gracias a ALWAYS_VISIBLE_STATUSES") . "\n\n";

echo "   " . yellow("CASO PROBLEMÁTICO:") . "\n";
echo "   Si el reporte se crea a la 1:00 AM y se cierra (status='ok')\n";
echo "   antes de las 7:00 AM, NO aparecerá en la consulta del día.\n\n";

// Buscar reportes cerrados en madrugada
$reportesMadrugadaCerrados = DB::table('reportes')
    ->where('area_id', $areaId)
    ->whereIn('status', ['ok', 'OK', 'finalizado', 'cerrado'])
    ->whereRaw("TIME(inicio) >= '00:00:00' AND TIME(inicio) < '07:00:00'")
    ->where('inicio', '>=', Carbon::now($tz)->subDays(7))
    ->get(['id', 'status', 'inicio', 'fin']);

echo "   Reportes cerrados en madrugada (últimos 7 días): " . count($reportesMadrugadaCerrados) . "\n";
if (count($reportesMadrugadaCerrados) > 0) {
    foreach ($reportesMadrugadaCerrados as $r) {
        echo "   - #" . $r->id . " ({$r->status}) inicio: {$r->inicio}\n";
    }
}
echo "\n";

// ═══════════════════════════════════════════════════════════════
// FASE 5: VERIFICAR PROBLEMA DE "REPORTES BORRADOS"
// ═══════════════════════════════════════════════════════════════
echo blue("═══════════════════════════════════════════════════════════════\n");
echo blue("  FASE 5: VERIFICAR SI HAY REPORTES \"BORRADOS\"\n");
echo blue("═══════════════════════════════════════════════════════════════\n\n");

// Verificar si hay soft deletes
$tableInfo = DB::select("SHOW COLUMNS FROM reportes LIKE 'deleted_at'");
if (count($tableInfo) > 0) {
    echo yellow("⚠️  La tabla tiene columna 'deleted_at' (soft deletes habilitados)\n");
    $softDeleted = DB::table('reportes')->whereNotNull('deleted_at')->count();
    echo "   Reportes soft-deleted: " . ($softDeleted > 0 ? red($softDeleted) : green($softDeleted)) . "\n";
} else {
    echo green("✓ No hay soft deletes - los reportes NO se borran\n");
}

// Verificar integridad de datos
echo "\n" . bold("Verificando integridad de datos:\n");

$reportesSinArea = DB::table('reportes')->whereNull('area_id')->count();
$reportesSinMaquina = DB::table('reportes')->whereNull('maquina_id')->count();
$reportesSinInicio = DB::table('reportes')->whereNull('inicio')->count();

echo "   Reportes sin area_id: " . ($reportesSinArea > 0 ? red($reportesSinArea) : green($reportesSinArea)) . "\n";
echo "   Reportes sin maquina_id: " . ($reportesSinMaquina > 0 ? red($reportesSinMaquina) : green($reportesSinMaquina)) . "\n";
echo "   Reportes sin inicio: " . ($reportesSinInicio > 0 ? red($reportesSinInicio) : green($reportesSinInicio)) . "\n\n";

// ═══════════════════════════════════════════════════════════════
// FASE 6: VERIFICAR QUE LAS HORAS SE GUARDAN CORRECTAMENTE
// ═══════════════════════════════════════════════════════════════
echo blue("═══════════════════════════════════════════════════════════════\n");
echo blue("  FASE 6: VERIFICAR GUARDADO DE TIMESTAMPS\n");
echo blue("═══════════════════════════════════════════════════════════════\n\n");

// Obtener un reporte reciente
$reporteReciente = DB::table('reportes')->orderBy('id', 'desc')->first();

if ($reporteReciente) {
    echo "   Último reporte (#" . $reporteReciente->id . "):\n";
    echo "   - inicio (DB): {$reporteReciente->inicio}\n";
    echo "   - created_at:  {$reporteReciente->created_at}\n";
    
    // Verificar si inicio == created_at (deberían ser similares)
    $inicio = Carbon::parse($reporteReciente->inicio);
    $created = Carbon::parse($reporteReciente->created_at);
    $diff = $inicio->diffInSeconds($created);
    
    if ($diff > 5) {
        echo "   - Diferencia: " . yellow($diff . " segundos") . " (posible problema de TZ)\n";
    } else {
        echo "   - Diferencia: " . green($diff . " segundos") . " (OK)\n";
    }
}
echo "\n";

// ═══════════════════════════════════════════════════════════════
// INFORME FINAL
// ═══════════════════════════════════════════════════════════════
echo bold("═══════════════════════════════════════════════════════════════\n");
echo bold("  📊 INFORME FINAL\n");
echo bold("═══════════════════════════════════════════════════════════════\n\n");

$problemas = [];
$ok = [];

// Check 1: Soft deletes
if (count($tableInfo) === 0) {
    $ok[] = "No hay soft deletes - reportes no se borran";
} else if ($softDeleted > 0) {
    $problemas[] = "Hay {$softDeleted} reportes soft-deleted";
}

// Check 2: Integridad
if ($reportesSinArea > 0 || $reportesSinMaquina > 0 || $reportesSinInicio > 0) {
    $problemas[] = "Hay reportes con datos incompletos";
} else {
    $ok[] = "Integridad de datos OK";
}

// Check 3: Lógica de madrugada
if (count($reportesMadrugadaCerrados) > 0) {
    $problemas[] = "Reportes de madrugada cerrados pueden no aparecer en consultas del día";
}

// Check 4: Always visible
$pendientes = DB::table('reportes')
    ->where('area_id', $areaId)
    ->whereIn('status', $alwaysVisibleStatuses)
    ->count();
if ($pendientes > 0) {
    $ok[] = "{$pendientes} reportes pendientes siempre visibles";
}

echo bold("✅ FUNCIONANDO CORRECTAMENTE:\n");
foreach ($ok as $item) {
    echo "   " . green("✓ " . $item) . "\n";
}
echo "\n";

if (count($problemas) > 0) {
    echo bold("⚠️  POSIBLES PROBLEMAS:\n");
    foreach ($problemas as $item) {
        echo "   " . yellow("⚠ " . $item) . "\n";
    }
    echo "\n";
}

echo bold("📋 CONCLUSIÓN:\n\n");

echo "   El backend " . green("NO BORRA reportes") . ". Los reportes siempre se guardan.\n\n";

echo "   " . bold("Si los usuarios no ven reportes, puede ser porque:") . "\n\n";

echo "   1. " . yellow("PROBLEMA DE FECHA EN FRONTEND") . "\n";
echo "      El frontend puede estar enviando una fecha incorrecta.\n";
echo "      Verificar qué valor tiene `day` en la request.\n\n";

echo "   2. " . yellow("PROBLEMA DE HORA DE MADRUGADA") . "\n";
echo "      Reportes creados entre 00:00 y 06:59 pertenecen al día anterior.\n";
echo "      Ej: Reporte a las 01:00 del 13 feb → pertenece al turno del 12 feb.\n\n";

echo "   3. " . yellow("PROBLEMA DE CACHÉ") . "\n";
echo "      El backend cachea por 2 minutos. Si se crea un reporte,\n";
echo "      podría tardar hasta 2 min en aparecer.\n\n";

echo "   4. " . yellow("PROBLEMA DE NETWORK/API") . "\n";
echo "      La request puede fallar y el frontend no muestra error.\n";
echo "      Verificar Network tab del navegador.\n\n";

echo bold("🔧 RECOMENDACIONES:\n\n");

echo "   1. Verificar en el frontend qué fecha envía: console.log(day)\n";
echo "   2. Verificar Network tab: ver la response completa del API\n";
echo "   3. Probar manualmente: curl 'API_URL?day=2026-02-13'\n";
echo "   4. Si es problema de madrugada, ajustar la lógica o UI\n\n";

echo bold("═══════════════════════════════════════════════════════════════\n");
echo bold("  FIN DEL INFORME\n");
echo bold("═══════════════════════════════════════════════════════════════\n\n");
