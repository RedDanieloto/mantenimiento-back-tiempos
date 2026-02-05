<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use App\Models\Maquina;
use App\Models\herramental;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HerramentalStatsController extends Controller
{
    /**
     * GET /api/herramentales/estadisticas
     * Obtiene estadísticas detalladas de fallas de herramentales
     */
    public function index(Request $request)
    {
        $desde = $request->query('desde') 
            ? Carbon::parse($request->query('desde'))->startOfDay()
            : now()->subMonths(3)->startOfDay();
            
        $hasta = $request->query('hasta')
            ? Carbon::parse($request->query('hasta'))->endOfDay()
            : now()->endOfDay();

        // Obtener todos los reportes que tengan herramental_id asignado
        $reportesHerramenta = Reporte::whereNotNull('herramental_id')
            ->whereBetween('inicio', [$desde, $hasta])
            ->with(['herramental', 'maquina.linea.area'])
            ->get();

        // Si no hay datos
        if ($reportesHerramenta->isEmpty()) {
            return response()->json([
                'periodo' => ['desde' => $desde, 'hasta' => $hasta],
                'total_fallas' => 0,
                'mttr_minutos' => 0,
                'mtbf_horas' => 0,
                'tiempo_total_downtime' => 0,
                'por_maquina' => [],
                'top_10_herramentales' => [],
                'estadisticas_herramentales' => []
            ]);
        }

        // Cálculos
        $totalFallas = $reportesHerramenta->count();
        $mttr = $this->calcularMTTR($reportesHerramenta);
        $mtbf = $this->calcularMTBF($reportesHerramenta, $desde, $hasta);
        $tiempoDowntime = $this->calcularTiempoDowntime($reportesHerramenta);
        $porMaquina = $this->agruparPorMaquina($reportesHerramenta);
        $top10 = $this->top10Herramentales($reportesHerramenta);
        $statsHerramenta = $this->estadisticasDetalladas($reportesHerramenta);

        return response()->json([
            'periodo' => [
                'desde' => $desde->format('Y-m-d'),
                'hasta' => $hasta->format('Y-m-d')
            ],
            'resumen' => [
                'total_fallas' => $totalFallas,
                'mttr_minutos' => round($mttr, 2),
                'mtbf_horas' => round($mtbf, 2),
                'tiempo_total_downtime_horas' => round($tiempoDowntime / 60, 2),
                'tiempo_total_downtime_minutos' => round($tiempoDowntime, 2),
            ],
            'por_maquina' => $porMaquina,
            'top_10_herramentales' => $top10,
            'estadisticas_herramentales' => $statsHerramenta,
        ]);
    }

    /**
     * GET /herramentales-stats (vista HTML con dashboard)
     */
    public function dashboard(Request $request)
    {
        $desde = $request->query('desde') 
            ? Carbon::parse($request->query('desde'))->startOfDay()
            : now()->subMonths(3)->startOfDay();
            
        $hasta = $request->query('hasta')
            ? Carbon::parse($request->query('hasta'))->endOfDay()
            : now()->endOfDay();

        // Parámetros de sort (sort_by: 'fallos' o 'downtime', sort_order: 'desc' o 'asc')
        $sortBy = $request->query('sort_by', 'fallos'); // 'fallos' o 'downtime'
        $sortOrder = $request->query('sort_order', 'desc'); // 'desc' o 'asc'
        $sortByMaquina = $request->query('sort_by_maquina', 'fallos'); // 'fallos' o 'downtime'
        $sortOrderMaquina = $request->query('sort_order_maquina', 'desc'); // 'desc' o 'asc'

        // Obtener datos - todos los reportes que tengan herramental_id asignado
        $reportesHerramenta = Reporte::whereNotNull('herramental_id')
            ->whereBetween('inicio', [$desde, $hasta])
            ->with(['herramental', 'maquina.linea.area'])
            ->get();

        $totalFallas = $reportesHerramenta->count();
        $mttr = $this->calcularMTTR($reportesHerramenta);
        $mtbf = $this->calcularMTBF($reportesHerramenta, $desde, $hasta);
        $tiempoDowntime = $this->calcularTiempoDowntime($reportesHerramenta);
        $porMaquina = $this->agruparPorMaquina($reportesHerramenta);
        $top10 = $this->top10Herramentales($reportesHerramenta);
        
        // Obtener estadísticas detalladas para ordenar
        $estadisticas = $this->estadisticasDetalladas($reportesHerramenta);
        
        // Ordenar estadísticas según parámetro
        if ($sortBy === 'downtime') {
            $estadisticas = collect($estadisticas)->sortBy(
                'tiempo_total_minutos',
                SORT_REGULAR,
                $sortOrder === 'asc'
            )->values()->all();
        } else {
            $estadisticas = collect($estadisticas)->sortBy(
                'total_fallos',
                SORT_REGULAR,
                $sortOrder === 'asc'
            )->values()->all();
        }
        
        // Ordenar máquinas según parámetro
        if ($sortByMaquina === 'downtime') {
            $porMaquina = collect($porMaquina)->sortBy(
                'tiempo_downtime_minutos',
                SORT_REGULAR,
                $sortOrderMaquina === 'asc'
            )->values()->all();
        } else {
            $porMaquina = collect($porMaquina)->sortBy(
                'numero_fallas',
                SORT_REGULAR,
                $sortOrderMaquina === 'asc'
            )->values()->all();
        }

        return view('herramentales.dashboard', [
            'desde' => $desde->format('Y-m-d'),
            'hasta' => $hasta->format('Y-m-d'),
            'totalFallas' => $totalFallas,
            'mttr' => round($mttr, 2),
            'mtbf' => round($mtbf, 2),
            'tiempoDowntime' => round($tiempoDowntime / 60, 2),
            'porMaquina' => $porMaquina,
            'top10' => $top10,
            'reportesHerramenta' => $reportesHerramenta,
            'estadisticas' => $estadisticas,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'sortByMaquina' => $sortByMaquina,
            'sortOrderMaquina' => $sortOrderMaquina,
        ]);
    }

    /**
     * Calcula MTTR (Mean Time To Repair) en minutos
     * Promedio del tiempo entre inicio y fin del reporte
     */
    private function calcularMTTR($reportes)
    {
        if ($reportes->isEmpty()) return 0;

        $tiemposReparacion = $reportes
            ->filter(fn($r) => $r->inicio && $r->fin)
            ->map(fn($r) => abs($r->fin->diffInMinutes($r->inicio)))
            ->values();

        return $tiemposReparacion->isEmpty() 
            ? 0 
            : $tiemposReparacion->avg();
    }

    /**
     * Calcula MTBF (Mean Time Between Failures) en horas
     * Promedio de horas transcurridas entre un fallo y el siguiente
     */
    private function calcularMTBF($reportes, $desde, $hasta)
    {
        if ($reportes->count() < 2) return 0;

        // Ordenar por fecha
        $sorted = $reportes->sortBy('inicio')->values();
        $tiemposEntreFallos = [];

        for ($i = 1; $i < count($sorted); $i++) {
            $tiempo = abs($sorted[$i]->inicio->diffInMinutes($sorted[$i-1]->fin));
            $tiemposEntreFallos[] = $tiempo;
        }

        if (empty($tiemposEntreFallos)) return 0;

        // Convertir a horas
        return (array_sum($tiemposEntreFallos) / count($tiemposEntreFallos)) / 60;
    }

    /**
     * Calcula el tiempo total de downtime en minutos
     * Suma de todos los tiempos entre inicio y fin
     */
    private function calcularTiempoDowntime($reportes)
    {
        return $reportes
            ->filter(fn($r) => $r->inicio && $r->fin)
            ->sum(fn($r) => abs($r->fin->diffInMinutes($r->inicio)));
    }

    /**
     * Agrupa reportes por máquina con estadísticas
     */
    private function agruparPorMaquina($reportes)
    {
        return $reportes
            ->groupBy('maquina_id')
            ->map(function($grupo, $maquinaId) {
                $primeraFila = $grupo->first();
                $maquina = $primeraFila->maquina;
                $tiempoTotal = $grupo
                    ->filter(fn($r) => $r->inicio && $r->fin)
                    ->sum(fn($r) => abs($r->fin->diffInMinutes($r->inicio)));

                return [
                    'maquina_id' => $maquinaId,
                    'maquina_nombre' => $maquina?->name ?? 'Desconocida',
                    'linea_nombre' => optional($maquina?->linea)->name,
                    'area_nombre' => optional(optional($maquina?->linea)->area)->name,
                    'numero_fallas' => $grupo->count(),
                    'tiempo_downtime_minutos' => round($tiempoTotal, 2),
                    'tiempo_downtime_horas' => round($tiempoTotal / 60, 2),
                ];
            })
            ->sortByDesc('numero_fallas')
            ->values();
    }

    /**
     * Top 10 herramentales con más fallos
     */
    private function top10Herramentales($reportes)
    {
        return $reportes
            ->groupBy('herramental_id')
            ->map(function($grupo, $herramentalId) {
                $primeraFila = $grupo->first();
                $herramental = $primeraFila->herramental;
                $tiempoTotal = $grupo
                    ->filter(fn($r) => $r->inicio && $r->fin)
                    ->sum(fn($r) => abs($r->fin->diffInMinutes($r->inicio)));

                return [
                    'herramental_id' => $herramentalId,
                    'herramental_nombre' => $herramental?->name ?? 'Desconocido',
                    'numero_fallos' => $grupo->count(),
                    'tiempo_downtime_total_minutos' => round($tiempoTotal, 2),
                    'tiempo_downtime_promedio_minutos' => round($tiempoTotal / $grupo->count(), 2),
                ];
            })
            ->sortByDesc('numero_fallos')
            ->take(10)
            ->values();
    }

    /**
     * Estadísticas detalladas por herramental
     */
    private function estadisticasDetalladas($reportes)
    {
        return $reportes
            ->groupBy('herramental_id')
            ->map(function($grupo, $herramentalId) {
                $primeraFila = $grupo->first();
                $herramental = $primeraFila->herramental;

                $tiempos = $grupo
                    ->filter(fn($r) => $r->inicio && $r->fin)
                    ->map(fn($r) => abs($r->fin->diffInMinutes($r->inicio)))
                    ->values();

                return [
                    'herramental_id' => $herramentalId,
                    'herramental_nombre' => $herramental?->name ?? 'Desconocido',
                    'total_fallos' => $grupo->count(),
                    'tiempo_promedio_minutos' => $tiempos->isEmpty() ? 0 : round($tiempos->avg(), 2),
                    'tiempo_minimo_minutos' => $tiempos->isEmpty() ? 0 : round($tiempos->min(), 2),
                    'tiempo_maximo_minutos' => $tiempos->isEmpty() ? 0 : round($tiempos->max(), 2),
                    'tiempo_total_minutos' => round($tiempos->sum(), 2),
                ];
            })
            ->sortByDesc('total_fallos')
            ->values();
    }
}
