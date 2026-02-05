# 📊 Dashboard de Estadísticas de Herramentales - Resumen de Implementación

**Fecha:** 5 de Febrero 2026  
**Estado:** ✅ Completado y Funcional  
**Versión:** 1.0

---

## 🎯 Lo que se Implementó

### 1. **Backend - Controller**
**Archivo:** `app/Http/Controllers/HerramentalStatsController.php`

Contiene dos métodos principales:

#### `index(Request $request)` - API JSON
```http
GET /api/herramentales-estadisticas?desde=2026-01-01&hasta=2026-02-05
```

Retorna JSON con:
- Total de fallos de herramental
- MTTR (Mean Time To Repair) en minutos
- MTBF (Mean Time Between Failures) en horas
- Tiempo total de downtime
- Fallos agrupados por máquina
- Top 10 herramentales con más fallos
- Estadísticas detalladas por herramental

#### `dashboard(Request $request)` - Vista HTML
```http
GET /herramentales-stats?desde=2026-01-01&hasta=2026-02-05
```

Retorna vista HTML con:
- Dashboard interactivo con Chart.js
- KPIs principales (MTTR, MTBF, Downtime)
- Gráficas de barras
- Tablas detalladas
- Filtros de fecha

---

### 2. **Rutas Registradas**

#### **Rutas Web**
```php
GET /herramentales-stats  → dashboard HTML
```

#### **Rutas API**
```php
GET /api/herramentales-estadisticas  → JSON data
```

---

### 3. **Vista Blade**
**Archivo:** `resources/views/herramentales/dashboard.blade.php`

Características:
- ✅ KPIs principales con colores diferenciados
- ✅ 2 gráficas interactivas con Chart.js
  - Top 10 herramentales (gráfica de barras horizontal)
  - Fallos por máquina (gráfica de barras vertical)
- ✅ 2 tablas detalladas
  - Detalle por herramental
  - Máquinas afectadas por fallas
- ✅ Filtros de fecha (desde/hasta)
- ✅ Responsive design con Bootstrap 5
- ✅ Iconos con Font Awesome 6.4.0

---

## 📊 Métricas Calculadas

### **MTTR (Mean Time To Repair) - Minutos**
```
Fórmula: Suma(fin - inicio) / número de fallos
Ejemplo: MTTR = 23.5 minutos
Interpretación: Tiempo promedio para reparar una falla
```

### **MTBF (Mean Time Between Failures) - Horas**
```
Fórmula: Suma(inicio_fallo_N+1 - fin_fallo_N) / número de intervalos / 60
Ejemplo: MTBF = 18.3 horas
Interpretación: Tiempo promedio entre una falla y la siguiente
```

### **Downtime Total - Horas**
```
Fórmula: Suma(fin - inicio) / 60
Ejemplo: Downtime = 17.6 horas
Interpretación: Horas totales que equipos estuvieron parados
```

---

## 🎨 Componentes Visuales

### **KPIs Principales**
```
┌──────────────────┬────────────────┬────────────────┬────────────────┐
│ Total Fallos     │ MTTR (min)     │ MTBF (horas)   │ Downtime (h)   │
│ 19               │ 23.5           │ 18.3           │ 17.6           │
└──────────────────┴────────────────┴────────────────┴────────────────┘
```

### **Gráfica 1: Top 10 Herramentales**
- Tipo: Barra Horizontal
- Datos: 
  - Número de fallos (amarillo)
  - Downtime total en minutos (rojo)
- Ordenado: Descendente por fallos

### **Gráfica 2: Fallos por Máquina**
- Tipo: Barra Vertical
- Datos: Número de fallos por máquina
- Ordenado: Descendente por fallos

### **Tabla 1: Detalle por Herramental**
| Herramental | Total Fallos | Prom (min) | Min (min) | Max (min) | Total Downtime (min) |
|---|---|---|---|---|---|
| Llave Inglesa | 5 | 23.4 | 20 | 30 | 117 |
| Destornillador | 4 | 22.3 | 18 | 25 | 89 |

### **Tabla 2: Máquinas Afectadas**
| Máquina | Línea | Área | Fallos | Downtime (h) |
|---|---|---|---|---|
| Torno CNC-01 | Línea A | Producción | 5 | 2.0 |
| Prensa Industrial | Línea B | Ensamble | 4 | 1.5 |

---

## 🔧 Métodos del Controlador

