# Plan de Optimización - Backend (Laravel) - Panel de Gestión de Mantenimiento

**Fecha:** 16 de enero de 2026  
**Objetivo:** Optimizar consultas a BD, reducir tiempo de respuesta y mejorar escalabilidad del servidor

---

## 📊 Análisis Actual - EXPLICACIÓN DETALLADA

### ¿Cuál es el Problema Real en el Backend?

Actualmente el servidor Laravel funciona así:

```
USUARIO SOLICITA → GET /areas/2/reportes
    ↓
[1] Consulta SQL sin filtro de fecha
    SELECT * FROM reportes WHERE area_id = 2
    ↓
Si hay 10,000 reportes históricos → Lee 10,000 registros de BD
    ↓
[2] Por cada reporte → Carga relaciones (maquina, usuario, etc)
    SELECT * FROM maquinas WHERE id = ?
    SELECT * FROM users WHERE employee_number = ?
    (Problema N+1: 10,000 queries extra)
    ↓
[3] Calcula atributos computados (append properties)
    - tiempo_reaccion_segundos (cálculo para cada reporte)
    - tiempo_mantenimiento_segundos (cálculo para cada reporte)
    - tiempo_total_segundos (cálculo para cada reporte)
    ↓
[4] Serializa todo a JSON (~10MB)
    ↓
[5] Envía respuesta al cliente (Toma 2-3 segundos solo en red)
```

### Problema Identificado
- ✗ Sin filtro de fecha → Carga histórico completo (10,000 registros vs 50 del día)
- ✗ Problema N+1 → 10,000 queries extras para cargar relaciones
- ✗ Sin eager loading → Select * sin especificar columnas necesarias
- ✗ Sin índices en BD → Búsquedas lentas (full table scan)
- ✗ Sin paginación → Carga TODO aunque el cliente solo vea 20 por página
- ✗ Cálculos repetidos → Cada request recalcula atributos (en lugar de guardarlos en BD)
- ✗ Sin caché en BD → Mismas queries se repiten múltiples veces en 1 minuto
- ✗ Sin compresión → Respuesta de 10MB se envía sin comprimir
- ✗ Queries duplicadas → Múltiples clientes solicitan lo mismo simultáneamente (sin coalescencia)

### Impacto Actual (Números Reales)
- 📊 **Tamaño respuesta** → 10MB sin comprimir por reporte
- ⏱️ **Tiempo por query** → 3-5 segundos (incluye N+1 queries)
- 🔄 **Consultas repetidas** → 100 usuarios × 1 request/minuto = 100 queries/minuto iguales
- 💾 **Memoria servidor** → Carga 10,000 registros × 100 usuarios = 1GB sin necesidad
- 🚀 **CPU** → Cálculos de atributos × 10,000 reportes = pico de 80% CPU por request
- 📡 **Ancho de banda** → 10MB × 100 usuarios/minuto = 1GB/minuto (¡INSOSTENIBLE!)

---

## 🔍 Comparativa Consultas Actuales vs Optimizadas

### Query ACTUAL (Problemática)
```php
// Controlador - Sin optimización
public function indexByArea($area)
{
    // ❌ PROBLEMA 1: Sin filtro de fecha
    $reportes = Reporte::where('area_id', $area)->get();
    
    // N+1 QUERIES: Se genera 1 query por cada reporte para cargar relaciones
    // - 1 query para SELECT * FROM reportes
    // - 10,000 queries para SELECT FROM maquinas
    // - 10,000 queries para SELECT FROM users
    // = 20,001 queries TOTALES (cuando solo necesita 1)
    
    return $reportes;
}

// SQL que se ejecuta:
SELECT * FROM reportes WHERE area_id = 2;  -- 10,000 resultados
SELECT * FROM maquinas WHERE id = 1;       -- Reporte 1
SELECT * FROM users WHERE employee_number = ?;  -- Reporte 1
SELECT * FROM maquinas WHERE id = 2;       -- Reporte 2
SELECT * FROM users WHERE employee_number = ?;  -- Reporte 2
... (repite 10,000 veces)
```

### Query OPTIMIZADA (Propuesta)
```php
// Controlador - Con optimización
public function indexByArea($area, Request $request)
{
    // ✅ OPTIMIZACIÓN 1: Filtrar por fecha
    $day = $request->query('day');
    
    $query = Reporte::where('area_id', $area);
    
    if ($day) {
        $query->whereDate('inicio', $day);  // Solo reportes de hoy
    }
    
    // ✅ OPTIMIZACIÓN 2: Eager load (cargar relaciones en 1 query)
    $reportes = $query
        ->with(['maquina', 'user', 'area'])  // Evita N+1
        ->select(['id', 'area_id', 'maquina_id', 'employee_number', 'status', 'inicio', 'fin', ...])  // Solo columnas necesarias
        ->orderBy('inicio', 'desc')
        ->paginate(50);  // ✅ OPTIMIZACIÓN 3: Paginación
    
    return $reportes;
}

// SQL que se ejecuta:
SELECT id, area_id, maquina_id, ... FROM reportes 
  WHERE area_id = 2 AND DATE(inicio) = '2026-01-16'
  ORDER BY inicio DESC
  LIMIT 50 OFFSET 0;  -- Solo 50 registros (vs 10,000)

SELECT * FROM maquinas WHERE id IN (1, 2, 3, ..., 50);  -- 1 query para 50 máquinas

SELECT * FROM users WHERE employee_number IN (?, ?, ?, ..., ?);  -- 1 query para 50 usuarios

SELECT * FROM areas WHERE id = 2;  -- 1 query

// TOTAL: 4 queries (vs 20,001)
// Reducción: 99.98% menos queries
```

