# Rutas para Testing - Insomnia

**Base URL**: `http://127.0.0.1:8000/api`

Headers requeridos:
```
Accept: application/json
Content-Type: application/json
```

---

## 📁 USUARIOS

### 1️⃣ Listar todos los usuarios
```
GET /user
```
**cURL**:
```bash
curl -X GET "http://127.0.0.1:8000/api/user" \
  -H "Accept: application/json"
```

---

### 2️⃣ Crear usuario - Líder
```
POST /user
```
**Body**:
```json
{
  "employee_number": 7218,
  "name": "Juan Líder",
  "role": "lider",
  "turno": "1"
}
```
**cURL**:
```bash
curl -X POST "http://127.0.0.1:8000/api/user" \
  -H "Content-Type: application/json" \
  -d '{
    "employee_number": 7218,
    "name": "Juan Líder",
    "role": "lider",
    "turno": "1"
  }'
```

---

### 3️⃣ Crear usuario - Técnico
```
POST /user
```
**Body**:
```json
{
  "employee_number": 4321,
  "name": "Carlos Técnico",
  "role": "tecnico",
  "turno": "1"
}
```
**cURL**:
```bash
curl -X POST "http://127.0.0.1:8000/api/user" \
  -H "Content-Type: application/json" \
  -d '{
    "employee_number": 4321,
    "name": "Carlos Técnico",
    "role": "tecnico",
    "turno": "1"
  }'
```

---

### 4️⃣ Obtener usuario por ID
```
GET /user/{employee_number}
```
**Ejemplo**:
```
GET /user/7218
```
**cURL**:
```bash
curl -X GET "http://127.0.0.1:8000/api/user/7218"
```

---

### 5️⃣ Actualizar usuario
```
PUT /user/{employee_number}
```
**Ejemplo**:
```
PUT /user/7218
```
**Body**:
```json
{
  "name": "Juan Líder Actualizado",
  "turno": "2"
}
```
**cURL**:
```bash
curl -X PUT "http://127.0.0.1:8000/api/user/7218" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Juan Líder Actualizado",
    "turno": "2"
  }'
```

---

### 6️⃣ Eliminar usuario
```
DELETE /user/{employee_number}
```
**Ejemplo**:
```
DELETE /user/7218
```
**cURL**:
```bash
curl -X DELETE "http://127.0.0.1:8000/api/user/7218"
```

---

## 📍 ÁREAS

### 1️⃣ Listar áreas
```
GET /areas
```
**cURL**:
```bash
curl -X GET "http://127.0.0.1:8000/api/areas"
```

---

### 2️⃣ Crear área
```
POST /areas
```
**Body**:
```json
{
  "name": "Costura"
}
```
**cURL**:
```bash
curl -X POST "http://127.0.0.1:8000/api/areas" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Costura"
  }'
```

---

### 3️⃣ Obtener área por ID
```
GET /areas/{id}
```
**Ejemplo**:
```
GET /areas/1
```

---

### 4️⃣ Actualizar área
```
PUT /areas/{id}
```
**Body**:
```json
{
  "name": "Costura Actualizado"
}
```

---

### 5️⃣ Eliminar área
```
DELETE /areas/{id}
```

---

## 📊 LÍNEAS

### 1️⃣ Listar líneas
```
GET /lineas
```

---

### 2️⃣ Crear línea
```
POST /lineas
```
**Body**:
```json
{
  "name": "Línea 1",
  "area_id": 1
}
```
**cURL**:
```bash
curl -X POST "http://127.0.0.1:8000/api/lineas" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Línea 1",
    "area_id": 1
  }'
```

---

### 3️⃣ Obtener línea por ID
```
GET /lineas/{id}
```

---

### 4️⃣ Actualizar línea
```
PUT /lineas/{id}
```
**Body**:
```json
{
  "name": "Línea 1 Actualizada",
  "area_id": 1
}
```

---

### 5️⃣ Eliminar línea
```
DELETE /lineas/{id}
```

---

### 6️⃣ Listar líneas por área
```
GET /areas/{area_id}/lineas
```

---

## ⚙️ MÁQUINAS

### 1️⃣ Listar máquinas
```
GET /maquinas
```

---

