# 📊 Estadísticas de Herramentales - Documentación

## 🎯 Descripción General

Dashboard completo de estadísticas y análisis de fallas causadas por herramentales defectuosos. Proporciona métricas clave de mantenimiento, visualización con gráficos interactivos y filtros por fecha.

---

## 📍 Rutas

### 1. **Vista HTML - Dashboard Interactivo**

```
GET /herramentales-stats
```

**Parámetros (opcionales):**
- `desde` (date): Fecha inicial (formato: YYYY-MM-DD)
- `hasta` (date): Fecha final (formato: YYYY-MM-DD)

**Ejemplo:**
```
GET /herramentales-stats?desde=2026-01-01&hasta=2026-02-05
```

**Respuesta:** HTML con dashboard interactivo completo

---

### 2. **API JSON - Datos Estadísticos**

```
GET /api/herramentales-estadisticas
```

**Parámetros (opcionales):**
- `desde` (date): Fecha inicial
- `hasta` (date): Fecha final

**Ejemplo:**
```
GET /api/herramentales-estadisticas?desde=2026-01-01&hasta=2026-02-05
```

**Respuesta (200 OK):**
```json
{
  "periodo": {
    "desde": "2026-01-01",
    "hasta": "2026-02-05"
  },
  "resumen": {
    "total_fallas": 45,
    "mttr_minutos": 23.5,
    "mtbf_horas": 18.3,
    "tiempo_total_downtime_horas": 17.625,
    "tiempo_total_downtime_minutos": 1057.5
  },
  "por_maquina": [
    {
      "maquina_id": 3,
      "maquina_nombre": "Torno CNC-01",
      "linea_nombre": "Línea A",
      "area_nombre": "Producción",
      "numero_fallas": 8,
      "tiempo_downtime_minutos": 245.5,
      "tiempo_downtime_horas": 4.09
    },
    {
      "maquina_id": 5,
      "maquina_nombre": "Prensa Industrial",
      "linea_nombre": "Línea B",
      "area_nombre": "Ensamble",
      "numero_fallas": 6,
      "tiempo_downtime_minutos": 189.0,
      "tiempo_downtime_horas": 3.15
    }
  ],
  "top_10_herramentales": [
    {
      "herramental_id": 2,
      "herramental_nombre": "Llave Inglesa 10mm",
      "numero_fallos": 12,
      "tiempo_downtime_total_minutos": 285.5,
      "tiempo_downtime_promedio_minutos": 23.79
    },
    {
      "herramental_id": 5,
      "herramental_nombre": "Destornillador Phillips",
      "numero_fallos": 9,
      "tiempo_downtime_total_minutos": 198.0,
      "tiempo_downtime_promedio_minutos": 22.0
    }
  ],
  "estadisticas_herramentales": [
    {
      "herramental_id": 2,
      "herramental_nombre": "Llave Inglesa 10mm",
      "total_fallos": 12,
      "tiempo_promedio_minutos": 23.79,
      "tiempo_minimo_minutos": 15.5,
      "tiempo_maximo_minutos": 45.0,
      "tiempo_total_minutos": 285.5
    }
  ]
}
```

---

## 📊 Métricas Explicadas

### **MTTR (Mean Time To Repair) - Minutos**
- **Definición:** Tiempo promedio que toma reparar una falla
- **Cálculo:** Suma de (fin - inicio) / número de fallos
- **Utilidad:** Indicador de eficiencia del equipo de mantenimiento
- **Ejemplo:** MTTR 23.5 min = en promedio se tarda 23.5 minutos reparar

### **MTBF (Mean Time Between Failures) - Horas**
- **Definición:** Tiempo promedio entre una falla y la siguiente
- **Cálculo:** Suma de (inicio_fallo_N+1 - fin_fallo_N) / número de intervalos convertido a horas
- **Utilidad:** Indicador de confiabilidad del equipo
- **Ejemplo:** MTBF 18.3 horas = en promedio hay 18.3 horas entre fallos