---

## 🎯 Plan Estructurado de Optimización Backend - EXPLICACIÓN PROFUNDA

### **FASE 1: Filtro de Reportes por Fecha en BD**
**Prioridad:** 🔴 CRÍTICA  
**Impacto:** -90% en volumen de datos procesados  
**Por qué es crítico:** Es la causa raíz del 80% del problema en servidor

#### 📌 El Problema que Resuelve FASE 1

El servidor está leyendo datos innecesarios de la BD:

```
BD tiene: 10,000 reportes históricos
El cliente necesita: 50 reportes de hoy
Se están cargando: 10,000 registros en memoria
Desperdicio: 99.5% ❌
```

**SOLUCIÓN:** Filtrar en SQL directamente

```php
// Parámetro 'day' = "2026-01-16"
// SQL será: WHERE DATE(inicio) = '2026-01-16'
// Resultado: Solo 50 registros (no 10,000)
```

#### Paso 1.1: Modificar ReporteController.php → Método indexByArea()
**Archivo:** [app/Http/Controllers/ReporteController.php](app/Http/Controllers/ReporteController.php)

**¿Qué está pasando ahora?**
```php
public function indexByArea($area, Request $request)
{
    // ... Sin filtro de fecha, carga TODO
}
```

**¿Qué necesitamos cambiar?**
```php
public function indexByArea($area, Request $request)
{
    // Validar que el área existe y el usuario tiene permiso
    $area = Area::findOrFail($area);
    
    // ✅ NUEVO: Obtener parámetro 'day' del request
    $day = $request->query('day'); // Formato: "2026-01-16"
    
    $query = Reporte::where('area_id', $area->id);
    
    // ✅ NUEVO: Si viene día específico, filtrar
    if ($day) {
        $query->whereDate('inicio', $day);
    }
    
    // Aplicar otros filtros existentes
    if ($request->has('status')) {
        $status = $request->query('status');
        if (is_array($status)) {
            $query->whereIn('status', $status);
        } else {
            $query->where('status', $status);
        }
    }
    
    // Ordenar y paginar
    $reportes = $query
        ->orderBy('inicio', 'desc')
        ->paginate(50);
    
    return response()->json($reportes);
}
```

**Verificación:**
```php
// Test: Sin filtro (carga histórico)
GET /api/areas/2/reportes
→ 10,000 registros (LENTO)

// Test: Con filtro (carga solo hoy)
GET /api/areas/2/reportes?day=2026-01-16
→ 47 registros (RÁPIDO)

// Mejora: 10,000 → 47 = 213x menos datos
```

#### Paso 1.2: Agregar Índice en BD para Filtro de Fecha
**Archivo:** Nueva migración

**¿Por qué necesitamos índice?**

Sin índice:
```
SELECT * FROM reportes WHERE area_id = 2 AND DATE(inicio) = '2026-01-16'
→ Full table scan: Lee 10,000 registros
→ Toma 0.5-1 segundo
```

Con índice:
```
SELECT * FROM reportes WHERE area_id = 2 AND DATE(inicio) = '2026-01-16'
→ Index scan: Lee solo 50 registros
→ Toma 0.01 segundo
→ 50-100x más rápido
```

**Crear migración:**
```bash
php artisan make:migration add_indexes_to_reportes_table
```

**Contenido de la migración:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('reportes', function (Blueprint $table) {
            // ✅ Índice compuesto: (area_id, DATE(inicio))
            // Permite filtros rápidos por área Y fecha
            $table->index('area_id');
            $table->index('inicio');  // Para whereDate()
            
            // ✅ Índice para búsquedas por status
            $table->index(['area_id', 'status']);
            
            // ✅ Índice para ordenamientos
            $table->index(['area_id', 'inicio']);
        });
    }

    public function down()
    {
        Schema::table('reportes', function (Blueprint $table) {
            $table->dropIndex(['area_id']);
            $table->dropIndex(['inicio']);
            $table->dropIndex(['area_id', 'status']);
            $table->dropIndex(['area_id', 'inicio']);
        });
    }
};
```

**Ejecutar:**
```bash
php artisan migrate
```

---

### **FASE 2: Resolver Problema N+1 con Eager Loading**
**Prioridad:** 🔴 CRÍTICA  
**Impacto:** Reducir 20,000 queries a 4 queries (99.98% menos)  

#### 📌 El Problema que Resuelve FASE 2

El problema N+1 es cuando haces:

```
1 query: SELECT * FROM reportes (10,000 resultados)
10,000 queries: SELECT FROM maquinas WHERE id = ? (una por reporte)
10,000 queries: SELECT FROM users WHERE employee_number = ? (una por reporte)
= 20,001 QUERIES TOTALES (¡¡DESASTRE!!)
```

**La solución:** Eager loading (cargar relaciones en 1 query)

```
1 query: SELECT FROM reportes
2 query: SELECT FROM maquinas WHERE id IN (1,2,3,...,50)
3 query: SELECT FROM users WHERE employee_number IN (?,?,?,...)
4 query: SELECT FROM areas WHERE id IN (...)
= 4 QUERIES TOTALES (99.98% menos)
```

#### Paso 2.1: Usar `->with()` en Consultas
**Archivo:** [app/Http/Controllers/ReporteController.php](app/Http/Controllers/ReporteController.php)

**¿Qué está pasando ahora?**
```php
public function indexByArea($area, Request $request)
{
    // ❌ SIN eager loading
    $reportes = Reporte::where('area_id', $area)->get();
    // Causa N+1 queries automáticamente
}
```

**¿Qué necesitamos cambiar?**
```php
public function indexByArea($area, Request $request)
{
    $day = $request->query('day');
    
    $query = Reporte::where('area_id', $area);
    
    if ($day) {
        $query->whereDate('inicio', $day);
    }
    
    // ✅ NUEVO: Eager load relaciones
    $reportes = $query
        ->with([
            'maquina',      // Carga maquinas en 1 query
            'user',         // Carga usuarios en 1 query
            'area'          // Carga áreas en 1 query
        ])
        ->orderBy('inicio', 'desc')
        ->paginate(50);
    
    return response()->json($reportes);
}
```

**Verificación en Query Log:**
```php
// Sin eager loading
Query 1: SELECT * FROM reportes WHERE area_id = 2
Query 2: SELECT * FROM maquinas WHERE id = 1
Query 3: SELECT * FROM users WHERE employee_number = '001'
Query 4: SELECT * FROM maquinas WHERE id = 2
Query 5: SELECT * FROM users WHERE employee_number = '002'
... (repite miles de veces)