### 2️⃣ Crear máquina
```
POST /maquinas
```
**Body**:
```json
{
  "name": "Máquina Coser A",
  "linea_id": 1
}
```
**cURL**:
```bash
curl -X POST "http://127.0.0.1:8000/api/maquinas" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Máquina Coser A",
    "linea_id": 1
  }'
```

---

### 3️⃣ Obtener máquina por ID
```
GET /maquinas/{id}
```

---

### 4️⃣ Actualizar máquina
```
PUT /maquinas/{id}
```
**Body**:
```json
{
  "name": "Máquina Coser A Actualizada",
  "linea_id": 1
}
```

---

### 5️⃣ Eliminar máquina
```
DELETE /maquinas/{id}
```

---

### 6️⃣ Listar máquinas por línea
```
GET /lineas/{linea_id}/maquinas
```

---

### 7️⃣ Listar máquinas por área
```
GET /areas/{area_id}/maquinas
```

---

### 8️⃣ Buscar máquina por nombre
```
GET /maquinas/search/{name}
```
**Ejemplo**:
```
GET /maquinas/search/Coser
```

---

### 9️⃣ Obtener máquina con relaciones
```
GET /maquinas/{id}/relations
```

---

### 🔟 Listar máquinas con relaciones
```
GET /maquinas-with-relations
```

---

## 📋 REPORTES

### 1️⃣ Listar reportes
```
GET /reportes
```
**cURL**:
```bash
curl -X GET "http://127.0.0.1:8000/api/reportes"
```

---

### 2️⃣ Listar reportes - Con filtros
```
GET /reportes?status=abierto&paginate=true&per_page=10
```
**Parámetros disponibles**:
- `status`: abierto | en_mantenimiento | OK (o alias: ok, mtto)
- `turno`: 1 | 2 | 3
- `area_id`: ID del área
- `maquina_id`: ID de máquina
- `linea_id`: ID de línea
- `employee_number`: Número de empleado (líder)
- `tecnico_employee_number`: Número de empleado (técnico)
- `has_tecnico`: true | false
- `has_fin`: true | false
- `q`: Búsqueda de texto libre
- `day`: YYYY-MM-DD
- `from`: YYYY-MM-DD
- `to`: YYYY-MM-DD
- `hour_from`: 0-23
- `hour_to`: 0-23
- `shift`: 1 | 2 | 3
- `sort_by`: inicio | aceptado_en | fin | status | maquina_id | area_id
- `sort_dir`: asc | desc
- `paginate`: true | false
- `per_page`: número

---

### 3️⃣ Listar reportes - Del día
```
GET /reportes?day=2025-12-11
```

---

### 4️⃣ Crear reporte (Líder)
```
POST /reportes
```
**Body**:
```json
{
  "employee_number": 7218,
  "maquina_id": 1,
  "turno": "1",
  "descripcion_falla": "Se detuvo el transportador principal"
}
```
**cURL**:
```bash
curl -X POST "http://127.0.0.1:8000/api/reportes" \
  -H "Content-Type: application/json" \
  -d '{
    "employee_number": 7218,
    "maquina_id": 1,
    "turno": "1",
    "descripcion_falla": "Se detuvo el transportador principal"
  }'
```

**Respuesta 201**:
```json
{
  "id": 1,
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
  "maquina_nombre": "Máquina Coser A",
  "turno": "1",
  "falla": "por definir",
  "departamento": null,
  "descripcion_falla": "Se detuvo el transportador principal",
  "descripcion_resultado": "",
  "refaccion_utilizada": null,
  "inicio": "2025-12-11T10:30:00-06:00",
  "aceptado_en": null,
  "fin": null,
  "tiempo_reaccion_segundos": null,
  "tiempo_mantenimiento_segundos": null,
  "tiempo_total_segundos": null
}
```

---

### 5️⃣ Aceptar reporte (Técnico)
```
POST /reportes/{id}/aceptar
```
**Ejemplo**:
```
POST /reportes/1/aceptar
```
**Body**:
```json
{
  "tecnico_employee_number": 4321
}
```
**cURL**:
```bash
curl -X POST "http://127.0.0.1:8000/api/reportes/1/aceptar" \
  -H "Content-Type: application/json" \
  -d '{
    "tecnico_employee_number": 4321
  }'
```