### **calcularMTTR($reportes)**
```php
Calcula el promedio de tiempo de reparación
- Filtra reportes con fin y inicio
- Suma diferencias en minutos
- Retorna promedio
```

### **calcularMTBF($reportes, $desde, $hasta)**
```php
Calcula tiempo promedio entre fallos
- Ordena reportes por fecha
- Suma intervalos entre fin de uno e inicio del siguiente
- Convierte a horas
```

### **calcularTiempoDowntime($reportes)**
```php
Suma total de tiempo de parada
- Suma todas las diferencias fin - inicio
- Retorna en minutos
```

### **agruparPorMaquina($reportes)**
```php
Agrupa y suma estadísticas por máquina
- Agrupa por maquina_id
- Calcula fallos y downtime por máquina
- Ordena descendente
```

### **top10Herramentales($reportes)**
```php
Top 10 herramentales con más fallos
- Agrupa por herramental_id
- Calcula estadísticas
- Toma primeros 10
```

### **estadisticasDetalladas($reportes)**
```php
Estadísticas completas por herramental
- Min, Max, Promedio, Total
- Ordena por total de fallos
```

---

## 🔗 Endpoints Disponibles

### **API JSON**
```http
GET /api/herramentales-estadisticas
GET /api/herramentales-estadisticas?desde=2026-02-01&hasta=2026-02-28
GET /api/herramentales-estadisticas?desde=2026-02-01&hasta=2026-02-28&page=1
```

**Respuesta ejemplo:**
```json
{
  "periodo": {
    "desde": "2026-02-01",
    "hasta": "2026-02-28"
  },
  "resumen": {
    "total_fallas": 19,
    "mttr_minutos": 23.5,
    "mtbf_horas": 18.3,
    "tiempo_total_downtime_horas": 17.625,
    "tiempo_total_downtime_minutos": 1057.5
  },
  "por_maquina": [
    {
      "maquina_id": 1,
      "maquina_nombre": "Torno CNC-01",
      "linea_nombre": "Línea A",
      "area_nombre": "Producción",
      "numero_fallas": 5,
      "tiempo_downtime_minutos": 120,
      "tiempo_downtime_horas": 2.0
    }
  ],
  "top_10_herramentales": [
    {
      "herramental_id": 1,
      "herramental_nombre": "Llave Inglesa",
      "numero_fallos": 5,
      "tiempo_downtime_total_minutos": 117,
      "tiempo_downtime_promedio_minutos": 23.4
    }
  ]
}
```

### **Web Dashboard**
```http
GET /herramentales-stats
GET /herramentales-stats?desde=2026-02-01&hasta=2026-02-05
```

---

## 📱 Características del Dashboard

### ✅ Funcionalidades
- Filtros de fecha rango (desde/hasta)
- Gráficas interactivas con Chart.js
- Tablas responsive con scroll
- KPIs resaltados por color
- Iconos descriptivos
- Badges para categorización
- Botones de acción (filtrar/limpiar)

### ✅ Responsividad
- Mobile-first design
- Breakpoints Bootstrap (xs, sm, md, lg, xl)
- Tablas scrollables en mobile
- Gráficas escalables

### ✅ Estilos
- Bootstrap 5.3.0 CDN
- Font Awesome 6.4.0 CDN
- Colores personalizados
- Hover effects en tablas y cards
- Sombras y transiciones suaves

---

## 🚀 Cómo Usar

### **Acceder al Dashboard**
```
URL: http://localhost:8000/herramentales-stats
```

### **Filtrar por Fecha**
1. Seleccionar "Desde" (date picker)
2. Seleccionar "Hasta" (date picker)
3. Click en "Filtrar"
4. Dashboard se recarga con nuevos datos

### **Consultar API**
```bash
# Últimos 3 meses (default)
curl http://localhost:8000/api/herramentales-estadisticas

# Rango específico
curl "http://localhost:8000/api/herramentales-estadisticas?desde=2026-01-01&hasta=2026-02-05"
```

### **Integrar en Frontend**
```javascript
// Obtener datos JSON
fetch('/api/herramentales-estadisticas?desde=2026-02-01&hasta=2026-02-05')
    .then(r => r.json())
    .then(data => {
        console.log('MTTR:', data.resumen.mttr_minutos);
        console.log('Fallos:', data.resumen.total_fallas);
    });
```

---

## 📝 Datos de Prueba

Se incluye script SQL para insertar 19 reportes de prueba:
**Archivo:** `/tmp/insert_test_data.sql`