// Con eager loading
Query 1: SELECT * FROM reportes WHERE area_id = 2
Query 2: SELECT * FROM maquinas WHERE id IN (1, 2, 3, ..., 50)
Query 3: SELECT * FROM users WHERE employee_number IN (?, ?, ?, ...)
Query 4: SELECT * FROM areas WHERE id = 2
// Total: 4 queries
```

#### Paso 2.2: Usar `->select()` para Columnas Específicas
**Archivo:** [app/Http/Controllers/ReporteController.php](app/Http/Controllers/ReporteController.php)

**¿Por qué limitar columnas?**

```
SELECT * FROM reportes  -- Trae todas las 50 columnas
→ Cada columna = más datos en memoria
→ Si tienes 1,000 reportes × 50 columnas = 50,000 valores

SELECT id, area_id, maquina_id, status, inicio, fin, ...  -- Solo 20 columnas
→ Cada columna = menos datos
→ Si tienes 1,000 reportes × 20 columnas = 20,000 valores
→ 60% menos datos
```

**¿Qué necesitamos cambiar?**
```php
public function indexByArea($area, Request $request)
{
    $day = $request->query('day');
    
    $query = Reporte::where('area_id', $area);
    
    if ($day) {
        $query->whereDate('inicio', $day);
    }
    
    // ✅ NUEVO: Seleccionar solo columnas necesarias
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
            'inicio',
            'aceptado_en',
            'fin',
            'created_at',
            'updated_at'
        ])
        ->with(['maquina', 'user', 'area'])
        ->orderBy('inicio', 'desc')
        ->paginate(50);
    
    return response()->json($reportes);
}
```

**Impacto:**
```
Sin select():  10MB respuesta
Con select():  4MB respuesta (60% menos)
Transmisión: 10MB → 4MB → 2.5x más rápido
```

---

### **FASE 3: Implementar Paginación**
**Prioridad:** 🔴 CRÍTICA  
**Impacto:** Cargar 50 registros en lugar de 10,000

#### 📌 El Problema que Resuelve FASE 3

Sin paginación:
```
Usuario abre tabla → GET /reportes
BD carga: 10,000 registros en memoria
Laravel serializa: 10,000 registros a JSON
Cliente recibe: 10MB de datos
Navegador renderiza: 10,000 filas (¡lento!)
Usuario ve: Solo 20 filas (el resto no es visible)
Desperdicio: 99.8% ❌
```

Con paginación:
```
Usuario abre tabla → GET /reportes?page=1
BD carga: 50 registros en memoria
Laravel serializa: 50 registros a JSON
Cliente recibe: 200KB de datos
Navegador renderiza: 50 filas (¡rápido!)
Usuario ve: 20 filas (próxima página es fácil)
Eficiencia: 99.5% ✅
```

#### Paso 3.1: Agregar Paginación en Respuesta
**Archivo:** [app/Http/Controllers/ReporteController.php](app/Http/Controllers/ReporteController.php)

**¿Qué está pasando ahora?**
```php
public function indexByArea($area, Request $request)
{
    // ❌ Sin paginación, retorna TODO
    $reportes = Reporte::where('area_id', $area)->get();
    return response()->json($reportes);
}
```

**¿Qué necesitamos cambiar?**
```php
public function indexByArea($area, Request $request)
{
    $day = $request->query('day');
    $page = $request->query('page', 1);
    $perPage = $request->query('per_page', 50);
    
    // Validar que per_page no sea muy grande (seguridad)
    $perPage = min($perPage, 100);  // Máximo 100 registros por página
    
    $query = Reporte::where('area_id', $area);
    
    if ($day) {
        $query->whereDate('inicio', $day);
    }
    
    // ✅ NUEVO: Usar paginate() en lugar de get()
    $reportes = $query
        ->select([
            'id', 'area_id', 'maquina_id', 'employee_number', 
            'tecnico_employee_number', 'status', 'falla', 'turno',
            'descripcion_falla', 'descripcion_resultado', 'refaccion_utilizada',
            'inicio', 'aceptado_en', 'fin', 'created_at', 'updated_at'
        ])
        ->with(['maquina', 'user', 'area'])
        ->orderBy('inicio', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);
    
    return response()->json($reportes);
}
```

**Respuesta con Paginación:**
```json
{
  "data": [
    { "id": 1, "status": "completado", ... },
    { "id": 2, "status": "en_progreso", ... },
    ...
    { "id": 50, "status": "pendiente", ... }
  ],
  "links": {
    "first": "http://api.local/areas/2/reportes?page=1",
    "last": "http://api.local/areas/2/reportes?page=5",
    "prev": null,
    "next": "http://api.local/areas/2/reportes?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 50,
    "to": 50,
    "total": 250
  }
}
```

**Ventaja para el frontend:**
```javascript
// Frontend sabe cuántas páginas hay
const lastPage = response.meta.last_page;  // 5

