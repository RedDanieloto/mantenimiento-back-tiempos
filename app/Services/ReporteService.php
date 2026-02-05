<?php

namespace App\Services;

use App\Models\Reporte;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

class ReporteService
{
    private string $tz = 'America/Mexico_City';
    
    // ✅ TTL en segundos (2 minutos = 120 segundos)
    private const CACHE_TTL = 120;
    
    /**
     * Obtener reportes por área con optimizaciones
     * - Filtro por fecha
     * - Eager loading
     * - Select limitado de columnas
     * - Paginación
     * - Caché
     */
    public function getByArea(
        int $areaId,
        ?string $day = null,
        int $page = 1,
        int $perPage = 50,
        ?array $filters = []
    ): LengthAwarePaginator {
        // Validar que per_page no sea muy grande (seguridad)
        $perPage = min($perPage, 100);
        
        // Generar clave de caché única
        // Ej: "reportes_area_2_day_2026-01-16_page_1"
        $cacheKey = $this->generateCacheKey($areaId, $day, $page, $perPage);
        
        // ✅ Intentar obtener del caché primero
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }
        
        // ❌ No está en caché, construir query
        $query = Reporte::where('area_id', $areaId);
        
        // ✅ FILTRO 1: Por fecha
        if ($day) {
            $start = Carbon::parse($day, $this->tz)->setTime(7, 0, 0);
            $end   = (clone $start)->addDay();
            $query->whereBetween('inicio', [$start, $end]);
        }
        
        // ✅ FILTRO 2: Por status (si viene en filtros)
        if (!empty($filters['status']) && is_array($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }
        
        // ✅ FILTRO 3: Por turno (si viene en filtros)
        if (!empty($filters['turno']) && is_array($filters['turno'])) {
            $query->whereIn('turno', $filters['turno']);
        }
        
        // ✅ FILTRO 4: Por técnico (si viene en filtros)
        if (!empty($filters['tecnico_employee_number'])) {
            $tecnico = $filters['tecnico_employee_number'];
            if ($tecnico === 'null' || $tecnico === null) {
                $query->whereNull('tecnico_employee_number');
            } else {
                $query->where('tecnico_employee_number', $tecnico);
            }
        }
        
        // ✅ OPTIMIZACIÓN 1: Select de columnas específicas (no SELECT *)
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
            // ✅ OPTIMIZACIÓN 2: Eager loading (evita N+1 queries)
            ->with([
                'maquina:id,name,linea_id',
                'user:employee_number,name,role,turno',
                'tecnico:employee_number,name,role,turno',
                'area:id,name',
                'herramental:id,name'
            ])
            // ✅ OPTIMIZACIÓN 3: Ordenamiento
            ->orderBy('inicio', 'desc')
            // ✅ OPTIMIZACIÓN 4: Paginación (no cargar TODO)
            ->paginate($perPage, ['*'], 'page', $page);
        
        // ✅ Guardar en caché
        Cache::put($cacheKey, $reportes, self::CACHE_TTL);
        
        return $reportes;
    }
    
    /**
     * Obtener un reporte con todas sus relaciones cargadas
     */
    public function getById(int $reporteId): ?Reporte
    {
        return Reporte::with([
            'maquina.linea.area',
            'user',
            'tecnico'
        ])->find($reporteId);
    }
    
    /**
     * Limpiar caché cuando se crea/actualiza un reporte en el área
     */
    public function clearCacheForArea(int $areaId): void
    {
        // Limpiar el caché que empiece con "reportes_area_X"
        // Para esto usamos un patrón de invalidación
        
        // Opción 1: Si usas Redis con tags (recomendado)
        // Cache::tags(['reportes_area_' . $areaId])->flush();
        
        // Opción 2: Borrar todas las claves manualmente (menos eficiente)
        // Por ahora, como Laravel no tiene un get de claves por patrón fácil,
        // usaremos una aproximación simple: invalidar las primeras N páginas
        for ($page = 1; $page <= 10; $page++) {
            for ($perPage = 50; $perPage <= 100; $perPage += 50) {
                $cacheKey = $this->generateCacheKey($areaId, null, $page, $perPage);
                Cache::forget($cacheKey);
                
                // También limpiar con día específico
                $today = date('Y-m-d');
                $cacheKey = $this->generateCacheKey($areaId, $today, $page, $perPage);
                Cache::forget($cacheKey);
            }
        }
    }
    
    /**
     * Generar clave de caché única
     */
    private function generateCacheKey(int $areaId, ?string $day, int $page, int $perPage): string
    {
        $dayStr = $day ? $day : 'all';
        return "reportes_area_{$areaId}_day_{$dayStr}_page_{$page}_perpage_{$perPage}";
    }
}
