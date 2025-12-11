# Documentación General - Sistema de Gestión de Reportes de Mantenimiento

**Fecha:** Diciembre de 2025  
**Versión:** 1.0  
**Equipo:** Desarrollo Backend - Mantenimiento de Tiempos GST

---

## 📋 Tabla de Contenidos

1. [Descripción General del Sistema](#descripción-general-del-sistema)
2. [Conceptos Clave](#conceptos-clave)
3. [Arquitectura del Sistema](#arquitectura-del-sistema)
4. [Flujo de Datos](#flujo-de-datos)
5. [Documentación Técnica Detallada](#documentación-técnica-detallada)
6. [Guía de Uso de la API](#guía-de-uso-de-la-api)

---

## Descripción General del Sistema

### ¿Qué hace este programa?

Este es un **sistema de gestión de reportes de mantenimiento** para plantas de producción. Su propósito es registrar, rastrear y analizar las fallas en maquinaria con el objetivo de:

- **Reportar problemas**: Los líderes de línea reportan cuando una máquina falla
- **Asignar técnicos**: Los técnicos aceptan los reportes para ir a reparar
- **Documentar soluciones**: Se registra qué se hizo para resolver el problema
- **Analizar tiempos**: Medir cuánto tiempo tomó reaccionar, cuánto tiempo de mantenimiento, y cuánto tiempo total de paro

### Caso de uso típico

1. **Juana (líder de línea)** está en la línea de producción y ve que una máquina se paró
2. **Juana crea un reporte** diciendo "Atoron de maquina, suena extraño"
3. **El sistema registra** la hora exacta (inicio del paro)
4. **Carlos (técnico)** ve el reporte y lo acepta (dice "voy para allá")
5. **El sistema registra** la hora de aceptación (tiempo de reacción)
6. **Carlos arregla la máquina** (cambió un sensor, por ejemplo)
7. **Carlos cierra el reporte** diciendo "Se cambió el sensor defectuoso"
8. **El sistema registra** la hora de cierre (fin del paro)
9. **Se calcula automáticamente**:
   - Tiempo de reacción: desde que Juana reportó hasta que Carlos aceptó
   - Tiempo de mantenimiento: desde que Carlos aceptó hasta que cerró
   - Tiempo total de paro: desde que se reportó hasta que se cerró
10. **Los gerentes pueden consultar** gráficas y reportes para ver cuáles máquinas fallan más, qué línea es más rápida en reparaciones, etc.

---

## Conceptos Clave

### Entidades principales

#### 1. **Áreas** 
Lugares dentro de la planta donde hay máquinas. Ejemplos: "Costura", "Corte", "Estampado"

#### 2. **Líneas**
Cadenas de producción dentro de un área. Ejemplo: "Costura - Línea 1", "Costura - Línea 2"

#### 3. **Máquinas**
Equipos específicos dentro de una línea. Ejemplo: "Máquina de Coser #5", "Cortadora Laser A"

#### 4. **Usuarios**
Personas que interactúan con el sistema:
- **Líder**: Reporta fallas (crea reportes)
- **Técnico**: Repara máquinas (acepta y cierra reportes)

#### 5. **Reportes**
El registro de una falla y su resolución. Contiene:
- Qué pasó (descripción de la falla)
- Cuándo pasó (timestamps)
- Quién lo reportó (líder)
- Quién lo reparó (técnico)
- Cómo se resolvió (descripción de resultado)

### Estados de un Reporte

Un reporte tiene un **ciclo de vida**:

```
ABIERTO → EN_MANTENIMIENTO → OK
```

- **ABIERTO**: Acaba de ser creado por un líder. Está esperando que un técnico lo acepte
- **EN_MANTENIMIENTO**: Un técnico ya lo aceptó y está trabajando en repararlo
- **OK**: El técnico finalizó la reparación

### Turnos

El sistema registra en qué turno ocurrió el problema:
- **Turno 1**: 07:00 - 15:00
- **Turno 2**: 15:00 - 23:00
- **Turno 3**: 23:00 - 07:00 (siguiente día)

### Restricción de 15 minutos

**Regla importante**: Un líder NO puede crear dos reportes de la misma máquina en menos de 15 minutos **MIENTRAS el primer reporte esté activo** (abierto o en mantenimiento).

Esto previene duplicación de reportes. Sin embargo:
- Si pasaron 15+ minutos desde el primer reporte activo → sí se permite un nuevo
- Si el primer reporte fue cerrado (estado OK) → sí se permite un nuevo reporte inmediatamente

---

## Arquitectura del Sistema

### Stack tecnológico

- **Backend**: PHP 8.4 + Laravel 11
- **Base de datos**: MySQL
- **API**: RESTful JSON
- **Formato de fechas**: ISO 8601 (UTC)
- **Zona horaria**: America/Mexico_City (CST/CDT)

### Estructura de carpetas

```
app/
├── Http/
│   └── Controllers/
│       ├── ReporteController.php     ← Lógica de reportes
│       ├── UserController.php        ← Lógica de usuarios
│       ├── AreaController.php        ← Lógica de áreas
│       ├── LineaController.php       ← Lógica de líneas
│       └── MaquinaController.php     ← Lógica de máquinas
├── Models/
│   ├── Reporte.php
│   ├── User.php
│   ├── Area.php
│   ├── Linea.php
│   └── Maquina.php
├── Events/                           ← Eventos para notificaciones (websockets)
└── Exports/
    └── ReportesExport.php            ← Generador de Excel
    
database/
├── migrations/
│   ├── create_areas_table.php
│   ├── create_lineas_table.php
│   ├── create_maquinas_table.php
│   ├── create_users_table.php
│   └── create_reportes_table.php
└── seeders/
    └── AreasLineasMaquinasSeeder.php ← Datos de prueba
    
routes/
└── api.php                           ← Definición de todas las rutas

resources/
├── views/
│   ├── welcome.blade.php
│   └── graficas/
│       └── index.blade.php           ← Dashboard de KPIs
        
docs/
├── api.md                            ← Referencia técnica de endpoints
└── DOCUMENTACION_GENERAL.md          ← Este archivo
```

### Modelo de datos (relaciones)

```
┌─────────────────────────────────────┐
│            AREAS (áreas)             │
│  id | name | created_at | updated_at│
└──────────────┬──────────────────────┘
               │ 1 (uno a muchos)
               │
       ┌───────▼──────┐
       │    LINEAS    │
       │ id | area_id │
       └───────┬──────┘
               │ 1 (uno a muchos)
               │
       ┌───────▼───────┐
       │  MAQUINAS     │
       │ id | linea_id │
       └───────┬───────┘
               │ 1 (muchos a uno)
               │
    ┌──────────┴──────────┐
    │                     │
┌───▼──────────────────────────────┐       ┌──────────────┐
│          REPORTES                │       │    USERS     │
│ id | maquina_id | area_id        │   ◄───│ employee_no  │
│ | employee_number (fk→Users)     │───┐   │ | name       │
│ | tecnico_employee_number (fk)   │───┼──►│ | role       │
│ | status | inicio | fin | ...    │   │   │ | turno      │
└──────────────────────────────────┘   │   └──────────────┘
                                       │
         (apunta a la misma tabla)─────┘
```

---

## Flujo de Datos

### 1. Setup Inicial

```
Administrador
    ↓
Crea Áreas (ej: Costura, Corte)
    ↓
Crea Líneas dentro de cada Área (ej: Línea 1, Línea 2)
    ↓
Crea Máquinas dentro de cada Línea (ej: Máquina A, Máquina B)
    ↓
Crea Usuarios con roles: Líderes y Técnicos
    ↓
Sistema listo para reportes
```

### 2. Creación de Reporte (Flujo Normal)

```
Líder (Juana) detecta falla
    ↓
POST /reportes
{
  employee_number: 7218,
  maquina_id: 5,
  turno: "1",
  descripcion_falla: "Se detuvo el transportador"
}
    ↓
Backend valida:
  - ¿Existe el líder?
  - ¿Existe la máquina?
  - ¿No hay otro reporte activo en los últimos 15 min?
    ↓
Se crea Reporte con:
  - status = "abierto"
  - inicio = ahora
  - employee_number = 7218
    ↓
Retorna: { id: 42, status: "abierto", inicio: "2025-12-04T08:30:00", ... }
    ↓
Cliente (app/web) muestra: "Reporte creado, esperando técnico..."
```

### 3. Aceptación de Reporte (Técnico toma el trabajo)

```
Técnico (Carlos) ve reporte
    ↓
POST /reportes/42/aceptar
{
  tecnico_employee_number: 4321
}
    ↓
Backend valida:
  - ¿Es un técnico válido?
  - ¿El reporte no fue aceptado ya?
  - ¿El reporte no está ya finalizado?
    ↓
Se actualiza Reporte:
  - status = "en_mantenimiento"
  - aceptado_en = ahora
  - tecnico_employee_number = 4321
    ↓
Retorna: { status: "en_mantenimiento", aceptado_en: "2025-12-04T08:35:00", ... }
    ↓
Tiempo de reacción calculado automáticamente: 5 minutos
```

### 4. Cierre de Reporte (Se resolvió el problema)

```
Técnico (Carlos) termina de reparar
    ↓
POST /reportes/42/finalizar
{
  descripcion_resultado: "Se cambió el sensor defectuoso",
  refaccion_utilizada: "Sensor X modelo RS-500",
  departamento: "Mantenimiento"
}
    ↓
Backend valida:
  - ¿El reporte no está ya finalizado?
    ↓
Se actualiza Reporte:
  - status = "OK"
  - fin = ahora
  - descripcion_resultado = ...
  - refaccion_utilizada = ...
    ↓
Se calculan automáticamente:
  - tiempo_reaccion_segundos = aceptado_en - inicio
  - tiempo_mantenimiento_segundos = fin - aceptado_en
  - tiempo_total_segundos = fin - inicio
    ↓
Retorna: { status: "OK", fin: "2025-12-04T08:45:00", tiempo_total_segundos: 900 }
    ↓
Cliente muestra: "Reporte cerrado. Total 15 minutos."
```

### 5. Análisis y Reportes

```
Gerente (o sistema de BI)
    ↓
GET /reportes?from=2025-12-01&to=2025-12-04&area_id=1
    ↓
Filtra todos los reportes del período en el área 1
    ↓
Calcula:
  - Promedio de MTTR (Mean Time To Repair)
  - Promedio de MTBF (Mean Time Between Failures)
  - Máquina con más fallas
  - Línea más lenta en reparaciones
    ↓
Genera gráficas y reportes Excel
```

---

## Documentación Técnica Detallada

### Modelo de Base de Datos

#### Tabla `users`
```
id (autoincrement) - NO SE USA
employee_number (4 dígitos, PRIMARY KEY)
name (string)
role (tecnico | lider)
turno (1 | 2 | 3)
created_at, updated_at
```

**Nota importante**: `employee_number` es la clave primaria lógica, no `id`.

#### Tabla `areas`
```
id (PRIMARY KEY)
name (string)
created_at, updated_at
```

#### Tabla `lineas`
```
id (PRIMARY KEY)
name (string)
area_id (FOREIGN KEY → areas.id)
created_at, updated_at
```

#### Tabla `maquinas`
```
id (PRIMARY KEY)
name (string, UNIQUE)
linea_id (FOREIGN KEY → lineas.id)
created_at, updated_at
```

#### Tabla `reportes`
```
id (PRIMARY KEY)
status (abierto | en_mantenimiento | OK)
falla (string)
departamento (nullable string)
turno (1 | 2 | 3)
descripcion_falla (text)
descripcion_resultado (text, nullable)
refaccion_utilizada (nullable string)
area_id (FOREIGN KEY → areas.id)
maquina_id (FOREIGN KEY → maquinas.id)
employee_number (FOREIGN KEY → users.employee_number)
tecnico_employee_number (FOREIGN KEY → users.employee_number, nullable)
aceptado_en (datetime, nullable)
inicio (datetime) ← marca el inicio del paro
fin (datetime, nullable) ← marca el cierre
created_at, updated_at
```

### Validaciones Clave

#### Al crear reporte
- `employee_number` debe existir en usuarios con rol "lider"
- `maquina_id` debe existir
- `turno` es requerido
- `descripcion_falla` es requerido
- **No puede existir otro reporte ACTIVO** (status = abierto o en_mantenimiento) de la misma máquina dentro de los últimos 15 minutos

#### Al aceptar reporte
- `tecnico_employee_number` debe existir en usuarios con rol "tecnico"
- El reporte no debe estar ya aceptado
- El reporte no debe estar finalizado (status = OK)

#### Al finalizar reporte
- `descripcion_resultado` es requerido
- `departamento` es requerido
- El reporte no debe estar ya finalizado

### Cálculos Automáticos

Los tiempos se calculan automáticamente en segundos:

```
tiempo_reaccion_segundos = aceptado_en - inicio
tiempo_mantenimiento_segundos = fin - aceptado_en
tiempo_total_segundos = fin - inicio
```

Si alguno de estos timestamps no existe, el campo es `null`.

### Filtros de Reportes

El endpoint `GET /reportes` soporta filtros avanzados:

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | csv | IDs específicos de reportes (ej: 1,5,10) |
| `status` | csv | Estados (abierto, en_mantenimiento, OK, o alias: ok, mtto) |
| `turno` | csv | Turnos (1, 2, 3) |
| `area_id` | csv | Área(s) específica(s) |
| `maquina_id` | csv | Máquina(s) específica(s) |
| `linea_id` | csv | Línea(s) específica(s) |
| `employee_number` | csv | Líder(es) específico(s) |
| `tecnico_employee_number` | csv | Técnico(s) específico(s) |
| `has_tecnico` | bool | true = tiene técnico, false = sin técnico |
| `has_fin` | bool | true = finalizado, false = abierto |
| `q` | string | Búsqueda de texto libre (falla, descripciones, nombres) |
| `day` | YYYY-MM-DD | Día específico (ventana 7:00 AM → 7:00 AM siguiente día) |
| `from` | YYYY-MM-DD | Fecha inicial |
| `to` | YYYY-MM-DD | Fecha final |
| `hour_from` | 0-23 | Hora inicial del día |
| `hour_to` | 0-23 | Hora final del día |
| `shift` | 1\|2\|3 | Turno (1=07-15, 2=15-23, 3=23-07) |
| `sort_by` | string | Campo de orden (inicio, aceptado_en, fin, status, etc.) |
| `sort_dir` | asc\|desc | Dirección de orden |
| `paginate` | bool | Activar paginación (por defecto: false, lista completa) |
| `per_page` | int | Registros por página (defecto: 15) |
| `select` | csv | Columnas específicas para payload ligero |

### Formatos de Fecha

- **Entrada**: `YYYY-MM-DD` para día, `YYYY-MM-DD HH:MM:SS` para hora exacta
- **Salida**: ISO 8601 con zona horaria (ej: `2025-12-04T08:30:00-06:00`)
- **Zona horaria**: America/Mexico_City (UTC-6 o UTC-5 en horario de verano)

---

## Guía de Uso de la API

### Configuración Inicial

#### 1. Crear Áreas

```bash
POST /api/areas
Content-Type: application/json

{
  "name": "Costura"
}
```

Respuesta:
```json
{
  "id": 1,
  "name": "Costura",
  "created_at": "2025-12-04T08:00:00",
  "updated_at": "2025-12-04T08:00:00"
}
```

#### 2. Crear Líneas

```bash
POST /api/lineas
Content-Type: application/json

{
  "name": "Línea 1",
  "area_id": 1
}
```

#### 3. Crear Máquinas

```bash
POST /api/maquinas
Content-Type: application/json

{
  "name": "Máquina A",
  "linea_id": 1
}
```

#### 4. Crear Usuarios

```bash
POST /api/user
Content-Type: application/json

{
  "employee_number": 7218,
  "name": "Juan Líder",
  "role": "lider",
  "turno": "1"
}

{
  "employee_number": 4321,
  "name": "Carlos Técnico",
  "role": "tecnico",
  "turno": "1"
}
```

### Flujo de Reportes

#### 1. Crear Reporte (Líder)

```bash
POST /api/reportes
Content-Type: application/json

{
  "employee_number": 7218,
  "maquina_id": 1,
  "turno": "1",
  "descripcion_falla": "Se detuvo el transportador principal"
}
```

Respuesta 201:
```json
{
  "id": 42,
  "status": "abierto",
  "employee_number": 7218,
  "lider_nombre": "Juan Líder",
  "tecnico_employee_number": null,
  "tecnico_nombre": null,
  "area_id": 1,
  "area_nombre": "Costura",
  "linea_id": 1,
  "linea_nombre": "Línea 1",
  "maquina_id": 1,
  "maquina_nombre": "Máquina A",
  "turno": "1",
  "falla": "por definir",
  "departamento": null,
  "descripcion_falla": "Se detuvo el transportador principal",
  "descripcion_resultado": "",
  "refaccion_utilizada": null,
  "inicio": "2025-12-04T08:30:00-06:00",
  "aceptado_en": null,
  "fin": null,
  "tiempo_reaccion_segundos": null,
  "tiempo_mantenimiento_segundos": null,
  "tiempo_total_segundos": null
}
```

#### 2. Aceptar Reporte (Técnico)

```bash
POST /api/reportes/42/aceptar
Content-Type: application/json

{
  "tecnico_employee_number": 4321
}
```

Respuesta 200:
```json
{
  ...mismo reporte...,
  "status": "en_mantenimiento",
  "tecnico_employee_number": 4321,
  "tecnico_nombre": "Carlos Técnico",
  "aceptado_en": "2025-12-04T08:35:00-06:00"
}
```

#### 3. Finalizar Reporte (Técnico)

```bash
POST /api/reportes/42/finalizar
Content-Type: application/json

{
  "descripcion_resultado": "Se cambió el sensor defectuoso, ajusté la tensión",
  "refaccion_utilizada": "Sensor X modelo RS-500",
  "departamento": "Mantenimiento Preventivo"
}
```

Respuesta 200:
```json
{
  ...mismo reporte...,
  "status": "OK",
  "descripcion_resultado": "Se cambió el sensor defectuoso, ajusté la tensión",
  "refaccion_utilizada": "Sensor X modelo RS-500",
  "departamento": "Mantenimiento Preventivo",
  "fin": "2025-12-04T08:45:00-06:00",
  "tiempo_reaccion_segundos": 300,
  "tiempo_mantenimiento_segundos": 600,
  "tiempo_total_segundos": 900
}
```

### Consultas Comunes

#### Listar todos los reportes del día

```bash
GET /api/reportes?day=2025-12-04
```

#### Listar reportes activos (sin cerrar)

```bash
GET /api/reportes?status=abierto,en_mantenimiento&has_fin=false
```

#### Listar reportes de una área específica

```bash
GET /api/areas/1/reportes
```

#### Listar reportes de la última semana

```bash
GET /api/reportes?from=2025-11-27&to=2025-12-04
```

#### Exportar reportes a Excel

```bash
GET /api/reportes/exportarexcel?day=2025-12-04&area_id=1
```

Descarga un archivo `historial_reportes.xlsx` con todos los filtros aplicados.

#### Buscar reportes por texto

```bash
GET /api/reportes?q=sensor%20defectuoso
```

Busca "sensor defectuoso" en descripción de falla, resultado, refacción, etc.

### Manejo de Errores

#### Error de Validación (422)

```json
{
  "field": [
    "El campo es requerido"
  ]
}
```

#### Error de Reporte Duplicado (422)

```json
{
  "message": "Ya existe un reporte activo para esta máquina en los últimos 15 minutos."
}
```

#### Error de Conflicto (409)

```json
{
  "message": "El reporte ya fue aceptado por un técnico."
}
```

#### Error de Recurso No Encontrado (404)

```json
{
  "message": "Recurso no encontrado."
}
```

---

## Tablas de Referencia Rápida

### Estados de Reporte

| Estado | Descripción | Quién actúa | Siguiente |
|--------|-------------|-------------|-----------|
| `abierto` | Recién creado, esperando técnico | Técnico | `en_mantenimiento` |
| `en_mantenimiento` | Técnico está reparando | Técnico | `OK` |
| `OK` | Reparación completada | - | - |

### Roles de Usuario

| Rol | Acciones | Restricciones |
|-----|----------|---|
| `lider` | Crear reportes | Solo puede crear (POST /reportes) |
| `tecnico` | Aceptar y cerrar reportes | Solo puede aceptar y cerrar |

### Turnos

| Turno | Horario |
|-------|---------|
| 1 | 07:00 - 15:00 |
| 2 | 15:00 - 23:00 |
| 3 | 23:00 - 07:00 |

---

## Notas Importantes para Integraciones

1. **Siempre incluir headers**:
   ```
   Accept: application/json
   Content-Type: application/json
   ```

2. **Las fechas vienen en ISO 8601** con zona horaria. Para mostrar en la interfaz local, considera convertir a formato local (MM/DD/YYYY HH:MM).

3. **La restricción de 15 minutos** previene duplicación pero permite:
   - Nuevo reporte si pasaron 15+ minutos
   - Nuevo reporte si el anterior está en estado OK

4. **Para listas grandes**, usar `paginate=true` y `per_page=20` para evitar sobrecargar el servidor.

5. **Los cálculos de tiempo son en segundos**. Para mostrar en minutos u horas, dividir entre 60 o 3600 respectivamente.

6. **El campo `employee_number`** es único por usuario y se usa como clave primaria en la tabla de usuarios. No es autoincrement.

---

## Preguntas Frecuentes Técnicas

### ¿Qué pasa si un técnico no cierra un reporte?
El reporte queda en estado `en_mantenimiento` indefinidamente. Los gerentes pueden filtrar por `has_fin=false` para ver reportes sin cerrar.

### ¿Se puede reasignar un reporte a otro técnico?
No, una vez que un técnico acepta, no se puede cambiar. Se requeriría una funcionalidad adicional.

### ¿Se puede editar un reporte una vez cerrado?
No, el sistema no permite editar reportes cerrados (status = OK).

### ¿Qué pasa con un reporte si se elimina la máquina?
La base de datos está configurada con `onDelete('cascade')`, así que se eliminaría el reporte también.

### ¿Se puede crear un reporte para una máquina que ya tiene uno cerrado?
Sí, siempre y cuando el reporte anterior esté en estado OK. La restricción de 15 minutos solo aplica a reportes ACTIVOS.

---

## Contacto y Soporte

Para dudas o aclaraciones sobre esta documentación, consultar con el equipo de desarrollo original o revisar el código fuente en los controllers correspondientes.

**Última actualización**: Diciembre 2025