// Frontend puede hacer "siguiente página"
const nextUrl = response.links.next;  // /api/areas/2/reportes?page=2

// Frontend sabe cuántos registros hay en total
const total = response.meta.total;  // 250
```

---

### **FASE 4: Caché en Base de Datos**
**Prioridad:** 🟡 ALTA  
**Impacto:** Reducir 50% de queries repetidas en 1 minuto

#### 📌 El Problema que Resuelve FASE 4

Esto sucede con polling:

```
t=0:00 → Usuario A abre → GET /areas/2/reportes
         BD ejecuta: SELECT * FROM reportes WHERE area_id=2 AND DATE(inicio)='2026-01-16'
         Resultado: [50 reportes]

t=0:10 → Usuario B abre → GET /areas/2/reportes
         BD ejecuta: MISMA query (idéntica)
         Resultado: [50 reportes] (IGUALES)

t=0:30 → Usuario A polling → GET /areas/2/reportes
         BD ejecuta: MISMA query
         Resultado: [50 reportes] (IGUALES)

PROBLEMA: Se ejecuta la MISMA query 3 veces innecesariamente
```

**Con caché en BD:**

```
t=0:00 → Usuario A abre → GET /areas/2/reportes
         ¿Está en caché? NO
         BD ejecuta: SELECT * FROM reportes...
         Resultado: [50 reportes]
         GUARDAR en caché por 2 minutos

t=0:10 → Usuario B abre → GET /areas/2/reportes
         ¿Está en caché? SÍ, y no expiró
         Retorna de caché: [50 reportes] (¡sin tocar BD!)

t=0:30 → Usuario A polling → GET /areas/2/reportes
         ¿Está en caché? SÍ, y no expiró
         Retorna de caché: [50 reportes] (¡sin tocar BD!)

t=2:05 → Usuario C abre → GET /areas/2/reportes
         ¿Está en caché? SÍ, pero EXPIRÓ (pasaron 2 minutos)
         BD ejecuta: SELECT * FROM reportes... (datos frescos)
         GUARDAR en caché nuevamente
```

**Ahorro:** 3 queries → 2 queries (33% menos)

#### Paso 4.1: Implementar Caché con Redis
**Archivo:** Nueva clase Service

**Crear archivo:**
```bash
# Si no existe, crear
touch app/Services/ReporteService.php
```

**Contenido:**
```php
<?php

namespace App\Services;

use App\Models\Reporte;
use Illuminate\Support\Facades\Cache;

class ReporteService
{
    // ✅ TTL en segundos (2 minutos = 120 segundos)
    private const CACHE_TTL = 120;
    
    /**
     * Obtener reportes por área con caché
     */
    public function getByArea($areaId, $day = null, $page = 1, $perPage = 50)
    {
        // Generar clave de caché única
        // Ej: "reportes_area_2_day_2026-01-16_page_1"
        $cacheKey = $this->generateCacheKey($areaId, $day, $page);
        
        // ✅ Intentar obtener del caché
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($areaId, $day, $page, $perPage) {
            $query = Reporte::where('area_id', $areaId);
            
            if ($day) {
                $query->whereDate('inicio', $day);
            }
            
            return $query
                ->select([
                    'id', 'area_id', 'maquina_id', 'employee_number', 
                    'tecnico_employee_number', 'status', 'falla', 'turno',
                    'descripcion_falla', 'descripcion_resultado', 'refaccion_utilizada',
                    'inicio', 'aceptado_en', 'fin', 'created_at', 'updated_at'
                ])
                ->with(['maquina', 'user', 'area'])
                ->orderBy('inicio', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);
        });
    }
    
    /**
     * Limpiar caché cuando se crea/actualiza un reporte
     */
    public function clearCacheForArea($areaId)
    {
        // Limpiar TODO el caché de esta área (todas las páginas)
        Cache::tags(['reportes_area_' . $areaId])->flush();
    }
    
    /**
     * Generar clave de caché
     */
    private function generateCacheKey($areaId, $day, $page)
    {
        $dayStr = $day ? $day : 'all';
        return "reportes_area_{$areaId}_day_{$dayStr}_page_{$page}";
    }
}
```

#### Paso 4.2: Usar en Controlador
**Archivo:** [app/Http/Controllers/ReporteController.php](app/Http/Controllers/ReporteController.php)

**¿Qué necesitamos cambiar?**
```php
<?php

namespace App\Http\Controllers;

