<?php

namespace App\Services;

use App\Models\Reporte;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

class ReporteService
{
    private string $tz = 'America/Mexico_City';
    
    // Tiempo de vida de la cache en segundos
    private const CACHE_TTL = 120;
    
    // Estados de reportes pendientes que siempre se muestran
    private const ALWAYS_VISIBLE_STATUSES = [
        'abierto',
        'en_mantenimiento',
        'en_proceso',
        'pendiente',
        'asignado'
    ];

    // Obtiene y pagina los reportes de un area aplicando filtros y cache
    public function getByArea(
        int $areaId,
        ?string $day = null,
        int $page = 1,
        int $perPage = 50,
        ?array $filters = []
    ): LengthAwarePaginator {
        $perPage = min($perPage, 100);
        $cacheKey = $this->generateCacheKey($areaId, $day, $page, $perPage);
        
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }
        
        $query = Reporte::where('area_id', $areaId);
        
        if ($day) {
            $start = Carbon::parse($day, $this->tz)->startOfDay();
            $end   = (clone $start)->endOfDay();
            
            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('inicio', [$start, $end])
                  ->orWhereIn('status', self::ALWAYS_VISIBLE_STATUSES);
            });
        }
        
        if (!empty($filters['status']) && is_array($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }
        
        if (!empty($filters['turno']) && is_array($filters['turno'])) {
            $query->whereIn('turno', $filters['turno']);
        }
        
        if (!empty($filters['tecnico_employee_number'])) {
            $tecnico = $filters['tecnico_employee_number'];
            if ($tecnico === 'null' || $tecnico === null) {
                $query->whereNull('tecnico_employee_number');
            } else {
                $query->where('tecnico_employee_number', $tecnico);
            }
        }
        
        $reportes = $query
            ->select([
                'id',
                'area_id',
                'maquina_id',
                'employee_number',
                'tecnico_employee_number',
                'status',
                'falla',
                'turno',
                'descripcion_falla',
                'descripcion_resultado',
                'refaccion_utilizada',
                'departamento',
                'lider_nombre',
                'tecnico_nombre',
                'herramental_id',
                'inicio',
                'aceptado_en',
                'fin',
                'created_at',
                'updated_at'
            ])
            ->with([
                'maquina:id,name,linea_id',
                'user:employee_number,name,role,turno',
                'tecnico:employee_number,name,role,turno',
                'area:id,name',
                'herramental:id,name'
            ])
            ->orderBy('inicio', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        Cache::put($cacheKey, $reportes, self::CACHE_TTL);
        
        return $reportes;
    }
    
    // Obtiene un reporte especifico con sus relaciones
    public function getById(int $reporteId): ?Reporte
    {
        return Reporte::with([
            'maquina.linea.area',
            'user',
            'tecnico'
        ])->find($reporteId);
    }
    
    // Invalida las claves de cache asociadas a un area
    public function clearCacheForArea(int $areaId): void
    {
        for ($page = 1; $page <= 10; $page++) {
            for ($perPage = 50; $perPage <= 100; $perPage += 50) {
                $cacheKey = $this->generateCacheKey($areaId, null, $page, $perPage);
                Cache::forget($cacheKey);
                
                $today = date('Y-m-d');
                $cacheKey = $this->generateCacheKey($areaId, $today, $page, $perPage);
                Cache::forget($cacheKey);
            }
        }
    }
    
    // Genera la clave unica para almacenar en cache
    private function generateCacheKey(int $areaId, ?string $day, int $page, int $perPage): string
    {
        $dayStr = $day ? $day : 'all';
        return "reportes_area_{$areaId}_day_{$dayStr}_page_{$page}_perpage_{$perPage}";
    }
}