Datos:
- 5 fallos de Llave Inglesa (máquinas 1-5)
- 4 fallos de Destornillador (máquinas 1-4)
- 3 fallos de Martillo (máquinas 2, 3, 5)
- 2 fallos de Llave Torx (máquinas 1, 4)
- 2 fallos de Alicates (máquinas 3, 5)

---

## 🔍 Ejemplo Completo de Flujo

### **1. Usuario accede al dashboard**
```
GET /herramentales-stats
```
↓ Vista carga con último trimestre

### **2. Usuario filtra fechas**
```
Selecciona: 2026-02-01 a 2026-02-05
Click "Filtrar"
```
↓ JavaScript envía parámetros en URL

### **3. Backend procesa**
```
GET /herramentales-stats?desde=2026-02-01&hasta=2026-02-05
HerramentalStatsController::dashboard()
```
↓ Calcula métricas en 5 métodos paralelos

### **4. Vista se actualiza**
```
Dashboard muestra:
- KPIs actualizados
- Gráficas nuevas
- Tablas filtradas
```

### **5. Usuario analiza datos**
- ¿Cuál herramental tiene más fallos?
- ¿Cuánto downtime total?
- ¿Qué máquina es más problemática?

---

## 🛠️ Configuración Avanzada

### **Cambiar rango de fechas por defecto**
En `HerramentalStatsController.php`:
```php
$desde = $request->query('desde') 
    ? Carbon::parse($request->query('desde'))->startOfDay()
    : now()->subMonths(6)->startOfDay();  // ← Cambiar número
```

### **Cambiar "Top N" herramentales**
En método `top10Herramentales()`:
```php
->take(20)  // ← Cambiar de 10 a 20
```

### **Agregar más gráficas**
En `resources/views/herramentales/dashboard.blade.php`:
```html
<!-- Copiar sección de canvas + script -->
<canvas id="chartNuevo"></canvas>
```

---

## ✅ Testing y Validación

### **Probar API**
```bash
# Status 200 OK
curl -s http://localhost:8000/api/herramentales-estadisticas | jq .periodo

# Con filtros
curl -s "http://localhost:8000/api/herramentales-estadisticas?desde=2026-02-01&hasta=2026-02-05" | jq .resumen
```

### **Probar Dashboard**
```bash
# Debe retornar HTML con titulo
curl -s http://localhost:8000/herramentales-stats | grep "Estadísticas de Herramentales"
```

### **Prueba Manual**
1. Navegar a `/herramentales-stats`
2. Verificar que cargan KPIs
3. Verificar que cargan gráficas
4. Cambiar fechas y filtrar
5. Verificar que datos se actualizan

---

## 📚 Documentación Referenciada

- [ESTADISTICAS_HERRAMENTALES.md](./ESTADISTICAS_HERRAMENTALES.md) - API completa
- [HERRAMENTALES_PARA_FRONTEND.md](./HERRAMENTALES_PARA_FRONTEND.md) - Guía integration
- [RUTAS_HERRAMENTALES.md](./RUTAS_HERRAMENTALES.md) - Endpoints

---

## 🎓 Próximos Pasos Sugeridos

1. **Agregar filtros adicionales**
   - Por línea
   - Por área
   - Por máquina

2. **Más gráficas**
   - Pie chart de herramentales
   - Timeline de fallos
   - Heatmap por hora del día

3. **Exportar datos**
   - Exportar a Excel con gráficas
   - PDF del reporte
   - CSV para análisis

4. **Comparativas**
   - Comparar periodos
   - Visualizar tendencias
   - Predicciones

5. **Alertas**
   - Notificación si MTTR > X minutos
   - Alerta si una máquina supera Y fallos
   - Email con reporte semanal

---

## 📞 Soporte

**Error: Las gráficas no aparecen**
→ Verificar consola (F12) para errores JavaScript
→ Asegurar que datos existen en la BD

**Error: Filtros no funcionan**
→ Verificar que los inputs date tienen valores
→ Verificar en Network tab que URL tiene parámetros

**Error: Downtime = 0**
→ Asegurar que reportes tienen fin (no null)
→ Asegurar que fin > inicio

---

**Implementado por:** GitHub Copilot  
**Última actualización:** 5 de Febrero 2026  
**Estado:** ✅ Producción  
**Versión Larvel:** 11  
**Versión PHP:** 8.4.1