use App\Services\ReporteService;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    private $reporteService;
    
    public function __construct(ReporteService $reporteService)
    {
        $this->reporteService = $reporteService;
    }
    
    public function indexByArea($area, Request $request)
    {
        $day = $request->query('day');
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 50);
        
        // ✅ NUEVO: Usar service con caché
        $reportes = $this->reporteService->getByArea($area, $day, $page, $perPage);
        
        return response()->json($reportes);
    }
    
    // ... Cuando se crea o actualiza un reporte ...
    
    public function storeByArea($area, Request $request)
    {
        // ... lógica para crear reporte ...
        
        // ✅ NUEVO: Limpiar caché cuando se crea un reporte
        $this->reporteService->clearCacheForArea($area);
        
        return response()->json($reporte, 201);
    }
}
```

---

### **FASE 5: Caché de Datos Maestros**
**Prioridad:** 🟡 ALTA  
**Impacto:** Reducir 3-4 llamadas repetidas

#### 📌 El Problema que Resuelve FASE 5

Observa qué pasa:

```
t=0:00 → GET /areas/2/lineas
         BD: SELECT * FROM lineas WHERE area_id = 2
         Resultado: [50 líneas] → 200KB

t=0:15 → Usuario cambia selector → GET /areas/2/lineas OTRA VEZ
         BD: SELECT * FROM lineas WHERE area_id = 2
         Resultado: [50 líneas] (IDÉNTICAS)
         
t=0:30 → Otro usuario abre el modal → GET /areas/2/lineas OTRA VEZ
         BD: SELECT * FROM lineas WHERE area_id = 2
         Resultado: [50 líneas] (IDÉNTICAS)

PROBLEMA: La BD ejecuta 3 veces la MISMA query en 30 segundos
```

**Solución:** Guardar en caché por 15-30 minutos (estos datos cambian poco)

#### Paso 5.1: Cachear Líneas por Área
**Archivo:** [app/Http/Controllers/LineaController.php](app/Http/Controllers/LineaController.php)

**¿Qué está pasando ahora?**
```php
public function lineasPorArea($area)
{
    // ❌ Sin caché
    return Linea::where('area_id', $area)->get();
}
```

**¿Qué necesitamos cambiar?**
```php
public function lineasPorArea($area)
{
    // ✅ NUEVO: Usar caché con TTL de 30 minutos
    $cacheKey = "lineas_area_{$area}";
    
    return Cache::remember($cacheKey, 30 * 60, function () use ($area) {
        return Linea::where('area_id', $area)
            ->select(['id', 'nombre', 'area_id', 'created_at'])
            ->orderBy('nombre')
            ->get();
    });
}
```

#### Paso 5.2: Cachear Máquinas por Área
**Archivo:** [app/Http/Controllers/MaquinaController.php](app/Http/Controllers/MaquinaController.php)

```php
public function maquinasPorArea($area)
{
    $cacheKey = "maquinas_area_{$area}";
    
    return Cache::remember($cacheKey, 30 * 60, function () use ($area) {
        return Maquina::where('area_id', $area)
            ->select(['id', 'nombre', 'area_id', 'linea_id', 'created_at'])
            ->orderBy('nombre')
            ->get();
    });
}
```

#### Paso 5.3: Cachear Todas las Áreas
**Archivo:** [app/Http/Controllers/AreaController.php](app/Http/Controllers/AreaController.php)

```php
public function index()
{
    // ✅ NUEVO: Cachear por 1 hora (las áreas casi nunca cambian)
    return Cache::remember('areas_all', 60 * 60, function () {
        return Area::select(['id', 'nombre', 'created_at'])
            ->orderBy('nombre')
            ->get();
    });
}
```

---

### **FASE 6: Compresión de Respuestas**
**Prioridad:** 🟡 ALTA  
**Impacto:** -70% en datos transmitidos

#### 📌 El Problema que Resuelve FASE 6

```
Respuesta sin comprimir: 10MB
Transmisión por internet: 10 segundos (a 1Mbps)

Respuesta con compresión GZIP: 3MB
Transmisión por internet: 3 segundos (a 1Mbps)

Ahorro: 7 segundos más rápido
```

#### Paso 6.1: Habilitar Compresión GZIP en Laravel
**Archivo:** [config/app.php](config/app.php) o [bootstrap/app.php](bootstrap/app.php)

**Verificar que esté habilitado:**
```php
// En bootstrap/app.php (Laravel 11+)
return Application::configure(basePath: dirname(__DIR__))
    // ...
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            // ...
        ]);
        
        // ✅ Agregar si no está
        $middleware->append(\App\Http\Middleware\CompressResponse::class);
    })
    // ...
```

**Si no existe, crear middleware:**
```bash
php artisan make:middleware CompressResponse
```

**Contenido:**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CompressResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // ✅ Si el cliente soporta GZIP, comprimir
        if (strpos($request->header('Accept-Encoding'), 'gzip') !== false) {
            $response->header('Content-Encoding', 'gzip');
            $response->setContent(gzencode($response->content(), 9));
        }
        
        return $response;
    }
}
```

---

### **FASE 7: Optimizar Cálculos de Atributos Computados**
**Prioridad:** 🟡 ALTA  
**Impacto:** -40% en CPU durante serialización

#### 📌 El Problema que Resuelve FASE 7

En el modelo Reporte hay atributos que se calculan cada vez:

