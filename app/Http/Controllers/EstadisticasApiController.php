<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Linea;
use App\Models\Maquina;
use App\Models\Reporte;
use App\Models\Herramental;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
class EstadisticasApiController extends Controller
{
    private string $tz = 'America/Mexico_City';
    private string $appName = 'mantenimiento-tiempos';

    // Obtiene un resumen de los KPIs principales
    public function resumen(Request $request): JsonResponse
    {
        [$desde, $hasta] = $this->parsePeriodo($request);
        $reportes = $this->queryReportes($desde, $hasta, $request);

        $total = $reportes->count();
        $abiertos = $reportes->where('status', 'abierto')->count();
        $enMantenimiento = $reportes->where('status', 'en_mantenimiento')->count();
        $finalizados = $reportes->where('status', 'OK')->count();

        $secToHours = fn($s) => $s === null ? 0 : round($s / 3600, 2);

        // Downtime total (mismo valor que muestra la UI en "Tiempo Total")
        $totalDowntimeSec = $reportes->sum(fn($r) => $r->tiempo_total_segundos ?? 0);

        // MTTR = Tiempo Total / Total Fallas  (igual a como el usuario lo calcula a mano)
        $mttrAvgSec = $reportes->count() > 0 ? $totalDowntimeSec / $reportes->count() : 0;

        // MTBF
        $mtbfAvgSec = $this->calcularMTBFGlobal($reportes);

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

    // Obtiene el MTTR global del periodo filtrado
    public function mttr(Request $request): JsonResponse
    {
        [$desde, $hasta] = $this->parsePeriodo($request);
        $reportes = $this->queryReportes($desde, $hasta, $request);

        $totalReportes = $reportes->count();
        $downtimeTotalSec = $reportes->sum(fn($r) => $r->tiempo_total_segundos ?? 0);
        $mttrAvgSec = $totalReportes > 0 ? $downtimeTotalSec / $totalReportes : 0;

        return $this->apiResponse($desde, $hasta, [
            'mttr' => [
                'segundos' => round($mttrAvgSec, 2),
                'minutos'  => round($mttrAvgSec / 60, 2),
                'horas'    => round($mttrAvgSec / 3600, 2),
            ],
            'contexto' => [
                'total_reportes'          => $totalReportes,
                'downtime_total_segundos' => round($downtimeTotalSec, 2),
                'downtime_total_horas'    => round($downtimeTotalSec / 3600, 2),
            ],
        ]);
    }

    // Obtiene el MTBF global del periodo filtrado
    public function mtbf(Request $request): JsonResponse
    {
        [$desde, $hasta] = $this->parsePeriodo($request);
        $reportes = $this->queryReportes($desde, $hasta, $request);

        $mtbfAvgSec = $this->calcularMTBFGlobal($reportes);

        return $this->apiResponse($desde, $hasta, [
            'mtbf' => [
                'segundos' => round($mtbfAvgSec, 2),
                'minutos'  => round($mtbfAvgSec / 60, 2),
                'horas'    => round($mtbfAvgSec / 3600, 2),
            ],
            'contexto' => [
                'total_reportes' => $reportes->count(),
            ],
        ]);
    }

    // Obtiene el tiempo total de paro del periodo filtrado
    public function tiempoTotal(Request $request): JsonResponse
    {
        [$desde, $hasta] = $this->parsePeriodo($request);
        $reportes = $this->queryReportes($desde, $hasta, $request);

        $totalReportes = $reportes->count();
        $tiempoTotalSec = $reportes->sum(fn($r) => $r->tiempo_total_segundos ?? 0);
        $promedioPorReporteSec = $totalReportes > 0 ? $tiempoTotalSec / $totalReportes : 0;

        return $this->apiResponse($desde, $hasta, [
            'tiempo_total' => [
                'segundos' => round($tiempoTotalSec, 2),
                'minutos'  => round($tiempoTotalSec / 60, 2),
                'horas'    => round($tiempoTotalSec / 3600, 2),
            ],
            'contexto' => [
                'total_reportes'               => $totalReportes,
                'promedio_por_reporte_minutos' => round($promedioPorReporteSec / 60, 2),
                'promedio_por_reporte_horas'   => round($promedioPorReporteSec / 3600, 2),
            ],
        ]);
    }

    // Obtiene el conteo de reportes abiertos y en mantenimiento
    public function reportesAbiertos(Request $request): JsonResponse
    {
        [$desde, $hasta] = $this->parsePeriodo($request);
        $reportes = $this->queryReportes($desde, $hasta, $request);

        $abiertos = $reportes->where('status', 'abierto')->count();
        $enMantenimiento = $reportes->where('status', 'en_mantenimiento')->count();
        $totalActivos = $abiertos + $enMantenimiento;
        $totalReportes = $reportes->count();

        return $this->apiResponse($desde, $hasta, [
            'reportes_abiertos' => [
                'total_activos'     => $totalActivos,
                'abiertos'          => $abiertos,
                'en_mantenimiento'  => $enMantenimiento,
            ],
            'contexto' => [
                'total_reportes_filtrados' => $totalReportes,
                'porcentaje_activos'       => $totalReportes > 0
                    ? round(($totalActivos / $totalReportes) * 100, 2)
                    : 0,
            ],
        ]);
    }

    // Obtiene los datos formateados para las graficas
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

    // Obtiene tendencias semanales o mensuales para comparativas
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
                $downtime = $rows->sum(fn($r) => $r->tiempo_total_segundos ?? 0);
                $mttrSec  = $rows->count() > 0 ? $downtime / $rows->count() : 0;

                return [
                    'periodo'           => $periodo,
                    'total_reportes'    => $rows->count(),
                    'mttr_horas'        => $secToHours($mttrSec),
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

    // Obtiene los reportes activos en tiempo real
    public function tiempoReal(Request $request): JsonResponse
    {
        $ahora = Carbon::now($this->tz);

        $q = Reporte::with(['maquina:id,name,linea_id', 'maquina.linea:id,name,area_id', 'maquina.linea.area:id,name'])
            ->whereIn('status', ['abierto', 'en_mantenimiento'])
            ->orderBy('inicio', 'asc');

        // Filtrar por área si viene el parámetro
        if ($request->filled('area_id')) {
            $q->whereHas('maquina.linea.area', fn($aq) => $aq->whereIn('id', explode(',', (string) $request->input('area_id'))));
        }

        $abiertos = $q->get()
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

    // Obtiene estadisticas desglosadas por area
    public function porArea(Request $request): JsonResponse
    {
        [$desde, $hasta] = $this->parsePeriodo($request);
        $reportes = $this->queryReportes($desde, $hasta, $request);

        $secToHours = fn($s) => $s === null ? 0 : round($s / 3600, 2);

        $porArea = $reportes->groupBy(fn($r) => optional(optional(optional($r->maquina)->linea)->area)->name ?? 'Sin área')
            ->map(function ($rows, $areaName) use ($secToHours) {
                $totalDown = $rows->sum(fn($r) => $r->tiempo_total_segundos ?? 0);
                $mttrSec   = $rows->count() > 0 ? $totalDown / $rows->count() : 0;

                return [
                    'area'              => $areaName,
                    'total_reportes'    => $rows->count(),
                    'abiertos'          => $rows->where('status', 'abierto')->count(),
                    'en_mantenimiento'  => $rows->where('status', 'en_mantenimiento')->count(),
                    'finalizados'       => $rows->where('status', 'OK')->count(),
                    'downtime_horas'    => $secToHours($totalDown),
                    'mttr_horas'        => $secToHours($mttrSec),
                ];
            })
            ->sortByDesc('total_reportes')
            ->values();

        return $this->apiResponse($desde, $hasta, [
            'por_area' => $porArea,
        ]);
    }

    // Obtiene estadisticas desglosadas de herramentales
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

    // Obtiene estadisticas de rendimiento por tecnico
    public function tecnicos(Request $request): JsonResponse
    {
        [$desde, $hasta] = $this->parsePeriodo($request);
        $reportes = $this->queryReportes($desde, $hasta, $request);

        $secToHours = fn($s) => $s === null ? 0 : round($s / 3600, 2);

        $porTecnico = $reportes->filter(fn($r) => $r->tecnico_nombre)
            ->groupBy('tecnico_nombre')
            ->map(function ($rows, $nombre) use ($secToHours) {
                $finalizados  = $rows->where('status', 'OK');
                $totalDownSec = $rows->sum(fn($r) => $r->tiempo_total_segundos ?? 0);
                $mttrSec      = $rows->count() > 0 ? $totalDownSec / $rows->count() : 0;

                return [
                    'tecnico'              => $nombre,
                    'total_asignados'      => $rows->count(),
                    'finalizados'          => $finalizados->count(),
                    'en_proceso'           => $rows->where('status', 'en_mantenimiento')->count(),
                    'mttr_promedio_horas'   => $secToHours($mttrSec),
                    'mttr_promedio_minutos' => round($mttrSec / 60, 2),
                    'downtime_total_horas'  => $secToHours($totalDownSec),
                ];
            })
            ->sortByDesc('total_asignados')
            ->values();

        return $this->apiResponse($desde, $hasta, [
            'por_tecnico' => $porTecnico,
        ]);
    }

    // Obtiene los catalogos para filtros del frontend
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

    // Endpoint de verificacion de estado de la API
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

    // Helpers privados

    // Convierte los query params de fecha en un rango Carbon [desde, hasta]
    private function parsePeriodo(Request $request): array
    {
        if ($request->filled('day')) {
            $desde = Carbon::parse((string) $request->input('day'), $this->tz)->startOfDay();
            $hasta = (clone $desde)->endOfDay();
        } elseif ($request->filled('week') && preg_match('/^(\d{4})-W(\d{2})$/', (string) $request->input('week'), $m)) {
            $desde = Carbon::now($this->tz)->setISODate((int) $m[1], (int) $m[2], 1)->startOfDay();
            $hasta = (clone $desde)->addDays(6)->endOfDay();
        } elseif ($request->filled('month')) {
            $desde = Carbon::parse($request->input('month') . '-01', $this->tz)->startOfDay();
            $hasta = (clone $desde)->endOfMonth()->endOfDay();
        } elseif ($request->filled('desde') || $request->filled('inicio') || $request->filled('from') || $request->filled('to')) {
            $desdeInput = (string) ($request->input('desde')
                ?? $request->input('inicio')
                ?? $request->input('from')
                ?? $request->input('to'));

            $hastaInputRaw = $request->input('hasta')
                ?? $request->input('fin')
                ?? $request->input('to');
            $hastaInput = $hastaInputRaw === null ? '' : (string) $hastaInputRaw;

            // Mantener consistencia con la app de gráficas: día calendario completo.
            $desde = Carbon::parse($desdeInput, $this->tz)->startOfDay();

            if ($hastaInput !== '') {
                $hasta = Carbon::parse($hastaInput, $this->tz)->endOfDay();
            } elseif ($request->filled('inicio') || $request->filled('fin') || $request->filled('from') || $request->filled('to')) {
                // Si viene formato tipo día/rango sin fin explícito, cerrar al final del mismo día calendario.
                $hasta = Carbon::parse($desdeInput, $this->tz)->endOfDay();
            } else {
                // Compatibilidad hacia atrás para `desde` sin `hasta`.
                $hasta = Carbon::now($this->tz)->endOfDay();
            }
        } else {
            // Default: último mes
            $desde = Carbon::now($this->tz)->subMonth()->startOfDay();
            $hasta = Carbon::now($this->tz)->endOfDay();
        }

        return [$desde, $hasta];
    }

    // Construye y ejecuta la query base de reportes con filtros opcionales
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

    // Calcula el MTBF global como promedio ponderado de gaps entre fallas por maquina
    private function calcularMTBFGlobal($reportes): float
    {
        $byMachine = $reportes->groupBy(fn($r) => optional($r->maquina)->id);
        $allGaps = [];

        foreach ($byMachine as $rows) {
            $rows = $rows->sortBy('inicio')->values();
            for ($i = 0; $i < $rows->count() - 1; $i++) {
                if ($rows[$i]->fin && $rows[$i + 1]->inicio) {
                    $gap = $rows[$i]->fin->diffInSeconds($rows[$i + 1]->inicio);
                    if ($gap > 0) {
                        $allGaps[] = $gap;
                    }
                }
            }
        }

        return !empty($allGaps) ? array_sum($allGaps) / count($allGaps) : 0;
    }

    // Agrupa reportes por turno y devuelve conteo
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

    // Top 10 líneas por downtime total
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

    // Top 10 máquinas por downtime total
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

    // Top 10 departamentos por cantidad de fallas
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

    // Downtime agrupado por turno
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

    // MTTR promedio por máquina, top 10
    private function mttrPorMaquina($reportes, $secToHours): array
    {
        return $reportes->groupBy(fn($r) => optional($r->maquina)->name)
            ->map(function ($rows, $name) use ($secToHours) {
                $totalDownSec = $rows->sum(fn($r) => $r->tiempo_total_segundos ?? 0);
                $mttrSec      = $rows->count() > 0 ? $totalDownSec / $rows->count() : 0;
                return [
                    'nombre'       => $name ?: 'Sin máquina',
                    'mttr_horas'   => $secToHours($mttrSec),
                    'mttr_minutos' => round($mttrSec / 60, 2),
                    'total'        => $rows->count(),
                ];
            })
            ->sortByDesc('mttr_horas')
            ->take(10)
            ->values()
            ->toArray();
    }

    // MTBF promedio por máquina, top 10
    private function mtbfPorMaquina($reportes, $secToHours): array
    {
        $byMachine = $reportes->groupBy(fn($r) => optional($r->maquina)->id);
        $result = [];

        foreach ($byMachine as $machineId => $rows) {
            $rows = $rows->sortBy('inicio')->values();
            $gaps = [];
            for ($i = 0; $i < $rows->count() - 1; $i++) {
                if ($rows[$i]->fin && $rows[$i + 1]->inicio) {
                    $gap = $rows[$i]->fin->diffInSeconds($rows[$i + 1]->inicio);
                    if ($gap > 0) {
                        $gaps[] = $gap;
                    }
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

    // Serie diaria de MTTR y MTBF para graficas de tendencia
    private function serieDiaria($reportes, Carbon $desde, Carbon $hasta, $secToHours): array
    {
        $cursor = $desde->copy()->startOfDay();
        $end = $hasta->copy()->endOfDay();

        $series = [];

        while ($cursor < $end) {
            $dayStart = $cursor->copy();
            $dayEnd = $cursor->copy()->addDay();
            $dayRows = $reportes->filter(fn($r) => $r->inicio && $r->inicio >= $dayStart && $r->inicio < $dayEnd);

            $totalDownSec = $dayRows->sum(fn($r) => $r->tiempo_total_segundos ?? 0);
            $mttrSec      = $dayRows->count() > 0 ? $totalDownSec / $dayRows->count() : 0;

            $series[] = [
                'fecha'           => $dayStart->format('Y-m-d'),
                'total_reportes'  => $dayRows->count(),
                'mttr_horas'      => $secToHours($mttrSec),
                'downtime_horas'  => $secToHours($dayRows->sum(fn($r) => $r->tiempo_total_segundos ?? 0)),
            ];

            $cursor->addDay();
        }

        return $series;
    }

    // Conteo de reportes agrupados por dia
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

    // Envuelve los datos en la estructura de respuesta estandar de la API
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