**Respuesta 200**:
```json
{
  "id": 1,
  "status": "en_mantenimiento",
  "employee_number": 7218,
  "lider_nombre": "Juan Líder",
  "tecnico_employee_number": 4321,
  "tecnico_nombre": "Carlos Técnico",
  "area_id": 1,
  "area_nombre": "Costura",
  "linea_id": 1,
  "linea_nombre": "Línea 1",
  "maquina_id": 1,
  "maquina_nombre": "Máquina Coser A",
  "turno": "1",
  "falla": "por definir",
  "departamento": null,
  "descripcion_falla": "Se detuvo el transportador principal",
  "descripcion_resultado": "",
  "refaccion_utilizada": null,
  "inicio": "2025-12-11T10:30:00-06:00",
  "aceptado_en": "2025-12-11T10:35:00-06:00",
  "fin": null,
  "tiempo_reaccion_segundos": 300,
  "tiempo_mantenimiento_segundos": null,
  "tiempo_total_segundos": null
}
```

---

### 6️⃣ Finalizar reporte (Técnico)
```
POST /reportes/{id}/finalizar
```
**Ejemplo**:
```
POST /reportes/1/finalizar
```
**Body**:
```json
{
  "descripcion_resultado": "Se cambió el sensor defectuoso",
  "refaccion_utilizada": "Sensor X modelo RS-500",
  "departamento": "Mantenimiento"
}
```
**cURL**:
```bash
curl -X POST "http://127.0.0.1:8000/api/reportes/1/finalizar" \
  -H "Content-Type: application/json" \
  -d '{
    "descripcion_resultado": "Se cambió el sensor defectuoso",
    "refaccion_utilizada": "Sensor X modelo RS-500",
    "departamento": "Mantenimiento"
  }'
```

**Respuesta 200**:
```json
{
  "id": 1,
  "status": "OK",
  "employee_number": 7218,
  "lider_nombre": "Juan Líder",
  "tecnico_employee_number": 4321,
  "tecnico_nombre": "Carlos Técnico",
  "area_id": 1,
  "area_nombre": "Costura",
  "linea_id": 1,
  "linea_nombre": "Línea 1",
  "maquina_id": 1,
  "maquina_nombre": "Máquina Coser A",
  "turno": "1",
  "falla": "por definir",
  "departamento": "Mantenimiento",
  "descripcion_falla": "Se detuvo el transportador principal",
  "descripcion_resultado": "Se cambió el sensor defectuoso",
  "refaccion_utilizada": "Sensor X modelo RS-500",
  "inicio": "2025-12-11T10:30:00-06:00",
  "aceptado_en": "2025-12-11T10:35:00-06:00",
  "fin": "2025-12-11T10:45:00-06:00",
  "tiempo_reaccion_segundos": 300,
  "tiempo_mantenimiento_segundos": 600,
  "tiempo_total_segundos": 900
}
```

---

### 7️⃣ Lookup (Autocompletado)
```
GET /reportes/lookup?maquina_id=1
```
**Parámetros opcionales**:
- `maquina_id`: ID de máquina
- `linea_id`: ID de línea
- `employee_number`: Número de empleado
- `tecnico_employee_number`: Número de técnico

---

### 8️⃣ Exportar reportes a Excel
```
GET /reportes/exportarexcel
```
**Con filtros**:
```
GET /reportes/exportarexcel?day=2025-12-11&area_id=1&status=OK
```
**Descarga**: `historial_reportes.xlsx`

---

### 9️⃣ Listar reportes por área
```
GET /areas/{area_id}/reportes
```
**Ejemplo**:
```
GET /areas/1/reportes
```
**Soporta los mismos filtros que GET /reportes**

---

### 🔟 Crear reporte por área
```
POST /areas/{area_id}/reportes
```
**Ejemplo**:
```
POST /areas/1/reportes
```
**Body**:
```json
{
  "employee_number": 7218,
  "maquina_id": 1,
  "turno": "1",
  "descripcion_falla": "Falla detectada en la línea"
}
```

---

### 1️⃣1️⃣ Exportar reportes por área a Excel
```
GET /areas/{area_id}/reportes/exportarexcel
```
**Ejemplo**:
```
GET /areas/1/reportes/exportarexcel?day=2025-12-11
```

---

## 🧪 FLUJO COMPLETO DE PRUEBA

