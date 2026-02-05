# Optimización de Rendimiento Backend

## Resumen Ejecutivo

Se identificaron y corrigieron **6 problemas críticos** de rendimiento que causaban alto consumo de CPU con 7k+ reportes. Las optimizaciones reducen las queries de **~14,000+** a **~8** por request y limitan los payloads de **7k registros** a **50 por página**.

---

## Problemas Identificados y Soluciones

### 1. ⚡ N+1 Queries en Modelo Reporte (CRÍTICO)

**Problema:** `$appends` incluía `lider_nombre` y `tecnico_nombre` que usaban accessors con `$this->user->name` y `$this->tecnico->name`. Cada serialización de un Reporte disparaba **2 queries** al DB.

```
7,000 reportes × 2 queries = 14,000 queries adicionales por request
```

**Solución:** Denormalizamos `lider_nombre` y `tecnico_nombre` como columnas directas en la tabla `reportes`. Los accessors N+1 fueron eliminados.

**Archivo:** `app/Models/Reporte.php`
- `lider_nombre` y `tecnico_nombre` añadidos a `$fillable`
- Removidos de `$appends` (ya son columnas DB)
- Eliminados `getLiderNombreAttribute()` y `getTecnicoNombreAttribute()`

### 2. 📄 Sin Paginación por Defecto (CRÍTICO)

**Problema:** `GET /api/reportes` sin `?paginate=true` devolvía TODOS los registros. Con 7k reportes, el servidor serializaba ~14MB de JSON en cada request.

**Solución:** Paginación obligatoria:
- **Default:** 50 reportes por página
- **Máximo:** 200 reportes por página (`per_page=200`)
- Parámetro `paginate` ya no es necesario (siempre activo)

**Archivo:** `app/Http/Controllers/ReporteController.php`

### 3. 📅 Default a Reportes de HOY

**Problema:** Sin filtro de fecha, se cargaban TODOS los reportes históricos.

**Solución:** Si no se especifica `day`, `from`, ni `to`, el API filtra automáticamente los reportes del día actual (turno 7:00 AM a 7:00 AM siguiente).

**Archivo:** `app/Http/Controllers/ReporteController.php`

### 4. 🔢 Tiempos Negativos en Estadísticas

**Problema:** Carbon 3.x retorna valores con signo en `diffInMinutes()`. Las estadísticas de herramentales mostraban valores negativos (ej: MTTR: -67.93, downtime: -1183.27).

**Solución:** Todas las funciones de cálculo de tiempo usan `abs()`:
- `calcularMTTR()`
- `calcularMTBF()`
- `calcularTiempoDowntime()`
- `agruparPorMaquina()`
- `top10Herramentales()`
- `estadisticasDetalladas()`

**Archivo:** `app/Http/Controllers/HerramentalStatsController.php`

### 5. 🗄️ Índices de Base de Datos

**Problema:** Faltaban índices para las queries de estadísticas de herramentales.

**Solución:** Migración añade:
- `idx_herramental_inicio` — índice compuesto `(herramental_id, inicio)` para queries de stats
- `idx_lider_nombre` — índice para búsquedas por nombre de líder

**Archivo:** `database/migrations/2026_02_05_000001_optimize_reportes_performance.php`

### 6. 🔗 ReporteService: Columnas/Relaciones Faltantes

**Problema:** El servicio no incluía `lider_nombre`, `tecnico_nombre`, `herramental_id` en SELECT ni cargaba la relación `herramental`.

**Solución:** Añadidas columnas al SELECT y `herramental:id,name` al eager loading.

**Archivo:** `app/Services/ReporteService.php`

---

## Cambios en la API (⚠️ BREAKING CHANGES para Frontend)

### `GET /api/reportes` — Respuesta Ahora Paginada

**ANTES:**
```json
[
  { "id": 1, ... },
  { "id": 2, ... }
]
```

**AHORA:**
```json
{
  "current_page": 1,
  "data": [
    { "id": 1, ... },
    { "id": 2, ... }
  ],
  "first_page_url": "/api/reportes?page=1",
  "from": 1,
  "last_page": 140,
  "last_page_url": "/api/reportes?page=140",
  "next_page_url": "/api/reportes?page=2",
  "path": "/api/reportes",
  "per_page": 50,
  "prev_page_url": null,
  "to": 50,
  "total": 7000
}
```

### Parámetros de Query Actualizados

| Parámetro | Antes | Ahora | Notas |
|-----------|-------|-------|-------|
| `paginate` | Requerido para paginar | **Obsoleto** (siempre pagina) | Puede omitirse |
| `per_page` | Default: 15 | Default: **50**, Max: **200** | Ajustado para rendimiento |
| `page` | Solo con paginate=true | **Siempre disponible** | Default: 1 |
| `day` | Opcional | Opcional (default: **hoy**) | Sin filtro = reportes de hoy |
| `from`/`to` | Opcional | Opcional | Sobreescribe el default de hoy |

### Adaptación del Frontend

```javascript
// ❌ ANTES (ya no funciona)
const response = await fetch('/api/reportes');
const reportes = await response.json();
reportes.forEach(r => { ... });

// ✅ AHORA
const response = await fetch('/api/reportes?per_page=50&page=1');
const result = await response.json();
const reportes = result.data;        // Array de reportes
const total = result.total;           // Total de registros
const lastPage = result.last_page;    // Última página
const nextUrl = result.next_page_url; // URL siguiente página

reportes.forEach(r => { ... });

// Para obtener todos los de un día específico
const response = await fetch('/api/reportes?day=2026-02-05&per_page=200');

// Para rango de fechas
const response = await fetch('/api/reportes?from=2026-01-01&to=2026-02-05&per_page=200');
```

---

## Impacto Estimado en Rendimiento

| Métrica | Antes (7k reportes) | Después | Mejora |
|---------|---------------------|---------|--------|
| Queries por request | ~14,008 | ~8 | **99.94%** |
| Payload JSON | ~14 MB | ~300 KB | **97.8%** |
| Tiempo de respuesta | ~8-15s | ~50-150ms | **98%** |
| Memoria PHP | ~256 MB | ~16 MB | **93.7%** |

---

## Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `app/Models/Reporte.php` | Eliminados N+1 accessors, añadidas columnas a fillable, abs() en tiempos |
| `app/Http/Controllers/ReporteController.php` | Paginación forzada, default HOY, select optimizado |
| `app/Http/Controllers/HerramentalStatsController.php` | abs() en 6 cálculos de tiempo |
| `app/Services/ReporteService.php` | Añadidas columnas y relación herramental |
| `database/migrations/2026_02_05_000001_*` | Columnas denormalizadas + índices |
| `tests/Feature/ReporteHerramentalTest.php` | Adaptado a respuesta paginada |

---

## Migración

La migración se ejecuta automáticamente con:
```bash
php artisan migrate
```

Incluye backfill automático de `lider_nombre` y `tecnico_nombre` desde la tabla `users`. Compatible con MySQL y SQLite.