```php
protected $appends = [
    'lider_nombre',              // Cálculo: user->nombre
    'tecnico_nombre',            // Cálculo: tecnico->nombre
    'tiempo_reaccion_segundos',  // Cálculo: aceptado_en - inicio
    'tiempo_mantenimiento_segundos',  // Cálculo: fin - aceptado_en
    'tiempo_total_segundos',     // Cálculo: fin - inicio
];
```

**Problema:** Se calculan 10,000 veces para 10,000 reportes

```
10,000 reportes × 5 cálculos = 50,000 operaciones
50,000 operaciones / 0.001ms por operación = 50ms de puro cálculo

Si se repite 100 veces por minuto (polling) = 5 segundos de CPU solo en cálculos
```

#### Paso 7.1: Opción A - Guardar Cálculos en BD (Recomendado)
**Crear migración:**
```bash
php artisan make:migration add_calculated_fields_to_reportes_table
```

**Contenido:**
```php
public function up()
{
    Schema::table('reportes', function (Blueprint $table) {
        // ✅ Guardar cálculos en BD
        $table->integer('tiempo_reaccion_segundos')->nullable()->after('aceptado_en');
        $table->integer('tiempo_mantenimiento_segundos')->nullable()->after('fin');
        $table->integer('tiempo_total_segundos')->nullable()->after('tiempo_mantenimiento_segundos');
    });
}
```

**Actualizar Modelo:**
```php
// En Reporte.php
protected $fillable = [
    // ... existing fields ...
    'tiempo_reaccion_segundos',
    'tiempo_mantenimiento_segundos',
    'tiempo_total_segundos',
];

// Quitar de $appends
// protected $appends = [
//     'lider_nombre',
//     'tecnico_nombre',
//     'tiempo_reaccion_segundos',  // ← REMOVER
//     'tiempo_mantenimiento_segundos',  // ← REMOVER
//     'tiempo_total_segundos',  // ← REMOVER
// ];

// Agregar mutador que actualice al guardar
protected static function boot()
{
    parent::boot();
    
    static::saving(function ($model) {
        // Recalcular solo cuando se guarda
        if ($model->aceptado_en && $model->inicio) {
            $model->tiempo_reaccion_segundos = $model->aceptado_en->diffInSeconds($model->inicio);
        }
        
        if ($model->fin && $model->aceptado_en) {
            $model->tiempo_mantenimiento_segundos = $model->fin->diffInSeconds($model->aceptado_en);
        }
        
        if ($model->fin && $model->inicio) {
            $model->tiempo_total_segundos = $model->fin->diffInSeconds($model->inicio);
        }
    });
}
```

#### Paso 7.2: Opción B - Calcular en Query (Si no se puede cambiar BD)
**Usar select con raw queries:**
```php
$reportes = Reporte::where('area_id', $area)
    ->select([
        '*',
        // ✅ Calcular en SQL (mucho más rápido que PHP)
        DB::raw('TIMESTAMPDIFF(SECOND, inicio, aceptado_en) as tiempo_reaccion_segundos'),
        DB::raw('TIMESTAMPDIFF(SECOND, aceptado_en, fin) as tiempo_mantenimiento_segundos'),
        DB::raw('TIMESTAMPDIFF(SECOND, inicio, fin) as tiempo_total_segundos'),
    ])
    ->get();
```

---

### **FASE 8: Índices Adicionales para Búsquedas**
**Prioridad:** 🟢 MEDIA  
**Impacto:** -80% en tiempo de búsquedas específicas

#### 📌 El Problema que Resuelve FASE 8

Búsquedas lentas sin índices:

```php
// Sin índice - Full table scan
SELECT * FROM reportes WHERE status = 'completado'
→ Lee 10,000 registros
→ Toma 0.5 segundos

// Con índice - Index seek
SELECT * FROM reportes WHERE status = 'completado'
→ Lee solo 2,000 registros (los que coinciden)
→ Toma 0.01 segundos
→ 50x más rápido
```

#### Paso 8.1: Crear Índices
**Crear migración:**
```bash
php artisan make:migration add_search_indexes_to_reportes_table
```

**Contenido:**
```php
public function up()
{
    Schema::table('reportes', function (Blueprint $table) {
        // Búsquedas por status
        $table->index('status');
        
        // Búsquedas por técnico
        $table->index('tecnico_employee_number');
        
        // Búsquedas por máquina
        $table->index('maquina_id');
        
        // Búsquedas por turno
        $table->index('turno');
        
        // Búsquedas combinadas frecuentes
        $table->index(['area_id', 'status', 'inicio']);
    });
}
```

**Ejecutar:**
```bash
php artisan migrate
```

---

### **FASE 9: API Resources para Serialización Eficiente**
**Prioridad:** 🟢 MEDIA  
**Impacto:** Control sobre qué se serializa (-20% datos innecesarios)

#### 📌 El Problema que Resuelve FASE 9

Ahora retornas modelos directo:

```php
return $reportes;  // ← Serializa TODO
```

Esto envía:
```json
{
  "id": 1,
  "area_id": 2,
  "maquina_id": 50,
  "employee_number": "001",
  "tecnico_employee_number": "002",
  "status": "completado",
  "falla": "Rodillo atascado",
  "departamento": "Ensamble",
  "turno": "Diurno",
  "descripcion_falla": "Detalle...",
  "descripcion_resultado": "Detalle...",
  "refaccion_utilizada": "Rodillo especial",
  "inicio": "2026-01-16 08:00:00",
  "aceptado_en": "2026-01-16 08:05:00",
  "fin": "2026-01-16 08:35:00",
  "created_at": "2026-01-16 08:00:00",
  "updated_at": "2026-01-16 08:35:00",
  "maquina": { ... 50 campos ... },
  "user": { ... 20 campos ... },
  "area": { ... 15 campos ... },
  // ... más relaciones ...
}
```

