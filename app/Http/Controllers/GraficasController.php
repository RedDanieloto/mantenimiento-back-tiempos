<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Linea;
use App\Models\Reporte;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GraficasController extends Controller
{
    private string $tz = 'America/Mexico_City';
    public function index(Request $request)
    {
        $areas = Area::orderBy('name')->get(['id','name']);
        $lineasQuery = Linea::query();
        if ($request->filled('area_id')) {
            $lineasQuery->where('area_id', (int)$request->input('area_id'));
        }
        $lineas = $lineasQuery->orderBy('name')->get(['id','name','area_id']);
        try {
            $departamentos = Reporte::whereNotNull('departamento')
                ->where('departamento', '!=', '')
                ->distinct('departamento')
                ->orderBy('departamento')
                ->pluck('departamento')
                ->values();
        } catch (\Exception $e) {
            $departamentos = collect([]);
        }
        $query = Reporte::with(['maquina.linea.area']);
        $this->applyFilters($request, $query);

        $reportes = $query->get();
        $metrics = $this->computeMetrics($request, $reportes);
        $maquinasTopScrap = $reportes
            ->groupBy(fn($r) => optional($r->maquina)->id)
            ->map(function ($rows, $id) {
                return [
                    'id' => $id,
                    'name' => optional($rows->first()->maquina)->name ?: 'Sin máquina',
                    'scrap_total' => (int) $rows->sum(fn($r) => (int) ($r->scrap ?? 0)),
                ];
            })
            ->filter(fn($m) => !empty($m['id']) && $m['scrap_total'] > 0)
            ->sortByDesc('scrap_total')
            ->take(30)
            ->values();

        return view('graficas.index', [
            'filters' => [
                'day'      => $request->input('day'),
                'from'     => $request->input('from'),
                'to'       => $request->input('to'),
                'week'     => $request->input('week'),
                'month'    => $request->input('month'),
                'area_id'  => $request->input('area_id'),
                'linea_id' => $request->input('linea_id'),
                'maquina_id' => $request->input('maquina_id'),
                'turno'    => $request->input('turno'),
                'departamento' => implode(',', $request->input('departamento', [])),
            ],
            'areas'   => $areas,
            'lineas'  => $lineas,
            'maquinasTopScrap' => $maquinasTopScrap,
            'departamentos' => $departamentos,
            'metrics' => $metrics,
        ]);
    }
    public function export(Request $request)
    {
        $period = $request->input('day')
            ?: $request->input('week')
            ?: $request->input('month')
            ?: ($request->input('from') && $request->input('to') ? ($request->input('from').'_a_'.$request->input('to')) : 'rango');
        $filename = 'kpis_reportes_'.$period.'.xlsx';

        return (new \App\Exports\ReportesExport($request))
            ->download($filename, \Maatwebsite\Excel\Excel::XLSX, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'X-Content-Type-Options' => 'nosniff',
            ]);
    }
    private function applyFilters(Request $request, $q): void
    {
        if ($request->filled('status')) {
            $q->whereIn('status', explode(',', (string)$request->input('status')));
        }
        if ($request->filled('turno')) {
            $q->whereIn('turno', explode(',', (string)$request->input('turno')));
        }
        if ($request->filled('area_id')) {
            $q->whereIn('area_id', explode(',', (string)$request->input('area_id')));
        }
        if ($request->filled('linea_id')) {
            $lineas = explode(',', (string)$request->input('linea_id'));
            $q->whereHas('maquina', fn($mq) => $mq->whereIn('linea_id', $lineas));
        }
        if ($request->filled('departamento')) {
            $depts = $request->input('departamento');
            if (is_array($depts) && !empty($depts)) {
                $q->whereIn('departamento', $depts);
            }
        }
        if ($request->filled('maquina_id')) {
            $q->whereIn('maquina_id', explode(',', (string)$request->input('maquina_id')));
        }
        if ($request->filled('q')) {
            $term = '%'.str_replace(' ', '%', (string)$request->input('q')).'%';
            $q->where(function ($w) use ($term) {
                $w->where('falla', 'like', $term)
                  ->orWhere('descripcion_falla', 'like', $term)
                  ->orWhere('descripcion_resultado', 'like', $term)
                  ->orWhere('refaccion_utilizada', 'like', $term)
                  ->orWhereHas('maquina', fn($mq) => $mq->where('name', 'like', $term))
                  ->orWhereHas('maquina.linea', fn($lq) => $lq->where('name', 'like', $term))
                  ->orWhereHas('maquina.linea.area', fn($aq) => $aq->where('name', 'like', $term));
            });
        }
        if ($request->filled('day')) {
            $start = Carbon::parse((string)$request->input('day'), $this->tz)->setTime(7, 0, 0);
            $end   = (clone $start)->addDay();
            $q->whereBetween('inicio', [$start, $end]);
        } elseif ($request->filled('from') || $request->filled('to')) {
            $fromDay = $request->input('from');
            $toDay   = $request->input('to', $fromDay);
            if ($fromDay) {
                $start = Carbon::parse((string)$fromDay, $this->tz)->setTime(7, 0, 0);
                $end   = Carbon::parse((string)$toDay, $this->tz)->setTime(7, 0, 0)->addDay();
                $q->whereBetween('inicio', [$start, $end]);
            }
        } elseif ($request->filled('week')) { 
            $weekStr = (string)$request->input('week');
            if (preg_match('/^(\d{4})-W(\d{2})$/', $weekStr, $m)) {
                $year = (int)$m[1];
                $week = (int)$m[2];
                $start = Carbon::now($this->tz)->setISODate($year, $week, 1)->setTime(7, 0, 0);
                $end   = (clone $start)->addDays(7); 
                $q->whereBetween('inicio', [$start, $end]);
            }
        } elseif ($request->filled('month')) { 
            $month = $request->input('month');
            try {
                $start = Carbon::parse($month.'-01', $this->tz)->setTime(7, 0, 0);
                $end   = (clone $start)->addMonth();
                $q->whereBetween('inicio', [$start, $end]);
            } catch (\Throwable $e) {
              
            }
        }
    }

    private function computeMetrics(Request $request, $reportes): array
    {
    $secToHours = fn($s) => $s === null ? null : max(0, round($s / 3600, 2));
        $turnoLabel = function ($t) {
            $t = (string)$t;
            return match ($t) {
                '1' => 'A', '2' => 'B', '3' => 'C', default => ($t ?: 'N/A')
            };
        };
        $totalSeconds = $reportes->sum(fn($r) => $r->tiempo_total_segundos ?? 0);
        $mttrAvg = $reportes->count() > 0 ? $totalSeconds / $reportes->count() : 0;
        $mtbfPerMachine = [];
        $allGaps = [];
        $byMachine = $reportes->groupBy(fn($r) => optional($r->maquina)->id);
        foreach ($byMachine as $machineId => $rows) {
            $rows = $rows->sortBy('inicio')->values();
            $gaps = [];
            for ($i = 0; $i < $rows->count() - 1; $i++) {
                $a = $rows[$i];
                $b = $rows[$i + 1];
                if ($a->fin && $b->inicio) {
                    $gap = $a->fin->diffInSeconds($b->inicio);
                    if ($gap > 0) {
                        $gaps[] = $gap;
                        $allGaps[] = $gap;
                    }
                }
            }
            if (!empty($gaps)) {
                $mtbfPerMachine[$machineId] = array_sum($gaps) / count($gaps);
            }
        }
        $mtbfAvg = !empty($allGaps) ? array_sum($allGaps) / count($allGaps) : 0;
        $topLineas = $reportes->groupBy(fn($r) => optional(optional($r->maquina)->linea)->name)
            ->map(fn($rows, $name) => [
                'name' => $name ?: 'Sin línea',
                'seconds' => $rows->sum(fn($r) => $r->tiempo_total_segundos ?? 0),
            ])
            ->sortByDesc('seconds')->take(10)->values();
        $topMaquinas = $reportes->groupBy(fn($r) => optional($r->maquina)->name)
            ->map(fn($rows, $name) => [
                'name' => $name ?: 'Sin máquina',
                'seconds' => $rows->sum(fn($r) => $r->tiempo_total_segundos ?? 0),
            ])
            ->sortByDesc('seconds')->take(10)->values();
        $topMaquinasScrap = $reportes->groupBy(fn($r) => optional($r->maquina)->name)
            ->map(fn($rows, $name) => [
                'name' => $name ?: 'Sin máquina',
                'scrap_total' => (int) $rows->sum(fn($r) => (int) ($r->scrap ?? 0)),
            ])
            ->filter(fn($row) => $row['scrap_total'] > 0)
            ->sortByDesc('scrap_total')
            ->take(10)
            ->values();
        $porTurno = $reportes->groupBy(fn($r) => $turnoLabel($r->turno))
            ->map(fn($rows, $label) => [
                'turno' => $label,
                'seconds' => $rows->sum(fn($r) => $r->tiempo_total_segundos ?? 0),
            ])
            ->sortBy('turno')->values();
        $mttrPorMaquina = $reportes
            ->groupBy(fn($r) => optional($r->maquina)->name)
            ->map(function ($rows, $name) {
                $totalDown = $rows->sum(fn($r) => $r->tiempo_total_segundos ?? 0);
                return [
                    'name'    => $name ?: 'Sin máquina',
                    'seconds' => $rows->count() > 0 ? $totalDown / $rows->count() : 0,
                ];
            })
            ->sortByDesc('seconds')->take(10)->values();
        if ($request->filled('day')) {
            $minDate = Carbon::parse((string)$request->input('day'), $this->tz)->setTime(7, 0, 0);
            $maxDate = (clone $minDate)->addDay();
        } elseif ($request->filled('week') && preg_match('/^(\\d{4})-W(\\d{2})$/', (string)$request->input('week'), $m)) {
            $year = (int)$m[1];
            $week = (int)$m[2];
            $minDate = Carbon::now($this->tz)->setISODate($year, $week, 1)->setTime(7, 0, 0);
            $maxDate = (clone $minDate)->addDays(7);
        } elseif ($request->filled('month')) {
            $minDate = Carbon::parse($request->input('month').'-01', $this->tz)->setTime(7, 0, 0);
            $maxDate = (clone $minDate)->addMonth();
        } elseif ($request->filled('from') || $request->filled('to')) {
            $fromDay = $request->input('from');
            $toDay = $request->input('to', $fromDay);
            if ($fromDay) {
                $minDate = Carbon::parse((string)$fromDay, $this->tz)->setTime(7, 0, 0);
                $maxDate = Carbon::parse((string)$toDay, $this->tz)->setTime(7, 0, 0)->addDay();
            } else {
                $minDate = $reportes->min('inicio') ?: Carbon::now($this->tz)->startOfMonth()->setTime(7, 0, 0);
                $maxDate = $reportes->max('inicio') ?: (clone $minDate)->endOfMonth()->setTime(7, 0, 0)->addDay();
            }
        } else {
            if ($reportes->isNotEmpty()) {
                $minDate = $reportes->min('inicio');
                $maxDate = $reportes->max('inicio');
            } else {
                $minDate = Carbon::now($this->tz)->startOfMonth()->setTime(7, 0, 0);
                $maxDate = (clone $minDate)->endOfMonth()->setTime(7, 0, 0)->addDay();
            }
        }
        $cursor = Carbon::parse($minDate, $this->tz)->setTime(7, 0, 0);
        $end    = Carbon::parse($maxDate, $this->tz)->setTime(7, 0, 0)->addDay();

        $labelsDays = [];
        $seriesMttr = [];
        $seriesMtbf = [];
        $mtbfDaySum = [];
        $mtbfDayCnt = [];
        foreach ($byMachine as $machineId => $rowsAll) {
            $rowsAll = $rowsAll->sortBy('inicio')->values();
            for ($i = 0; $i < $rowsAll->count() - 1; $i++) {
                $a = $rowsAll[$i];
                $b = $rowsAll[$i + 1];
                if ($a->fin && $b->inicio) {
                    $gap = $a->fin->diffInSeconds($b->inicio);
                    $bInicio = $b->inicio->copy()->setTimezone($this->tz);
                    $bucketStart = $bInicio->copy()->hour < 7
                        ? $bInicio->copy()->subDay()->setTime(7,0,0)
                        : $bInicio->copy()->setTime(7,0,0);
                    $bucketKey = $bucketStart->toDateString();
                    $mtbfDaySum[$bucketKey] = ($mtbfDaySum[$bucketKey] ?? 0) + $gap;
                    $mtbfDayCnt[$bucketKey] = ($mtbfDayCnt[$bucketKey] ?? 0) + 1;
                }
            }
        }

        while ($cursor < $end) {
            $dayLabel = $cursor->format('d');
            $dayStart = (clone $cursor);
            $dayEnd   = (clone $cursor)->addDay();

            $dayRows = $reportes->filter(fn($r) => $r->inicio && $r->inicio >= $dayStart && $r->inicio < $dayEnd);

            $totalDownDay = $dayRows->sum(fn($r) => $r->tiempo_total_segundos ?? 0);
            $mttrDay = $dayRows->count() > 0 ? $totalDownDay / $dayRows->count() : 0;
            $bucketKey = $dayStart->toDateString();
            $sum = $mtbfDaySum[$bucketKey] ?? 0;
            $cnt = $mtbfDayCnt[$bucketKey] ?? 0;
            $mtbfDay = $cnt > 0 ? ($sum / $cnt) : 0;

            $labelsDays[] = $dayLabel;
            $seriesMttr[] = $secToHours($mttrDay);
            $seriesMtbf[] = $secToHours($mtbfDay);

            $cursor->addDay();
        }
        $abiertosPorDia = $reportes->filter(fn($r) => in_array($r->status, ['abierto','en_mantenimiento']))
            ->groupBy(fn($r) => $r->inicio ? $r->inicio->format('Y-m-d') : 'unknown')
            ->map(fn($rows, $d) => ['day' => $d, 'count' => $rows->count()])
            ->sortBy('day')->values();
        $mtbfPorMaquina = collect($mtbfPerMachine)
            ->map(function ($secs, $machineId) use ($reportes, $secToHours) {
                $name = optional($reportes->firstWhere('maquina_id', $machineId)?->maquina)->name;
                return [ 'name' => $name ?: ('ID '.$machineId), 'hours' => $secToHours($secs) ];
            })
            ->sortByDesc('hours')
            ->take(10)
            ->values();
        $topDepartamentos = $reportes->groupBy(fn($r) => $r->departamento)
            ->map(fn($rows, $dept) => [
                'name' => $dept ?: 'Sin departamento',
                'count' => $rows->count(),
            ])
            ->sortByDesc('count')->take(10)->values();

        return [
            'cards' => [
                'mttr_avg_hours' => $secToHours($mttrAvg) ?? 0,
                'mtbf_avg_hours' => $secToHours($mtbfAvg) ?? 0,
                'total_hours'    => $secToHours($totalSeconds) ?? 0,
            ],
            'top_lineas' => [
                'labels' => $topLineas->pluck('name'),
                'data'   => $topLineas->pluck('seconds')->map($secToHours),
            ],
            'top_maquinas' => [
                'labels' => $topMaquinas->pluck('name'),
                'data'   => $topMaquinas->pluck('seconds')->map($secToHours),
            ],
            'top_maquinas_scrap' => [
                'labels' => $topMaquinasScrap->pluck('name'),
                'data'   => $topMaquinasScrap->pluck('scrap_total'),
            ],
            'por_turno' => [
                'labels' => $porTurno->pluck('turno'),
                'data'   => $porTurno->pluck('seconds')->map($secToHours),
            ],
            'mttr_por_maquina' => [
                'labels' => $mttrPorMaquina->pluck('name'),
                'data'   => $mttrPorMaquina->pluck('seconds')->map($secToHours),
            ],
            'serie_diaria' => [
                'labels' => $labelsDays,
                'mttr'   => $seriesMttr,
                'mtbf'   => $seriesMtbf,
                'goal_mttr' => array_fill(0, count($labelsDays), 1),
                'goal_mtbf' => array_fill(0, count($labelsDays), 10),
            ],
            'abiertos_dia' => [
                'labels' => $abiertosPorDia->pluck('day'),
                'data'   => $abiertosPorDia->pluck('count'),
                'goal'   => array_fill(0, $abiertosPorDia->count(), 10),
            ],
            'mtbf_por_maquina' => [
                'labels' => $mtbfPorMaquina->pluck('name'),
                'data'   => $mtbfPorMaquina->pluck('hours'),
            ],
            'top_departamentos' => [
                'labels' => $topDepartamentos->pluck('name'),
                'data'   => $topDepartamentos->pluck('count'),
            ],
            
        ];
    }
}