### **Downtime Total - Horas**
- **Definición:** Tiempo total que los equipos estuvieron parados por fallas de herramental
- **Cálculo:** Suma de todos (fin - inicio)
- **Utilidad:** Impacto total en producción

### **Fallos por Máquina**
- **Definición:** Cantidad de fallas de herramental en cada máquina
- **Utilidad:** Identificar máquinas problemáticas
- **Insight:** Máquinas con más fallos pueden necesitar mantenimiento preventivo

### **Top 10 Herramentales**
- **Definición:** Herramentales que causaron más fallas
- **Utilidad:** Identificar herramientas defectuosas o gastadas
- **Recomendación:** Reemplazar o reparar top 10

---

## 🎨 Dashboard - Componentes Visuales

### **1. KPIs Principales**
```
┌─────────────────┬──────────────┬──────────────┬──────────────┐
│ Total Fallos    │ MTTR (min)   │ MTBF (horas) │ Downtime (h) │
│      45         │     23.5     │     18.3     │     17.6     │
└─────────────────┴──────────────┴──────────────┴──────────────┘
```

### **2. Gráfica: Top 10 Herramentales**
- Tipo: Gráfica de barras horizontal
- Datos: Número de fallos + downtime total
- Filtros: Se actualiza con fechas

### **3. Gráfica: Fallos por Máquina**
- Tipo: Gráfica de barras vertical
- Datos: Número de fallos por máquina
- Ordenado: Descendente por fallos

### **4. Tabla: Detalle por Herramental**
- Columnas:
  - Herramental
  - Total Fallos
  - Tiempo Promedio (min)
  - Mínimo (min)
  - Máximo (min)
  - Total Downtime (min)
- Ordenado: Top 10

### **5. Tabla: Máquinas Afectadas**
- Columnas:
  - Máquina
  - Línea
  - Área
  - Número de Fallos
  - Downtime (Horas)
- Ordenado: Por número de fallos

### **6. Filtros de Fecha**
- Selector "Desde" (date)
- Selector "Hasta" (date)
- Botones: Filtrar / Limpiar

---

## 🔍 Casos de Uso

### **1. Análisis de Herramientas Defectuosas**
```
GET /api/herramentales-estadisticas
```
Response: Top 10 herramentales
→ Identificar cuál herramental causa más problemas
→ Reemplazar o reparar

### **2. Impacto en Máquinas Específicas**
```
GET /herramentales-stats?desde=2026-01-01&hasta=2026-02-05
```
Filtrar por máquina en tabla "Máquinas Afectadas"
→ Ver cuántas horas de downtime tuvo esa máquina
→ Planificar mantenimiento preventivo

### **3. Reporte Mensual**
```
GET /api/herramentales-estadisticas?desde=2026-02-01&hasta=2026-02-28
```
→ MTTR promedio del mes
→ Máquina más afectada
→ Herramental más problemático

### **4. Análisis de Tendencias**
Comparar MTBF mes a mes
→ ¿Mejora o empeora la confiabilidad?
→ ¿El mantenimiento preventivo está funcionando?

---

## 📋 Ejemplo de Integración Frontend

### **HTML - Link al Dashboard**
```html
<a href="/herramentales-stats" class="btn btn-primary">
  <i class="fas fa-chart-bar"></i> Ver Estadísticas de Herramentales
</a>
```

### **JavaScript - Filtrar por rango de fechas**
```javascript
document.getElementById('filterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const desde = document.getElementById('desde').value;
    const hasta = document.getElementById('hasta').value;
    
    // Opción 1: Ir a dashboard HTML
    window.location.href = `/herramentales-stats?desde=${desde}&hasta=${hasta}`;
    
    // Opción 2: Obtener datos JSON
    fetch(`/api/herramentales-estadisticas?desde=${desde}&hasta=${hasta}`)
        .then(r => r.json())
        .then(data => console.log(data))
        .catch(e => console.error(e));
});
```

