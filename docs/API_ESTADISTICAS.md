# API de Estadísticas — Dashboard Centralizado

> **Base URL:** `GET /api/estadisticas/...`  
> **App identifier:** `mantenimiento-tiempos`  
> **Timezone:** `America/Mexico_City`

API diseñada para ser consumida por una aplicación externa que centraliza estadísticas y gráficas de múltiples sistemas.

---

## Índice

1. [Formato de Respuesta](#formato-de-respuesta)
2. [Filtros Comunes](#filtros-comunes)
3. [Endpoints](#endpoints)
   - [Health Check](#1-health-check)
   - [Resumen (KPIs)](#2-resumen-kpis)
   - [Gráficas](#3-gráficas)
   - [Tendencias](#4-tendencias)
   - [Tiempo Real](#5-tiempo-real)
   - [Por Área](#6-por-área)
   - [Herramentales](#7-herramentales)
   - [Técnicos](#8-técnicos)
   - [Catálogos](#9-catálogos)
4. [Ejemplos de Integración](#ejemplos-de-integración)
5. [Glosario de Métricas](#glosario-de-métricas)

---

## Formato de Respuesta

Todos los endpoints (excepto `/health` y `/catalogos`) devuelven esta estructura:

```json
{
  "app": "mantenimiento-tiempos",
  "timestamp": "2026-02-19T10:30:00-06:00",
  "periodo": {
    "desde": "2026-01-19",
    "hasta": "2026-02-19"
  },
  "data": { ... }
}
```

| Campo       | Tipo     | Descripción                                          |
|-------------|----------|------------------------------------------------------|
| `app`       | `string` | Identificador de la aplicación origen                |
| `timestamp` | `string` | Momento de la consulta (ISO 8601)                    |
| `periodo`   | `object` | Rango de fechas consultado                           |
| `data`      | `object` | Datos específicos del endpoint                       |

---

## Filtros Comunes

Aplicables a los endpoints: `/resumen`, `/graficas`, `/areas`, `/herramentales`, `/tecnicos`.

### Filtros de Periodo

| Parámetro | Formato        | Ejemplo              | Descripción                            |
|-----------|----------------|----------------------|----------------------------------------|
| `day`     | `YYYY-MM-DD`   | `?day=2026-02-19`    | Un día específico (7:00 a 7:00)        |
| `week`    | `YYYY-Wnn`     | `?week=2026-W08`     | Semana ISO (lunes 7:00 a lunes 7:00)   |
| `month`   | `YYYY-MM`      | `?month=2026-02`     | Mes completo                           |
| `desde`   | `YYYY-MM-DD`   | `?desde=2026-01-01`  | Fecha inicio del rango                 |
| `hasta`   | `YYYY-MM-DD`   | `?hasta=2026-02-19`  | Fecha fin del rango                    |

> **Prioridad:** `day` > `week` > `month` > `desde/hasta`  
> **Default:** Último mes (si no se envía ningún filtro de periodo)

### Filtros de Datos

| Parámetro      | Tipo              | Ejemplo                        | Descripción                           |
|----------------|-------------------|--------------------------------|---------------------------------------|
| `area_id`      | `int` o CSV       | `?area_id=1,2`                 | Filtrar por área(s)                   |
| `linea_id`     | `int` o CSV       | `?linea_id=3`                  | Filtrar por línea(s)                  |
| `turno`        | `string` o CSV    | `?turno=1,2`                   | Filtrar por turno(s): 1=A, 2=B, 3=C  |
| `departamento` | `string` o CSV    | `?departamento=Produccion`     | Filtrar por departamento(s)           |
| `status`       | `string` o CSV    | `?status=abierto,OK`           | Filtrar por estado(s)                 |

---

## Endpoints

---

### 1. Health Check

Verifica que la aplicación está activa y la base de datos conectada. Ideal para monitoreo desde el dashboard centralizado.

```
GET /api/estadisticas/health
```

**Sin parámetros.**

#### Respuesta

```json
{
  "app": "mantenimiento-tiempos",
  "status": "ok",
  "timestamp": "2026-02-19T10:30:00-06:00",
  "version": "1.0.0",
  "database": "connected",
  "actividad": {
    "reportes_ultimas_24h": 15,
    "total_reportes": 4523,
    "total_maquinas": 87,
    "total_areas": 5
  }
}
```

| Campo                           | Tipo     | Descripción                              |
|---------------------------------|----------|------------------------------------------|
| `status`                        | `string` | `"ok"` si todo funciona                  |
| `version`                       | `string` | Versión de la API                        |
| `database`                      | `string` | Estado de conexión a BD                  |
| `actividad.reportes_ultimas_24h`| `int`    | Reportes creados en las últimas 24 horas |
| `actividad.total_reportes`      | `int`    | Total de reportes en el sistema          |
| `actividad.total_maquinas`      | `int`    | Total de máquinas registradas            |
| `actividad.total_areas`         | `int`    | Total de áreas registradas               |

---

### 2. Resumen (KPIs)

Devuelve los indicadores clave de rendimiento en una sola llamada. Ideal para tarjetas de resumen en el dashboard.

```
GET /api/estadisticas/resumen
```

**Parámetros:** [Filtros comunes](#filtros-comunes)

#### Respuesta `data`

```json
{
  "kpis": {
    "total_reportes": 120,
    "abiertos": 5,
    "en_mantenimiento": 3,
    "finalizados": 112,
    "mttr_horas": 1.25,
    "mttr_minutos": 75.0,
    "mtbf_horas": 18.5,
    "downtime_total_horas": 150.75,
    "reaccion_promedio_minutos": 12.3
  },
  "distribucion_status": [
    { "status": "abierto", "count": 5, "porcentaje": 4.2 },
    { "status": "en_mantenimiento", "count": 3, "porcentaje": 2.5 },
    { "status": "OK", "count": 112, "porcentaje": 93.3 }
  ],
  "distribucion_turno": [
    { "turno": "A", "count": 45 },
    { "turno": "B", "count": 40 },
    { "turno": "C", "count": 35 }
  ]
}
```

| Campo KPI                    | Tipo    | Descripción                                         |
|------------------------------|---------|-----------------------------------------------------|
| `total_reportes`             | `int`   | Total de reportes en el periodo                     |
| `abiertos`                   | `int`   | Reportes con status `abierto`                       |
| `en_mantenimiento`           | `int`   | Reportes con status `en_mantenimiento`              |
| `finalizados`                | `int`   | Reportes con status `OK`                            |
| `mttr_horas`                 | `float` | MTTR promedio en horas                              |
| `mttr_minutos`               | `float` | MTTR promedio en minutos                            |
| `mtbf_horas`                 | `float` | MTBF promedio en horas                              |
| `downtime_total_horas`       | `float` | Tiempo total de paro en horas                       |
| `reaccion_promedio_minutos`  | `float` | Tiempo promedio entre creación y aceptación          |

#### Ejemplo

```
GET /api/estadisticas/resumen?month=2026-02&area_id=1
```

---

### 3. Gráficas

Datos formateados para alimentar gráficas (barras, líneas, pie). Contiene todos los datasets necesarios.

```
GET /api/estadisticas/graficas
```

**Parámetros:** [Filtros comunes](#filtros-comunes)

#### Respuesta `data`

```json
{
  "top_lineas": [
    { "nombre": "Línea 1", "total_reportes": 25, "downtime_horas": 45.2 }
  ],
  "top_maquinas": [
    { "nombre": "CNC-01", "total_reportes": 12, "downtime_horas": 18.5 }
  ],
  "top_departamentos": [
    { "nombre": "Producción", "count": 40 }
  ],
  "por_turno": [
    { "turno": "A", "total_reportes": 45, "downtime_horas": 52.3 }
  ],
  "mttr_por_maquina": [
    { "nombre": "CNC-01", "mttr_horas": 2.1, "mttr_minutos": 126.0, "total": 12 }
  ],
  "mtbf_por_maquina": [
    { "nombre": "CNC-01", "mtbf_horas": 24.5 }
  ],
  "serie_diaria": [
    {
      "fecha": "2026-02-01",
      "total_reportes": 8,
      "mttr_horas": 1.2,
      "downtime_horas": 9.6
    }
  ],
  "reportes_por_dia": [
    {
      "fecha": "2026-02-01",
      "total": 8,
      "abiertos": 1,
      "en_mantenimiento": 0,
      "finalizados": 7
    }
  ]
}
```

| Dataset              | Tipo gráfica sugerida | Descripción                                      |
|----------------------|-----------------------|--------------------------------------------------|
| `top_lineas`         | Barras horizontal     | Top 10 líneas con más downtime                   |
| `top_maquinas`       | Barras horizontal     | Top 10 máquinas con más downtime                 |
| `top_departamentos`  | Barras / Pie          | Top 10 departamentos con más fallas              |
| `por_turno`          | Barras / Pie          | Downtime y reportes desglosados por turno         |
| `mttr_por_maquina`   | Barras horizontal     | Top 10 máquinas con mayor MTTR                   |
| `mtbf_por_maquina`   | Barras horizontal     | Top 10 máquinas con mayor MTBF                   |
| `serie_diaria`       | Línea temporal        | Serie diaria de MTTR, downtime y reportes         |
| `reportes_por_dia`   | Barras apiladas       | Desglose diario de reportes por status            |

---

### 4. Tendencias

Evolución semanal o mensual de KPIs para análisis de tendencia. Útil para gráficas de línea comparativas.

```
GET /api/estadisticas/tendencias
```

| Parámetro    | Default     | Valores              | Descripción                    |
|--------------|-------------|----------------------|--------------------------------|
| `agrupacion` | `semanal`   | `semanal`, `mensual` | Cómo agrupar los datos         |
| `meses`      | `6`         | Entero positivo      | Cuántos meses hacia atrás      |

**También soporta:** `area_id`, `linea_id`, `turno`, `departamento`, `status`

#### Respuesta `data`

```json
{
  "agrupacion": "semanal",
  "tendencia": [
    {
      "periodo": "2026-W04",
      "total_reportes": 32,
      "mttr_horas": 1.15,
      "downtime_horas": 36.8,
      "finalizados": 30,
      "abiertos": 2
    },
    {
      "periodo": "2026-W05",
      "total_reportes": 28,
      "mttr_horas": 0.98,
      "downtime_horas": 27.4,
      "finalizados": 28,
      "abiertos": 0
    }
  ]
}
```

#### Ejemplo

```
GET /api/estadisticas/tendencias?agrupacion=mensual&meses=12&area_id=2
```

---

### 5. Tiempo Real

Estado actual del sistema: reportes abiertos y en mantenimiento **en este momento**. No requiere filtros de periodo.

```
GET /api/estadisticas/tiempo-real
```

**Sin parámetros.**

#### Respuesta

```json
{
  "app": "mantenimiento-tiempos",
  "timestamp": "2026-02-19T10:30:00-06:00",
  "data": {
    "resumen": {
      "total_activos": 3,
      "abiertos": 2,
      "en_mantenimiento": 1
    },
    "reportes": [
      {
        "id": 4521,
        "status": "abierto",
        "falla": "Fuga de aceite",
        "departamento": "Producción",
        "turno": "1",
        "maquina": "CNC-01",
        "linea": "Línea 1",
        "area": "Maquinados",
        "inicio": "2026-02-19T08:15:00-06:00",
        "aceptado_en": null,
        "tiempo_transcurrido_minutos": 135.0,
        "lider": "Juan Pérez",
        "tecnico": null
      }
    ]
  }
}
```

| Campo reporte                  | Tipo      | Descripción                                          |
|-------------------------------|-----------|------------------------------------------------------|
| `id`                          | `int`     | ID del reporte                                       |
| `status`                      | `string`  | `abierto` o `en_mantenimiento`                       |
| `falla`                       | `string`  | Descripción de la falla                              |
| `departamento`                | `string`  | Departamento que reportó                             |
| `turno`                       | `string`  | Turno: `1`=A, `2`=B, `3`=C                          |
| `maquina`                     | `string`  | Nombre de la máquina                                 |
| `linea`                       | `string`  | Nombre de la línea                                   |
| `area`                        | `string`  | Nombre del área                                      |
| `inicio`                      | `string`  | Fecha/hora inicio (ISO 8601)                         |
| `aceptado_en`                 | `string?` | Fecha/hora aceptación (null si no aceptado)          |
| `tiempo_transcurrido_minutos` | `float`   | Minutos desde que se abrió hasta ahora               |
| `lider`                       | `string`  | Nombre del líder que reportó                         |
| `tecnico`                     | `string?` | Nombre del técnico asignado                          |

---

### 6. Por Área

Estadísticas desglosadas por cada área de la planta.

```
GET /api/estadisticas/areas
```

**Parámetros:** [Filtros comunes](#filtros-comunes)

#### Respuesta `data`

```json
{
  "por_area": [
    {
      "area": "Maquinados",
      "total_reportes": 45,
      "abiertos": 2,
      "en_mantenimiento": 1,
      "finalizados": 42,
      "downtime_horas": 67.5,
      "mttr_horas": 1.5
    },
    {
      "area": "Ensamble",
      "total_reportes": 30,
      "abiertos": 0,
      "en_mantenimiento": 0,
      "finalizados": 30,
      "downtime_horas": 22.3,
      "mttr_horas": 0.74
    }
  ]
}
```

---

### 7. Herramentales

Estadísticas específicas de fallas en herramentales.

```
GET /api/estadisticas/herramentales
```

**Parámetros:** [Filtros de periodo](#filtros-de-periodo)

#### Respuesta `data`

```json
{
  "resumen": {
    "total_fallas": 85,
    "mttr_minutos": 45.2,
    "downtime_total_minutos": 3842.0,
    "downtime_total_horas": 64.03
  },
  "top_herramentales": [
    {
      "herramental": "Molde A-123",
      "total_fallos": 12,
      "downtime_total_minutos": 540.0,
      "downtime_promedio_minutos": 45.0
    }
  ],
  "por_maquina": [
    {
      "maquina": "CNC-01",
      "linea": "Línea 1",
      "area": "Maquinados",
      "total_fallas": 8,
      "downtime_minutos": 360.0
    }
  ]
}
```

---

### 8. Técnicos

Rendimiento de cada técnico de mantenimiento.

```
GET /api/estadisticas/tecnicos
```

**Parámetros:** [Filtros comunes](#filtros-comunes)

#### Respuesta `data`

```json
{
  "por_tecnico": [
    {
      "tecnico": "Carlos López",
      "total_asignados": 35,
      "finalizados": 33,
      "en_proceso": 2,
      "mttr_promedio_horas": 1.1,
      "mttr_promedio_minutos": 66.0
    },
    {
      "tecnico": "Miguel Torres",
      "total_asignados": 28,
      "finalizados": 28,
      "en_proceso": 0,
      "mttr_promedio_horas": 0.85,
      "mttr_promedio_minutos": 51.0
    }
  ]
}
```

---

### 9. Catálogos

Devuelve los catálogos disponibles para poblar filtros (selects, dropdowns) en el frontend del dashboard centralizado.

```
GET /api/estadisticas/catalogos
```

**Sin parámetros.**

#### Respuesta

```json
{
  "app": "mantenimiento-tiempos",
  "data": {
    "areas": [
      { "id": 1, "name": "Maquinados" },
      { "id": 2, "name": "Ensamble" }
    ],
    "lineas": [
      { "id": 1, "name": "Línea 1", "area_id": 1, "area": { "id": 1, "name": "Maquinados" } }
    ],
    "maquinas": [
      { "id": 1, "name": "CNC-01", "linea_id": 1, "linea": { "id": 1, "name": "Línea 1", "area_id": 1 } }
    ],
    "departamentos": ["Calidad", "Logística", "Producción"],
    "turnos": ["1", "2", "3"]
  }
}
```

---

## Ejemplos de Integración

### Desde tu dashboard centralizado (JavaScript/fetch)

```javascript
const BASE_URL = 'https://tu-servidor.com/api/estadisticas';

// 1. Health check — verificar que la app está viva
const health = await fetch(`${BASE_URL}/health`).then(r => r.json());
console.log(health.status); // "ok"

// 2. KPIs del mes actual
const resumen = await fetch(`${BASE_URL}/resumen?month=2026-02`).then(r => r.json());
console.log(resumen.data.kpis.mttr_horas); // 1.25

// 3. Datos para gráficas de la semana
const graficas = await fetch(`${BASE_URL}/graficas?week=2026-W08`).then(r => r.json());
// graficas.data.top_maquinas → Barras
// graficas.data.serie_diaria → Línea temporal

// 4. Tendencia mensual de los últimos 12 meses
const tend = await fetch(`${BASE_URL}/tendencias?agrupacion=mensual&meses=12`).then(r => r.json());
// tend.data.tendencia → Array de {periodo, total_reportes, mttr_horas, ...}

// 5. Reportes activos en tiempo real (polling cada 30s)
setInterval(async () => {
  const rt = await fetch(`${BASE_URL}/tiempo-real`).then(r => r.json());
  actualizarPanel(rt.data.resumen, rt.data.reportes);
}, 30000);

// 6. Cargar catálogos para filtros
const catalogos = await fetch(`${BASE_URL}/catalogos`).then(r => r.json());
poblarSelect('areas', catalogos.data.areas);
poblarSelect('lineas', catalogos.data.lineas);
```

### Desde tu dashboard centralizado (Axios)

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'https://tu-servidor.com/api/estadisticas',
  headers: { 'Accept': 'application/json' }
});

// Llamada paralela para cargar todo el dashboard de inicio
const [resumen, graficas, catalogos] = await Promise.all([
  api.get('/resumen', { params: { month: '2026-02' } }),
  api.get('/graficas', { params: { month: '2026-02' } }),
  api.get('/catalogos'),
]);

// Acceso a los datos
const kpis = resumen.data.data.kpis;
const charts = graficas.data.data;
const filtros = catalogos.data.data;
```

---

## Glosario de Métricas

| Métrica     | Significado                                  | Cálculo                                                                 |
|-------------|----------------------------------------------|-------------------------------------------------------------------------|
| **MTTR**    | Mean Time To Repair (Tiempo Medio de Reparación) | Promedio de `(fin - aceptado_en)` de reportes finalizados                |
| **MTBF**    | Mean Time Between Failures (Tiempo Medio Entre Fallas) | Promedio de `(inicio[n+1] - fin[n])` por máquina                         |
| **Downtime**| Tiempo total de paro                         | Suma de `(fin - inicio)` de todos los reportes                          |
| **Reacción**| Tiempo de reacción                           | Promedio de `(aceptado_en - inicio)` — cuánto tardaron en aceptar        |

### Estados de un Reporte

| Status              | Descripción                                    |
|---------------------|------------------------------------------------|
| `abierto`           | Reporte creado, esperando que un técnico acepte |
| `en_mantenimiento`  | Técnico aceptó, trabajando en la reparación     |
| `OK`                | Reparación finalizada                           |

### Turnos

| Valor | Etiqueta |
|-------|----------|
| `1`   | A        |
| `2`   | B        |
| `3`   | C        |

---

## Mapa de Rutas

```
GET /api/estadisticas/
├── health              → Estado del servicio
├── resumen             → KPIs principales
├── graficas            → Datos para charts
├── tendencias          → Evolución semanal/mensual
├── tiempo-real         → Reportes activos ahora
├── areas               → Desglose por área
├── herramentales       → Stats de herramentales
├── tecnicos            → Rendimiento por técnico
└── catalogos           → Catálogos para filtros
```
