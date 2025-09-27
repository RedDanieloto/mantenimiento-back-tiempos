# API TiempoMuertoGST

Base URL: http://127.0.0.1:8000/api

Estado de auth: Actualmente no hay autenticación en los endpoints documentados.

Formato: JSON (UTF-8). Enviar siempre los headers:
- Accept: application/json
- Content-Type: application/json

Zona horaria: America/Mexico_City. Todos los filtros de fecha en reportes se aplican sobre el campo inicio usando ventana 7:00 → 6:59 del siguiente día.

Estatus de reporte: abierto | en_mantenimiento | OK.

Turnos: 'A' | 'B' | 'C' esos en costura.
En corte es 'TRNA, TRNB, TRNC, TRND

Usuarios: El identificador del usuario es employee_number (entero de 4 dígitos). No existe un id autoincrement convencional.

---

## Índice rápido
- Usuarios: GET/POST/GET:id/PUT:id/DELETE:id
- Áreas: GET/POST/GET:id/PUT:id/DELETE:id
- Líneas: GET/POST/GET:id/PUT:id/DELETE:id
- Máquinas: GET/POST/GET:id/PUT:id/DELETE:id
- Reportes:
  - GET /reportes (lista + filtros + paginación opcional)
  - GET /reportes/lookup (autocompletado de máquina/línea/usuarios)
  - GET /reportes/exportarexcel (export global con mismos filtros)
  - POST /reportes (crear por líder, bloqueo 15 min)
  - POST /reportes/{id}/aceptar (técnico toma)
  - POST /reportes/{id}/finalizar (técnico cierra)
  - Scope por área:
    - GET /areas/{area}/reportes
    - POST /areas/{area}/reportes
    - GET /areas/{area}/reportes/exportarexcel (XLSX)

Interfaz Blade (KPIs):
- GET /graficas (página con filtros y gráficas MTTR/MTBF, top 10, etc.)
  - Soporta query params: day=YYYY-MM-DD, from=YYYY-MM-DD, to=YYYY-MM-DD, month=YYYY-MM, area_id, linea_id, turno (1|2|3)
  - La ventana de día es 7:00 → 7:00 del siguiente día.

---

## Convenciones de respuesta de error
- 422 Validation Error
  {
    "field": ["mensaje de validación"],
    ...
  }
- 404 Not Found
  { "message": "Recurso no encontrado." }
- 409 Conflict (flujo de reportes)
  { "message": "El reporte ya fue aceptado por un técnico." }

---

## Usuarios

### GET /user
Lista usuarios.

Ejemplo respuesta 200:
[
  { "employee_number": 7218, "name": "Admin", "role": "lider", "turno": "1", "created_at": "...", "updated_at": "..." }
]

### POST /user
Crea usuario.

Body JSON:
{
  "employee_number": 1234,
  "name": "Juan Pérez",
  "role": "tecnico",  // tecnico | lider
  "turno": "2"         // '1' | '2' | '3'
}

Respuesta 201:
{
  "message": "Usuario creado correctamente.",
  "usuario": { "employee_number": 1234, "name": "Juan Pérez", "role": "tecnico", "turno": "2", "created_at": "...", "updated_at": "..." }
}

Errores 422 posibles: employee_number ya existe, tipos, formato de campos.

### GET /user/{id}
Obtiene un usuario por employee_number.

Respuesta 200: { "employee_number": 1234, "name": "...", "role": "...", "turno": "..." }

404 si no existe.

### PUT /user/{id}
Actualiza name, role y/o turno.

Body (uno o varios): { "name": "...", "role": "tecnico|lider", "turno": "1|2|3" }

Respuesta 200:
{ "message": "Usuario actualizado correctamente.", "usuario": { ... } }

### DELETE /user/{id}
Elimina usuario. Respuesta 200: { "message": "Usuario eliminado correctamente." }

---

## Áreas

### GET /areas
Devuelve lista de áreas.

### POST /areas
Body: { "name": "Producción" }

201: { "id": 1, "name": "Producción", ... }

### GET /areas/{area}
Detalle de un área.

### PUT /areas/{area}
Body: { "name": "Nuevo nombre" }

### DELETE /areas/{area}
Elimina área.

---

## Líneas

### GET /lineas
Lista con include de área: cada línea incluye area si el controlador lo carga con with('area').

