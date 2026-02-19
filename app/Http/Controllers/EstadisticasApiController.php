<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Linea;
use App\Models\Maquina;
use App\Models\Reporte;
use App\Models\herramental;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * API de Estadísticas para Dashboard Centralizado
 * 
 * Endpoints diseñados para ser consumidos por una app externa
 * que recopila estadísticas de múltiples aplicaciones.
 * 
 * Todos los endpoints devuelven JSON con estructura consistente:
 * { app: "mantenimiento", timestamp, periodo, data }
 */
class EstadisticasApiController extends Controller
{
    private string $tz = 'America/Mexico_City';
    private string $appName = 'mantenimiento-tiempos';

    // ─────────────────────────────────────────────────────────
    // 1. GET /api/estadisticas/resumen
    //    Resumen general: KPIs principales en una sola llamada
    // ─────────────────────────────────────────────────────────
    public function resumen(Request $request): JsonResponse
    {
        [$desde, $hasta] = $this->parsePeriodo($request);
        $reportes = $this->queryReportes($desde, $hasta, $request);

        $total = $reportes->count();
        $abiertos = $reportes->where('status', 'abierto')->count();
        $enMantenimiento = $reportes->where('status', 'en_mantenimiento')->count();
        $finalizados = $reportes->where('status', 'OK')->count();

        $secToHours = fn($s) => $s === null ? 0 : round($s / 3600, 2);

        // MTTR
        $mttrValues = $reportes->filter(fn($r) => $r->aceptado_en && $r->fin)
            ->map(fn($r) => $r->tiempo_mantenimiento_segundos ?? 0)
            ->filter(fn($s) => $s > 0);
        $mttrAvgSec = $mttrValues->isNotEmpty() ? $mttrValues->avg() : 0;

        // MTBF
        $mtbfAvgSec = $this->calcularMTBFGlobal($reportes);

        // Downtime total
        $totalDowntimeSec = $reportes->sum(fn($r) => $r->tiempo_total_segundos ?? 0);

        // Tiempo de reacción promedio
        $reaccionValues = $reportes->filter(fn($r) => $r->aceptado_en)
            ->map(fn($r) => $r->tiempo_reaccion_segundos ?? 0)
            ->filter(fn($s) => $s > 0);
        $reaccionAvgSec = $reaccionValues->isNotEmpty() ? $reaccionValues->avg() : 0;

        return $this->apiResponse($desde, $hasta, [
            'kpis' => [
                'total_reportes'           => $total,
                'abiertos'                 => $abiertos,
                'en_mantenimiento'         => $enMantenimiento,
                'finalizados'              => $finalizados,
                'mttr_horas'               => $secToHours($mttrAvgSec),
                'mttr_minutos'             => round($mttrAvgSec / 60, 2),
                'mtbf_horas'               => $secToHours($mtbfAvgSec),
                'downtime_total_horas'     => $secToHours($totalDowntimeSec),
                'reaccion_promedio_minutos' => round($reaccionAvgSec / 60, 2),
            ],
            'distribucion_status' => [
                ['status' => 'abierto', 'count' => $abiertos, 'porcentaje' => $total > 0 ? round($abiertos / $total * 100, 1) : 0],
                ['status' => 'en_mantenimiento', 'count' => $enMantenimiento, 'porcentaje' => $total > 0 ? round($enMantenimiento / $total * 100, 1) : 0],
                ['status' => 'OK', 'count' => $finalizados, 'porcentaje' => $total > 0 ? round($finalizados / $total * 100, 1) : 0],
            ],
            'distribucion_turno' => $this->distribucionTurno($reportes),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // 2. GET /api/estadisticas/graficas
    //    Datos formateados para gráficas (charts)
    // ─────────────────────────────────────────────────────────
    public function graficas(Request $request): JsonResponse
    {
        [$desde, $hasta] = $this->parsePeriodo($request);
        $reportes = $this->queryReportes($desde, $hasta, $request);

        $secToHours = fn($s) => $s === null ? 0 : round($s / 3600, 2);

        return $this->apiResponse($desde, $hasta, [
            'top_lineas'         => $this->topLineas($reportes, $secToHours),
            'top_maquinas'       => $this->topMaquinas($reportes, $secToHours),
            'top_departamentos'  => $this->topDepartamentos($reportes),
            'por_turno'          => $this->porTurno($reportes, $secToHours),
            'mttr_por_maquina'   => $this->mttrPorMaquina($reportes, $secToHours),
            'mtbf_por_maquina'   => $this->mtbfPorMaquina($reportes, $secToHours),
            'serie_diaria'       => $this->serieDiaria($reportes, $desde, $hasta, $secToHours),
            'reportes_por_dia'   => $this->reportesPorDia($reportes),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // 3. GET /api/estadisticas/tendencias
    //    Tendencias semanales/mensuales para comparativa
    // ─────────────────────────────────────────────────────────
    public function tendencias(Request $request): JsonResponse
    {
        $agrupacion = $request->input('agrupacion', 'semanal'); // semanal | mensual
        $meses = (int) $request->input('meses', 6);
        $desde = Carbon::now($this->tz)->subMonths($meses)->startOfDay();
        $hasta = Carbon::now($this->tz)->endOfDay();

        $reportes = $this->queryReportes($desde, $hasta, $request);

        $secToHours = fn($s) => $s === null ? 0 : round($s / 3600, 2);

        if ($agrupacion === 'mensual') {
            $grouped = $reportes->groupBy(fn($r) => $r->inicio ? $r->inicio->format('Y-m') : 'unknown');
        } else {
            $grouped = $reportes->groupBy(function ($r) {
                if (!$r->inicio) return 'unknown';
                $y = $r->inicio->isoWeekYear();
                $w = str_pad($r->inicio->isoWeek(), 2, '0', STR_PAD_LEFT);
                return "{$y}-W{$w}";
            });
        }

        $tendencia = $grouped->filter(fn($_, $k) => $k !== 'unknown')
            ->map(function ($rows, $periodo) use ($secToHours) {
                $mttrVals = $rows->filter(fn($r) => $r->aceptado_en && $r->fin)
                    ->map(fn($r) => $r->tiempo_mantenimiento_segundos ?? 0)
                    ->filter(fn($s) => $s > 0);
                $downtime = $rows->sum(fn($r) => $r->tiempo_total_segundos ?? 0);

                return [
                    'periodo'           => $periodo,
                    'total_reportes'    => $rows->count(),
                    'mttr_horas'        => $secToHours($mttrVals->isNotEmpty() ? $mttrVals->avg() : 0),
                    'downtime_horas'    => $secToHours($downtime),
                    'finalizados'       => $rows->where('status', 'OK')->count(),
                    'abiertos'          => $rows->where('status', 'abierto')->count(),
                ];
            })
            ->sortKeys()
            ->values();

        return $this->apiResponse($desde, $hasta, [
            'agrupacion' => $agrupacion,
            'tendencia'  => $tendencia,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // 4. GET /api/estadisticas/tiempo-real
    //    Estado actual: reportes abiertos ahora mismo
    // ─────────────────────────────────────────────────────────
    public function tiempoReal(): JsonResponse
    {
        $ahora = Carbon::now($this->tz);

        $abiertos = Reporte::with(['maquina:id,name,linea_id', 'maquina.linea:id,name,area_id', 'maquina.linea.area:id,name'])
            ->whereIn('status', ['abierto', 'en_mantenimiento'])
            ->orderBy('inicio', 'asc')
            ->get()
            ->map(function ($r) use ($ahora) {
                $tiempoEsperaSec = $r->inicio ? abs($r->inicio->diffInSeconds($ahora)) : 0;
                return [
                    'id'                   => $r->id,
                    'status'               => $r->status,
                    'falla'                 => $r->falla,
                    'departamento'         => $r->departamento,
                    'turno'                => $r->turno,
                    'maquina'              => optional($r->maquina)->name,
                    'linea'                => optional(optional($r->maquina)->linea)->name,
                    'area'                 => optional(optional(optional($r->maquina)->linea)->area)->name,
                    'inicio'               => $r->inicio?->toIso8601String(),
                    'aceptado_en'          => $r->aceptado_en?->toIso8601String(),
                    'tiempo_transcurrido_minutos' => round($tiempoEsperaSec / 60, 1),
                    'lider'                => $r->lider_nombre,
                    'tecnico'              => $r->tecnico_nombre,
                ];
            });

        $resumen = [
            'total_activos'      => $abiertos->count(),
            'abiertos'           => $abiertos->where('status', 'abierto')->count(),
            'en_mantenimiento'   => $abiertos->where('status', 'en_mantenimiento')->count(),
        ];

        return response()->json([
            'app'       => $this->appName,
            'timestamp' => $ahora->toIso8601String(),
            'data'      => [
                'resumen'  => $resumen,
                'reportes' => $abiertos->values(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // 5. GET /api/estadisticas/areas
    //    Estadísticas desglosadas por área
    // ─────────────────────────────────────────────────────────
    public function porArea(Request $request): JsonResponse
    {
        [$desde, $hasta] = $this->parsePeriodo($request);
        $reportes = $this->queryReportes($desde, $hasta, $request);

        $secToHours = fn($s) => $s === null ? 0 : round($s / 3600, 2);

        $porArea = $reportes->groupBy(fn($r) => optional(optional(optional($r->maquina)->linea)->area)->name ?? 'Sin área')
            ->map(function ($rows, $areaName) use ($secToHours) {
                $totalDown = $rows->sum(fn($r) => $r->tiempo_total_segundos ?? 0);
                $mttrVals = $rows->filter(fn($r) => $r->aceptado_en && $r->fin)
                    ->map(fn($r) => $r->tiempo_mantenimiento_segundos ?? 0)
                    ->filter(fn($s) => $s > 0);

                return [
                    'area'              => $areaName,
                    'total_reportes'    => $rows->count(),
                    'abiertos'          => $rows->where('status', 'abierto')->count(),
                    'en_mantenimiento'  => $rows->where('status', 'en_mantenimiento')->count(),
                    'finalizados'       => $rows->where('status', 'OK')->count(),
                    'downtime_horas'    => $secToHours($totalDown),
                    'mttr_horas'        => $secToHours($mttrVals->isNotEmpty() ? $mttrVals->avg() : 0),
                ];
            })
            ->sortByDesc('total_reportes')
            ->values();

        return $this->apiResponse($desde, $hasta, [
            'por_area' => $porArea,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // 6. GET /api/estadisticas/herramentales
    //    Estadísticas de herramentales
    // ─────────────────────────────────────────────────────────
    public function herramentales(Request $request): JsonResponse
    {
        [$desde, $hasta] = $this->parsePeriodo($request);

        $reportes = Reporte::whereNotNull('herramental_id')
            ->whereBetween('inicio', [$desde, $hasta])
            ->with(['herramental', 'maquina.linea.area'])
            ->get();

        $totalFallas = $reportes->count();

        // MTTR herramentales
        $tiemposRep = $reportes->filter(fn($r) => $r->inicio && $r->fin)
            ->map(fn($r) => abs($r->fin->diffInMinutes($r->inicio)));
        $mttrMin = $tiemposRep->isNotEmpty() ? $tiemposRep->avg() : 0;

        // Downtime
        $downtime = $tiemposRep->sum();

        // Top herramentales con más fallos
        $topHerr = $reportes->groupBy('herramental_id')
            ->map(function ($grupo) {
                $h = $grupo->first()->herramental;
                $tiempos = $grupo->filter(fn($r) => $r->inicio && $r->fin)
                    ->map(fn($r) => abs($r->fin->diffInMinutes($r->inicio)));
                return [
                    'herramental'              => $h?->name ?? 'Desconocido',
                    'total_fallos'             => $grupo->count(),
                    'downtime_total_minutos'   => round($tiempos->sum(), 2),
                    'downtime_promedio_minutos' => round($tiempos->isNotEmpty() ? $tiempos->avg() : 0, 2),
                ];
            })
            ->sortByDesc('total_fallos')
            ->take(10)
            ->values();

        // Por máquina
        $porMaquina = $reportes->groupBy('maquina_id')
            ->map(function ($grupo) {
                $m = $grupo->first()->maquina;
                $tiempos = $grupo->filter(fn($r) => $r->inicio && $r->fin)
                    ->map(fn($r) => abs($r->fin->diffInMinutes($r->inicio)));
                return [
                    'maquina'            => $m?->name ?? 'Desconocida',
                    'linea'              => optional($m?->linea)->name,
                    'area'               => optional(optional($m?->linea)->area)->name,
                    'total_fallas'       => $grupo->count(),
                    'downtime_minutos'   => round($tiempos->sum(), 2),
                ];
            })
            ->sortByDesc('total_fallas')
            ->values();

        return $this->apiResponse($desde, $hasta, [
            'resumen' => [
                'total_fallas'              => $totalFallas,
                'mttr_minutos'              => round($mttrMin, 2),
                'downtime_total_minutos'    => round($downtime, 2),
                'downtime_total_horas'      => round($downtime / 60, 2),
            ],
            'top_herramentales' => $topHerr,
            'por_maquina'       => $porMaquina,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // 7. GET /api/estadisticas/tecnicos
    //    Rendimiento por técnico
    // ─────────────────────────────────────────────────────────
    public function tecnicos(Request $request): JsonResponse
    {
        [$desde, $hasta] = $this->parsePeriodo($request);
        $reportes = $this->queryReportes($desde, $hasta, $request);

        $secToHours = fn($s) => $s === null ? 0 : round($s / 3600, 2);

        $porTecnico = $reportes->filter(fn($r) => $r->tecnico_nombre)
            ->groupBy('tecnico_nombre')
            ->map(function ($rows, $nombre) use ($secToHours) {
                $finalizados = $rows->where('status', 'OK');
                $mttrVals = $finalizados->filter(fn($r) => $r->aceptado_en && $r->fin)
                    ->map(fn($r) => $r->tiempo_mantenimiento_segundos ?? 0)
                    ->filter(fn($s) => $s > 0);

                return [
                    'tecnico'              => $nombre,
                    'total_asignados'      => $rows->count(),
                    'finalizados'          => $finalizados->count(),
                    'en_proceso'           => $rows->where('status', 'en_mantenimiento')->count(),
                    'mttr_promedio_horas'   => $secToHours($mttrVals->isNotEmpty() ? $mttrVals->avg() : 0),
                    'mttr_promedio_minutos' => round(($mttrVals->isNotEmpty() ? $mttrVals->avg() : 0) / 60, 2),
                ];
            })
            ->sortByDesc('total_asignados')
            ->values();

        return $this->apiResponse($desde, $hasta, [
            'por_tecnico' => $porTecnico,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // 8. GET /api/estadisticas/catalogos
    //    Catálogos: áreas, líneas, máquinas, turnos,
    //    departamentos — para filtros en el frontend
    // ─────────────────────────────────────────────────────────
    public function catalogos(): JsonResponse
    {
        $areas = Area::orderBy('name')->get(['id', 'name']);
        $lineas = Linea::with('area:id,name')->orderBy('name')->get(['id', 'name', 'area_id']);
        $maquinas = Maquina::with('linea:id,name,area_id')->orderBy('name')->get(['id', 'name', 'linea_id']);

        $departamentos = Reporte::whereNotNull('departamento')
            ->where('departamento', '!=', '')
            ->distinct('departamento')
            ->orderBy('departamento')
            ->pluck('departamento')
            ->values();

        $turnos = ['1', '2', '3']; // A, B, C

        return response()->json([
            'app'  => $this->appName,
            'data' => [
                'areas'          => $areas,
                'lineas'         => $lineas,
                'maquinas'       => $maquinas,
                'departamentos'  => $departamentos,
                'turnos'         => $turnos,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // 9. GET /api/estadisticas/health
    //    Health check para el dashboard centralizado
    // ─────────────────────────────────────────────────────────
    public function health(): JsonResponse
    {
        $ahora = Carbon::now($this->tz);

        // Contar registros recientes (últimas 24h) para confirmar que hay actividad
        $reportes24h = Reporte::where('inicio', '>=', $ahora->copy()->subDay())->count();

        return response()->json([
            'app'       => $this->appName,
            'status'    => 'ok',
            'timestamp' => $ahora->toIso8601String(),
            'version'   => '1.0.0',
            'database'  => 'connected',
            'actividad' => [
                'reportes_ultimas_24h' => $reportes24h,
                'total_reportes'       => Reporte::count(),
                'total_maquinas'       => Maquina::count(),
                'total_areas'          => Area::count(),
            ],
        ]);
    }

    // ═════════════════════════════════════════════════════════
    //  HELPERS PRIVADOS
    // ═════════════════════════════════════════════════════════

    /**
     * Parsea el periodo desde/hasta de los query params.
     * Soporta: desde/hasta, day, week, month
     * Default: último mes
     */
    private function parsePeriodo(Request $request): array
    {
        if ($request->filled('day')) {
            $desde = Carbon::parse((string) $request->input('day'), $this->tz)->setTime(7, 0, 0);
            $hasta = (clone $desde)->addDay();
        } elseif ($request->filled('week') && preg_match('/^(\d{4})-W(\d{2})$/', (string) $request->input('week'), $m)) {
            $desde = Carbon::now($this->tz)->setISODate((int) $m[1], (int) $m[2], 1)->setTime(7, 0, 0);
            $hasta = (clone $desde)->addDays(7);
        } elseif ($request->filled('month')) {
            $desde = Carbon::parse($request->input('month') . '-01', $this->tz)->setTime(7, 0, 0);
            $hasta = (clone $desde)->addMonth();
        } elseif ($request->filled('desde')) {
            $desde = Carbon::parse((string) $request->input('desde'), $this->tz)->startOfDay();
            $hasta = $request->filled('hasta')
                ? Carbon::parse((string) $request->input('hasta'), $this->tz)->endOfDay()
                : Carbon::now($this->tz)->endOfDay();
        } else {
            // Default: último mes
            $desde = Carbon::now($this->tz)->subMonth()->startOfDay();
            $hasta = Carbon::now($this->tz)->endOfDay();
        }

        return [$desde, $hasta];
    }

    /**
     * Query base de reportes con filtros opcionales
     */
    private function queryReportes(Carbon $desde, Carbon $hasta, ?Request $request = null)
    {
        $q = Reporte::with(['maquina:id,name,linea_id', 'maquina.linea:id,name,area_id', 'maquina.linea.area:id,name'])
            ->whereBetween('inicio', [$desde, $hasta]);

        if ($request) {
            if ($request->filled('area_id')) {
                $q->whereIn('area_id', explode(',', (string) $request->input('area_id')));
            }
            if ($request->filled('linea_id')) {
                $lineas = explode(',', (string) $request->input('linea_id'));
                $q->whereHas('maquina', fn($mq) => $mq->whereIn('linea_id', $lineas));
            }
            if ($request->filled('turno')) {
                $q->whereIn('turno', explode(',', (string) $request->input('turno')));
            }
            if ($request->filled('departamento')) {
                $depts = is_array($request->input('departamento'))
                    ? $request->input('departamento')
                    : explode(',', (string) $request->input('departamento'));
                $q->whereIn('departamento', $depts);
            }
            if ($request->filled('status')) {
                $q->whereIn('status', explode(',', (string) $request->input('status')));
            }
        }

        return $q->get();
    }

    /**
     * MTBF global promedio
     */
    private function calcularMTBFGlobal($reportes): float
    {
        $byMachine = $reportes->groupBy(fn($r) => optional($r->maquina)->id);
        $mtbfPerMachine = [];

        foreach ($byMachine as $rows) {
            $rows = $rows->sortBy('inicio')->values();
            $gaps = [];
            for ($i = 0; $i < $rows->count() - 1; $i++) {
                if ($rows[$i]->fin && $rows[$i + 1]->inicio) {
                    $gaps[] = $rows[$i]->fin->diffInSeconds($rows[$i + 1]->inicio);
                }
            }
            if (!empty($gaps)) {
                $mtbfPerMachine[] = array_sum($gaps) / count($gaps);
            }
        }

        return !empty($mtbfPerMachine) ? array_sum($mtbfPerMachine) / count($mtbfPerMachine) : 0;
    }

    /**
     * Distribución por turno
     */
    private function distribucionTurno($reportes): array
    {
        $turnoLabel = fn($t) => match ((string) $t) {
            '1' => 'A', '2' => 'B', '3' => 'C', default => ($t ?: 'N/A')
        };

        return $reportes->groupBy(fn($r) => $turnoLabel($r->turno))
            ->map(fn($rows, $label) => [
                'turno'   => $label,
                'count'   => $rows->count(),
            ])
            ->sortBy('turno')
            ->values()
            ->toArray();
    }

    /**
     * Top 10 líneas por downtime
     */
    private function topLineas($reportes, $secToHours): array
    {
        return $reportes->groupBy(fn($r) => optional(optional($r->maquina)->linea)->name)
            ->map(fn($rows, $name) => [
                'nombre'          => $name ?: 'Sin línea',
                'total_reportes'  => $rows->count(),
                'downtime_horas'  => $secToHours($rows->sum(fn($r) => $r->tiempo_total_segundos ?? 0)),
            ])
            ->sortByDesc('downtime_horas')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * Top 10 máquinas por downtime
     */
    private function topMaquinas($reportes, $secToHours): array
    {
        return $reportes->groupBy(fn($r) => optional($r->maquina)->name)
            ->map(fn($rows, $name) => [
                'nombre'          => $name ?: 'Sin máquina',
                'total_reportes'  => $rows->count(),
                'downtime_horas'  => $secToHours($rows->sum(fn($r) => $r->tiempo_total_segundos ?? 0)),
            ])
            ->sortByDesc('downtime_horas')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * Top 10 departamentos por cantidad de fallas
     */
    private function topDepartamentos($reportes): array
    {
        return $reportes->groupBy(fn($r) => $r->departamento)
            ->map(fn($rows, $dept) => [
                'nombre' => $dept ?: 'Sin departamento',
                'count'  => $rows->count(),
            ])
            ->sortByDesc('count')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * Downtime por turno
     */
    private function porTurno($reportes, $secToHours): array
    {
        $turnoLabel = fn($t) => match ((string) $t) {
            '1' => 'A', '2' => 'B', '3' => 'C', default => ($t ?: 'N/A')
        };

        return $reportes->groupBy(fn($r) => $turnoLabel($r->turno))
            ->map(fn($rows, $label) => [
                'turno'           => $label,
                'total_reportes'  => $rows->count(),
                'downtime_horas'  => $secToHours($rows->sum(fn($r) => $r->tiempo_total_segundos ?? 0)),
            ])
            ->sortBy('turno')
            ->values()
            ->toArray();
    }

    /**
     * MTTR por máquina (top 10)
     */
    private function mttrPorMaquina($reportes, $secToHours): array
    {
        return $reportes->filter(fn($r) => $r->aceptado_en && $r->fin)
            ->groupBy(fn($r) => optional($r->maquina)->name)
            ->map(function ($rows, $name) use ($secToHours) {
                $vals = $rows->map(fn($r) => $r->tiempo_mantenimiento_segundos ?? 0)->filter(fn($s) => $s > 0);
                return [
                    'nombre'       => $name ?: 'Sin máquina',
                    'mttr_horas'   => $secToHours($vals->isNotEmpty() ? $vals->avg() : 0),
                    'mttr_minutos' => round(($vals->isNotEmpty() ? $vals->avg() : 0) / 60, 2),
                    'total'        => $rows->count(),
                ];
            })
            ->sortByDesc('mttr_horas')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * MTBF por máquina (top 10)
     */
    private function mtbfPorMaquina($reportes, $secToHours): array
    {
        $byMachine = $reportes->groupBy(fn($r) => optional($r->maquina)->id);
        $result = [];

        foreach ($byMachine as $machineId => $rows) {
            $rows = $rows->sortBy('inicio')->values();
            $gaps = [];
            for ($i = 0; $i < $rows->count() - 1; $i++) {
                if ($rows[$i]->fin && $rows[$i + 1]->inicio) {
                    $gaps[] = $rows[$i]->fin->diffInSeconds($rows[$i + 1]->inicio);
                }
            }
            if (!empty($gaps)) {
                $avg = array_sum($gaps) / count($gaps);
                $name = optional($rows->first()->maquina)->name ?: ('ID ' . $machineId);
                $result[] = [
                    'nombre'     => $name,
                    'mtbf_horas' => $secToHours($avg),
                ];
            }
        }

        return collect($result)->sortByDesc('mtbf_horas')->take(10)->values()->toArray();
    }

    /**
     * Serie diaria MTTR/MTBF
     */
    private function serieDiaria($reportes, Carbon $desde, Carbon $hasta, $secToHours): array
    {
        $cursor = $desde->copy()->setTime(7, 0, 0);
        $end = $hasta->copy()->setTime(7, 0, 0)->addDay();

        $series = [];

        while ($cursor < $end) {
            $dayStart = $cursor->copy();
            $dayEnd = $cursor->copy()->addDay();
            $dayRows = $reportes->filter(fn($r) => $r->inicio && $r->inicio >= $dayStart && $r->inicio < $dayEnd);

            $mttrVals = $dayRows->filter(fn($r) => $r->aceptado_en && $r->fin)
                ->map(fn($r) => $r->tiempo_mantenimiento_segundos ?? 0)
                ->filter(fn($s) => $s > 0);

            $series[] = [
                'fecha'           => $dayStart->format('Y-m-d'),
                'total_reportes'  => $dayRows->count(),
                'mttr_horas'      => $secToHours($mttrVals->isNotEmpty() ? $mttrVals->avg() : 0),
                'downtime_horas'  => $secToHours($dayRows->sum(fn($r) => $r->tiempo_total_segundos ?? 0)),
            ];

            $cursor->addDay();
        }

        return $series;
    }

    /**
     * Reportes abiertos/en proceso por día
     */
    private function reportesPorDia($reportes): array
    {
        return $reportes->groupBy(fn($r) => $r->inicio ? $r->inicio->format('Y-m-d') : 'unknown')
            ->filter(fn($_, $k) => $k !== 'unknown')
            ->map(fn($rows, $day) => [
                'fecha'            => $day,
                'total'            => $rows->count(),
                'abiertos'         => $rows->where('status', 'abierto')->count(),
                'en_mantenimiento' => $rows->where('status', 'en_mantenimiento')->count(),
                'finalizados'      => $rows->where('status', 'OK')->count(),
            ])
            ->sortBy('fecha')
            ->values()
            ->toArray();
    }

    /**
     * Wrapper de respuesta estándar
     */
    private function apiResponse(Carbon $desde, Carbon $hasta, array $data): JsonResponse
    {
        return response()->json([
            'app'       => $this->appName,
            'timestamp' => Carbon::now($this->tz)->toIso8601String(),
            'periodo'   => [
                'desde' => $desde->format('Y-m-d'),
                'hasta' => $hasta->format('Y-m-d'),
            ],
            'data' => $data,
        ]);
    }
}