### **React/Vue - Componente de Estadísticas**
```javascript
// Componente React
useEffect(() => {
    const params = new URLSearchParams({
        desde: startDate,
        hasta: endDate
    });
    
    fetch(`/api/herramentales-estadisticas?${params}`)
        .then(r => r.json())
        .then(data => {
            setMTTR(data.resumen.mttr_minutos);
            setTop10(data.top_10_herramentales);
            setMaquinas(data.por_maquina);
        });
}, [startDate, endDate]);
```

---

## 🛠️ Algoritmos de Cálculo

### **MTTR - Pseudocódigo**
```
reportes = todos los reportes con falla='Herramental' 
           y entre rango de fechas
           
tiempos = []
para cada reporte:
    si reporte.inicio y reporte.fin existen:
        tiempo = reporte.fin - reporte.inicio (en minutos)
        tiempos.push(tiempo)

MTTR = suma(tiempos) / count(tiempos)
```

### **MTBF - Pseudocódigo**
```
reportes = ordenar por inicio (ascendente)

intervalos = []
para i = 1 hasta len(reportes):
    inicio_siguiente = reportes[i].inicio
    fin_anterior = reportes[i-1].fin
    intervalo = inicio_siguiente - fin_anterior (en minutos)
    intervalos.push(intervalo)

promedio_intervalos_minutos = suma(intervalos) / count(intervalos)
MTBF = promedio_intervalos_minutos / 60  (convertir a horas)
```

### **Downtime Total - Pseudocódigo**
```
reportes = todos los reportes con falla='Herramental'
           entre rango de fechas
           
downtime_total_minutos = 0
para cada reporte:
    si reporte.inicio y reporte.fin existen:
        downtime_total_minutos += (reporte.fin - reporte.inicio)

downtime_total_horas = downtime_total_minutos / 60
```

---

## 📈 Interpretación de Resultados

| Métrica | Valor "Bueno" | Valor "Malo" | Acción |
|---------|---------------|--------------|--------|
| MTTR | < 20 min | > 40 min | Mejorar procedimientos |
| MTBF | > 24 horas | < 8 horas | Mantenimiento preventivo |
| Downtime | Minimizar | > 20 horas/mes | Análisis de causa raíz |
| Top 1 Herramental | < 5% fallos | > 20% fallos | Reemplazar |

---

## 🔐 Seguridad

- Todos los endpoints están protegidos por autenticación Laravel
- Solo usuarios autenticados pueden ver estadísticas
- No hay restricción de rol (pueden ver cualquier usuario autenticado)
- Agregar restricción por rol si es necesario:
  ```php
  middleware(['auth', 'role:admin|supervisor'])
  ```

---

## 📞 Troubleshooting

### **Dashboard vacío (sin datos)**
```
Causas posibles:
1. No hay reportes con falla='Herramental' en el rango de fechas
2. Los reportes no tienen herramental_id asignado
3. Los reportes no tienen fin (no finalizados)

Solución:
- Crear reportes de prueba con falla='Herramental' y herramental_id
- Asegurar que los reportes se finalizan (tienen fin)
```

### **MTTR o MTBF = 0**
```
Causas:
- No hay reportes con ambos inicio y fin
- Solo hay 1 reporte (MTBF necesita al menos 2)

Solución:
- Ampliar rango de fechas
- Crear más reportes de prueba
```

### **Las gráficas no cargan**
```
Causas:
- Chart.js no se cargó correctamente
- Canvas IDs no coinciden

Solución:
- Verificar consola del navegador (F12)
- Verificar que Chart.js CDN está disponible
- Verificar IDs: chartTop10, chartMaquinas
```

---

## 🔗 Referencias

- Documentación oficial: [Chart.js](https://www.chartjs.org/)
- Rutas API: `/docs/RUTAS_HERRAMENTALES.md`
- Frontend Guide: `/docs/HERRAMENTALES_PARA_FRONTEND.md`

---

**Última actualización:** 5 de Febrero 2026  
**Versión:** 1.0  
**Estado:** ✅ Producción