Ejemplo 200:
[
  { "id": 1, "name": "Línea 1", "area_id": 1, "area": { "id": 1, "name": "Producción" } }
]

### POST /lineas
Body: { "name": "Línea 2", "area_id": 1 }

201:
{ "message": "Línea creada correctamente.", "linea": { "id": 2, "name": "Línea 2", "area_id": 1, ... } }

### GET /lineas/{linea}
Detalle con relaciones: area y maquinas.

Ejemplo 200:
{ "id": 1, "name": "Línea 1", "area_id": 1, "area": { ... }, "maquinas": [ { "id": 1, "name": "Máquina A" } ] }

### PUT /lineas/{linea}
Body: { "name": "Línea 1A", "area_id": 1 }

200: Objeto línea actualizado.

### DELETE /lineas/{linea}
200: { "message": "Línea eliminada correctamente." }

---

## Máquinas

### GET /maquinas
Lista máquinas.

### POST /maquinas
Body: { "name": "Máquina A", "linea_id": 1 }

201: { "id": 1, "name": "Máquina A", "linea_id": 1, ... }

### GET /maquinas/{maquina}
Detalle.

### PUT /maquinas/{maquina}
Body: { "name": "Máquina A2", "linea_id": 1 }

### DELETE /maquinas/{maquina}
Elimina.

---

## Reportes

Entidad clave del sistema. Campos principales en respuestas:
- id
- status: abierto | en_mantenimiento | OK
- employee_number, lider_nombre
- tecnico_employee_number, tecnico_nombre (cuando aplique)
- area_id/area_nombre, linea_id/linea_nombre, maquina_id/maquina_nombre
- turno
- falla, departamento
- descripcion_falla, descripcion_resultado, refaccion_utilizada
- inicio, aceptado_en, fin (ISO8601)
- tiempo_reaccion_segundos, tiempo_mantenimiento_segundos, tiempo_total_segundos

### GET /reportes
Lista con filtros y orden. Por defecto ordena por inicio desc.

Query params soportados:
- id: csv de ids específicos
- status: csv. Acepta alias [ok, abierto, mtto|en_mantenimiento]
- turno: csv de '1','2','3'
- area_id: csv de ids
- maquina_id: csv de ids
- linea_id: csv de ids (filtra vía relación máquina.linea)
- employee_number: csv (líder)
- tecnico_employee_number: csv
- has_tecnico: true|false (filtra por presencia de técnico asignado)
- has_fin: true|false (filtra por reportes finalizados o no)
- q: texto libre; busca en falla, descripciones, refacción, nombres, máquina/línea/área
- day: YYYY-MM-DD (ventana 7am→7am, aplicado sobre inicio)
- from, to: YYYY-MM-DD; misma ventana 7am→7am
- hour_from, hour_to: 0-23; filtra por hora del campo inicio
- shift: '1'|'2'|'3' según franjas: 1=07-15, 2=15-23, 3=23-07 (aplica sobre inicio)
- sort_by: inicio | aceptado_en | fin | status | maquina_id | area_id
- sort_dir: asc | desc
- paginate: true|false (si true, devuelve formato de paginación de Laravel)
- per_page: entero (por defecto 15)
- select: csv de columnas para payload liviano (ej: id,status,inicio,maquina_id)

Respuesta 200 (sin paginación):
[
  {
    "id": 10,
    "status": "abierto",
    "employee_number": 7218,
    "lider_nombre": "Admin",
    "tecnico_employee_number": null,
    "tecnico_nombre": null,
    "area_id": 1,
    "area_nombre": "Producción",
    "linea_id": 1,
    "linea_nombre": "Línea 1",
    "maquina_id": 1,
    "maquina_nombre": "Máquina A",
    "turno": "1",
    "falla": "por definir",
    "departamento": null,
    "descripcion_falla": "Se detuvo el transportador",
    "descripcion_resultado": "",
    "refaccion_utilizada": null,
    "inicio": "2025-09-26T08:12:31+00:00",
    "aceptado_en": null,
    "fin": null,
    "tiempo_reaccion_segundos": null,
    "tiempo_mantenimiento_segundos": null,
    "tiempo_total_segundos": null
  }
]