### Paso 1: Crear usuarios
```bash
# Crear Líder
curl -X POST "http://127.0.0.1:8000/api/user" \
  -H "Content-Type: application/json" \
  -d '{"employee_number": 7218, "name": "Juan Líder", "role": "lider", "turno": "1"}'

# Crear Técnico
curl -X POST "http://127.0.0.1:8000/api/user" \
  -H "Content-Type: application/json" \
  -d '{"employee_number": 4321, "name": "Carlos Técnico", "role": "tecnico", "turno": "1"}'
```

### Paso 2: Crear catálogos
```bash
# Crear Área
curl -X POST "http://127.0.0.1:8000/api/areas" \
  -H "Content-Type: application/json" \
  -d '{"name": "Costura"}'

# Crear Línea (usar area_id=1)
curl -X POST "http://127.0.0.1:8000/api/lineas" \
  -H "Content-Type: application/json" \
  -d '{"name": "Línea 1", "area_id": 1}'

# Crear Máquina (usar linea_id=1)
curl -X POST "http://127.0.0.1:8000/api/maquinas" \
  -H "Content-Type: application/json" \
  -d '{"name": "Máquina Coser A", "linea_id": 1}'
```

### Paso 3: Crear reporte
```bash
curl -X POST "http://127.0.0.1:8000/api/reportes" \
  -H "Content-Type: application/json" \
  -d '{"employee_number": 7218, "maquina_id": 1, "turno": "1", "descripcion_falla": "Se detuvo el transportador"}'
```

### Paso 4: Aceptar reporte
```bash
# Usar el ID del reporte devuelto (ej: 1)
curl -X POST "http://127.0.0.1:8000/api/reportes/1/aceptar" \
  -H "Content-Type: application/json" \
  -d '{"tecnico_employee_number": 4321}'
```

### Paso 5: Finalizar reporte
```bash
curl -X POST "http://127.0.0.1:8000/api/reportes/1/finalizar" \
  -H "Content-Type: application/json" \
  -d '{"descripcion_resultado": "Se cambió el sensor", "refaccion_utilizada": "Sensor X", "departamento": "Mantenimiento"}'
```

### Paso 6: Consultar reportes
```bash
# Listar todos
curl -X GET "http://127.0.0.1:8000/api/reportes"

# Con filtros
curl -X GET "http://127.0.0.1:8000/api/reportes?day=2025-12-11&status=OK"
```

---

## 🔴 PRUEBA: Validación de 15 minutos

### Crear reporte 1
```bash
curl -X POST "http://127.0.0.1:8000/api/reportes" \
  -H "Content-Type: application/json" \
  -d '{"employee_number": 7218, "maquina_id": 1, "turno": "1", "descripcion_falla": "Falla 1"}'
# ✅ Respuesta 201 - Reporte creado
```

### Intentar crear reporte 2 de la misma máquina (antes de 15 min)
```bash
curl -X POST "http://127.0.0.1:8000/api/reportes" \
  -H "Content-Type: application/json" \
  -d '{"employee_number": 7218, "maquina_id": 1, "turno": "1", "descripcion_falla": "Falla 2"}'
# ❌ Respuesta 422 - "Ya existe un reporte activo para esta máquina en los últimos 15 minutos."
```

### Cerrar el primer reporte
```bash
curl -X POST "http://127.0.0.1:8000/api/reportes/1/aceptar" \
  -H "Content-Type: application/json" \
  -d '{"tecnico_employee_number": 4321}'

curl -X POST "http://127.0.0.1:8000/api/reportes/1/finalizar" \
  -H "Content-Type: application/json" \
  -d '{"descripcion_resultado": "Listo", "refaccion_utilizada": null, "departamento": "Mantenimiento"}'
# ✅ Reporte cerrado (status = OK)
```

### Intentar crear reporte 3 de la misma máquina (después de cerrar)
```bash
curl -X POST "http://127.0.0.1:8000/api/reportes" \
  -H "Content-Type: application/json" \
  -d '{"employee_number": 7218, "maquina_id": 1, "turno": "1", "descripcion_falla": "Falla 3"}'
# ✅ Respuesta 201 - Permitido porque el anterior está cerrado (OK)
```

---

## 📄 Archivo Insomnia

Hay un archivo `insomnia_collection.json` en la carpeta `/docs` que puedes importar directamente en Insomnia para tener todas estas rutas preconfiguradas.

**Pasos para importar**:
1. Abre Insomnia
2. Ve a `File` → `Import`
3. Selecciona `insomnia_collection.json`
4. ¡Listo! Todas las rutas estarán disponibles