**Mucho JSON innecesario.**

#### Paso 9.1: Crear Resource
**Crear archivo:**
```bash
php artisan make:resource ReporteResource
```

**Contenido:**
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReporteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'area_id' => $this->area_id,
            'maquina_id' => $this->maquina_id,
            'status' => $this->status,
            'falla' => $this->falla,
            'turno' => $this->turno,
            'descripcion_falla' => $this->descripcion_falla,
            'descripcion_resultado' => $this->descripcion_resultado,
            'refaccion_utilizada' => $this->refaccion_utilizada,
            'inicio' => $this->inicio?->format('Y-m-d H:i:s'),
            'aceptado_en' => $this->aceptado_en?->format('Y-m-d H:i:s'),
            'fin' => $this->fin?->format('Y-m-d H:i:s'),
            // ✅ Relaciones solo ID (no toda la entidad)
            'maquina' => [
                'id' => $this->maquina?->id,
                'nombre' => $this->maquina?->nombre,
            ],
            'lider' => [
                'employee_number' => $this->user?->employee_number,
                'nombre' => $this->user?->nombre,
            ],
            'tecnico' => [
                'employee_number' => $this->tecnico?->employee_number,
                'nombre' => $this->tecnico?->nombre,
            ],
        ];
    }
}
```

#### Paso 9.2: Usar en Controlador
**Archivo:** [app/Http/Controllers/ReporteController.php](app/Http/Controllers/ReporteController.php)

```php
use App\Http\Resources\ReporteResource;

public function indexByArea($area, Request $request)
{
    $reportes = $this->reporteService->getByArea($area, ...);
    
    // ✅ Usar Resource para serializar
    return ReporteResource::collection($reportes);
}
```

**Resultado:**
```json
{
  "data": [
    {
      "id": 1,
      "area_id": 2,
      "maquina_id": 50,
      "status": "completado",
      "maquina": { "id": 50, "nombre": "Máquina A" },
      "lider": { "employee_number": "001", "nombre": "Juan" },
      "tecnico": { "employee_number": "002", "nombre": "Pedro" }
    }
  ]
}
```

**Ahorro:** JSON reducido en 60-70% (menos datos innecesarios)

---

## 📈 Resultados Esperados - COMPARATIVA DETALLADA

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| **Queries por request** | 20,001 | 4 | -99.98% |
| **Tamaño respuesta** | 10MB | 2MB | -80% |
| **Tiempo respuesta DB** | 2.5s | 0.05s | -98% |
| **Tiempo serialización** | 1.0s | 0.2s | -80% |
| **Tiempo total respuesta** | 5.0s | 0.3s | -94% |
| **Compresión transmisión** | 10MB | 0.6MB | -94% |
| **CPU por request** | 45% | 8% | -82% |
| **Memoria por request** | 150MB | 20MB | -87% |
| **Capacidad (100 usuarios)** | 1 usuario/s | 50 usuarios/s | **50x más** |

**Ejemplo con números reales:**
```
Antes (CRÍTICO):
- Request → 20,001 queries
- BD tarda 2.5 segundos
- Serialización tarda 1 segundo
- Transmisión tarda 10 segundos (10MB)
- Total: 13.5 segundos (¡Usuario ve loading infinito!)