Si paginate=true, estructura:
{
  "current_page": 1,
  "data": [ ...misma estructura de reporte... ],
  "first_page_url": "...",
  "from": 1,
  "last_page": 10,
  "last_page_url": "...",
  "links": [ ... ],
  "next_page_url": "...",
  "path": ".../api/reportes",
  "per_page": 15,
  "prev_page_url": null,
  "to": 15,
  "total": 150
}

### POST /reportes
Crea reporte (líder). Aplica bloqueo: no permite otro reporte para la misma máquina dentro de 15 minutos.

Body:
{
  "employee_number": 7218,            // líder
  "maquina_id": 1,
  "turno": "1",                       // '1'|'2'|'3'
  "descripcion_falla": "Texto libre"
}

201: Objeto reporte (misma estructura de GET) con status=abierto e inicio=now.

422: Validación o mensaje { "message": "Ya existe un reporte para esta máquina en los últimos 15 minutos." }

### POST /reportes/{id}/aceptar
El técnico toma el reporte.

Body:
{ "tecnico_employee_number": 4321 }

200: Objeto reporte actualizado (status=en_mantenimiento, aceptado_en=now).

409: { "message": "El reporte ya fue aceptado por un técnico." } o { "message": "El reporte ya fue finalizado." }

### POST /reportes/{id}/finalizar
Cierre por técnico.

Body:
{
  "descripcion_resultado": "Se cambió el sensor",
  "refaccion_utilizada": "Sensor X",
  "departamento": "Mantenimiento"
}

200: Objeto reporte con status=OK, fin=now, tiempos calculados.

### GET /reportes/lookup
Autocompletado/descubrimiento rápido.
Params opcionales: maquina_id, linea_id, employee_number, tecnico_employee_number
Devuelve: { maquina?, linea?, area?, maquinas?[], lider?, tecnico? }

---

## Reportes por área (scope)

### GET /areas/{area}/reportes
Mismos filtros que GET /reportes, forzando area_id al área del path.

### POST /areas/{area}/reportes
Igual que POST /reportes pero valida que maquina_id pertenezca al área dada.

### GET /areas/{area}/reportes/exportarexcel
Descarga XLSX con mismas columnas que la lista, tiempos en minutos.

Ejemplo (curl):

curl -s -o historial.xlsx "http://127.0.0.1:8000/api/areas/1/reportes/exportarexcel?day=2025-09-26&status=OK"

Respuesta: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet (archivo).

---

## Endpoints helper (catálogos)
- GET /areas/{area}/lineas → líneas por área
- GET /lineas/{linea}/maquinas → máquinas por línea
- GET /areas/{area}/maquinas → máquinas por área
- GET /maquinas/search/{name} → búsqueda por nombre
- GET /maquinas/{id}/relations → máquina con línea y área
- GET /maquinas-with-relations → listado de máquinas con relaciones

---

## Notas para el front/IA
- Siempre enviar Accept: application/json y Content-Type: application/json.
- Para listas grandes de reportes usa paginate=true y per_page.
- Los filtros day/from/to trabajan con una ventana 7:00 → 7:00 del siguiente día y se aplican sobre inicio.
- status acepta alias (ok → OK, mtto → en_mantenimiento).
- employee_number es clave primaria lógica para usuarios y se usa en relaciones de reportes.
- Las fechas en respuestas vienen en ISO 8601; para presentar en MX usa TZ America/Mexico_City.
- El flujo de reporte típico: crear (líder) → aceptar (técnico) → finalizar (técnico).

---

## Ejemplos rápidos (fetch)

// Crear usuario
fetch('/api/user', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ employee_number: 1234, name: 'Juana', role: 'lider', turno: '1' })})
  .then(r => r.json())
  .then(console.log);

// Crear línea
fetch('/api/lineas', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ name: 'Línea 2', area_id: 1 })})
  .then(r => r.json())
  .then(console.log);

// Crear reporte
fetch('/api/reportes', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ employee_number: 1234, maquina_id: 1, turno: '2', descripcion_falla: 'Paro en estación 3' })})
  .then(r => r.json())
  .then(console.log);

// Listar reportes del día (7am→7am) y paginar
fetch('/api/reportes?day=2025-09-26&paginate=true&per_page=20')
  .then(r => r.json())
  .then(console.log);

// Aceptar y cerrar
fetch('/api/reportes/10/aceptar', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ tecnico_employee_number: 4321 })});
fetch('/api/reportes/10/finalizar', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ descripcion_resultado: 'Listo', departamento: 'Mantenimiento' })});