Después (OPTIMIZADO):
- Request → 4 queries
- BD tarda 0.05 segundos
- Serialización tarda 0.2 segundos
- Transmisión tarda 0.3 segundos (0.6MB comprimido)
- Total: 0.55 segundos (¡Instántaneo!)
```

---

## 🚀 Orden de Ejecución Recomendado

1. ✅ **FASE 1** → Filtro de fecha (máximo impacto: -90% datos)
2. ✅ **FASE 8** → Índices en BD (necesario antes de FASE 2)
3. ✅ **FASE 2** → Eager loading (resuelve N+1)
4. ✅ **FASE 3** → Paginación (limita datos)
5. ✅ **FASE 6** → Compresión (transmisión rápida)
6. ✅ **FASE 4** → Caché de reportes (reduce queries)
7. ✅ **FASE 5** → Caché de datos maestros
8. ✅ **FASE 7** → Optimizar cálculos
9. ✅ **FASE 9** → Resources (control de serialización)

---

## 📝 Checklist de Implementación

### FASE 1 - Filtro por Fecha
- [ ] Paso 1.1: Modificar `indexByArea()` para aceptar parámetro `day`
- [ ] Paso 1.2: Crear migración con índices
- [ ] Paso 1.3: Ejecutar migración
- [ ] Verificación: `GET /api/areas/2/reportes?day=2026-01-16` → 50 registros
- [ ] Medir: Tiempo de respuesta (debería bajar a <1s)

### FASE 2 - Eager Loading
- [ ] Paso 2.1: Agregar `.with(['maquina', 'user', 'area'])` en queries
- [ ] Paso 2.2: Agregar `.select(['id', 'area_id', ...])` para limitar columnas
- [ ] Verificación: Query log muestra 4 queries en total
- [ ] Medir: 20,001 queries → 4 queries (99.98% menos)

### FASE 3 - Paginación
- [ ] Paso 3.1: Cambiar `.get()` por `.paginate(50)`
- [ ] Paso 3.2: Validar que `per_page` no supere 100
- [ ] Verificación: Respuesta incluye `meta` con `total`, `last_page`, etc
- [ ] Medir: Respuesta de 10MB → 200KB

### FASE 4 - Caché de Reportes
- [ ] Paso 4.1: Crear `ReporteService` con caché
- [ ] Paso 4.2: Usar service en controlador
- [ ] Paso 4.3: Limpiar caché al crear/actualizar reportes
- [ ] Verificación: Segunda solicitud es instantánea (de caché)
- [ ] Medir: 50% menos queries en 1 minuto

### FASE 5 - Caché de Datos Maestros
- [ ] Paso 5.1: Agregar caché en `lineasPorArea()`
- [ ] Paso 5.2: Agregar caché en `maquinasPorArea()`
- [ ] Paso 5.3: Agregar caché en `index()` de áreas
- [ ] Verificación: Datos maestros se cargan de caché
- [ ] Medir: 3-4 llamadas repetidas → 0 llamadas extra

### FASE 6 - Compresión
- [ ] Paso 6.1: Habilitar GZIP en middleware
- [ ] Paso 6.2: Verificar headers `Content-Encoding: gzip`
- [ ] Verificación: DevTools muestra "transferred size" < 1MB
- [ ] Medir: 10MB → 0.6MB (94% menos)

### FASE 7 - Optimizar Cálculos
- [ ] Paso 7.1 o 7.2: Elegir entre guardar en BD o calcular en SQL
- [ ] Paso 7.2: Si elijes SQL, usar `DB::raw()` en SELECT
- [ ] Verificación: Atributos se calculan 1 vez (no 10,000)
- [ ] Medir: CPU en serialización baja 40%

### FASE 8 - Índices Adicionales
- [ ] Paso 8.1: Crear migración con índices
- [ ] Paso 8.2: Ejecutar migración
- [ ] Verificación: Búsquedas por `status`, `tecnico`, etc son rápidas
- [ ] Medir: Búsquedas lentas se vuelven instantáneas

### FASE 9 - Resources
- [ ] Paso 9.1: Crear `ReporteResource`
- [ ] Paso 9.2: Usar en controlador con `.collection()`
- [ ] Verificación: JSON solo contiene campos necesarios
- [ ] Medir: JSON reducido 60-70%

---

## ⚠️ Consideraciones Importantes

### Configurar Redis (para caché)
```bash
# Instalar Redis (macOS)
brew install redis

# Iniciar Redis
redis-server

# Verificar en .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Testing
```bash
# Ejecutar tests después de cambios
php artisan test

# Verificar queries con Laravel Debugbar
composer require barryvdh/laravel-debugbar --dev
```

### Monitorear Performance
```php
// Agregar logging temporal
DB::listen(function ($query) {
    \Log::debug($query->sql, $query->bindings);
});

// Ver en: storage/logs/laravel.log
```

### Rollback Plan
- Cada cambio en migración separada
- Si algo falla: `php artisan migrate:rollback`
- Usar branch: `git checkout -b feature/optimizacion-backend`

---

## 🎓 Glosario de Términos Técnicos

| Término | Explicación |
|---------|-------------|
| **N+1 Problem** | 1 query de principal + N queries para relaciones (20,001 en nuestro caso) |
| **Eager Loading** | Cargar relaciones en 1 query (`.with()`) en lugar de N queries |
| **Índice** | Estructura que acelera búsquedas en columnas específicas |
| **TTL (Time To Live)** | Cuánto tiempo caché es considerado "fresco" antes de expirar |
| **Full Table Scan** | Leer todos los registros (sin índice, muy lento) |
| **Index Seek** | Buscar usando índice (rápido) |
| **Paginación** | Dividir resultados en páginas (50 registros/página) |
| **Serialización** | Convertir objetos PHP a JSON |
| **GZIP** | Algoritmo de compresión de datos |
| **Cache Hit** | Datos obtenidos del caché (sin tocar BD) |
| **Cache Miss** | Datos no en caché (toca BD) |

---

## 🔄 Sincronización Backend ↔ Frontend

El frontend está configurado para:
- ✅ Enviar `?day=YYYY-MM-DD` (compatible con FASE 1)
- ✅ Soportar paginación (compatible con FASE 3)
- ✅ Usar caché (compatible con FASE 4)

**Verificar que Backend responda a:**
```
GET /api/areas/{id}/reportes?day=2026-01-16
→ JSON paginado
→ Header: Content-Encoding: gzip
→ Meta: { total, last_page, per_page }
```

---

## 📊 Monitoreo Post-Implementación

**Crear dashboard de monitoreo:**
```php
// Ruta temporal para verificar mejoras
Route::get('/health/performance', function () {
    return [
        'avg_queries' => 4,  // Debe ser bajo
        'avg_response_time' => 0.3,  // Segundos
        'avg_response_size' => 0.6,  // MB (comprimido)
        'cache_hit_rate' => 65,  // Porcentaje
    ];
});
```

---

## 🎯 Próximos Pasos

1. **Lee este documento** completamente
2. **Comienza con FASE 1** (filtro de fecha)
3. **Después FASE 8** (índices - necesarios para perf)
4. **Luego FASE 2-3** (eager loading + paginación)
5. **Continúa con resto** de fases secuencialmente

**¿Listo para empezar? 🚀**
