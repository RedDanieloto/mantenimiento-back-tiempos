# 🔧 API de Herramentales

## ✅ Tests Automatizados

Se han creado tests completos para validar la funcionalidad de herramentales en reportes:

**Ubicación:** `tests/Feature/ReporteHerramentalTest.php`

**Tests implementados:**
- ✓ `puede_crear_reporte_sin_herramental` - Verifica que herramental_id es opcional al crear
- ✓ `flujo_completo_reporte_con_herramental` - Prueba crear → aceptar → finalizar con herramental
- ✓ `get_reportes_incluye_herramental_id` - Verifica que GET /reportes incluye herramental_id
- ✓ `export_excel_incluye_herramental` - Verifica que exportación Excel funciona
- ✓ `no_acepta_herramental_id_invalido` - Validación de herramental_id inválido
- ✓ `herramental_id_es_opcional_al_finalizar` - Permite finalizar con/sin herramental

**Ejecutar tests:**
```bash
php artisan test tests/Feature/ReporteHerramentalTest.php
```

**Resultado:** 6 tests pasados, 30 assertions

---

## Base URL
```
http://localhost:8000/api
```

---

## 📋 Endpoints

### 1. **GET /herramentales** - Listar todos los herramentales

**Descripción:** Obtiene la lista completa de todos los herramentales disponibles.

**Request:**
```http
GET /api/herramentales
Content-Type: application/json
```

**Response (200 OK):**
```json
[
  {
    "id": 1,
    "name": "Llave Inglesa",
    "linea_id": 1,
    "created_at": "2026-01-27T10:30:00.000000Z",
    "updated_at": "2026-01-27T10:30:00.000000Z"
  },
  {
    "id": 2,
    "name": "Destornillador",
    "linea_id": 1,
    "created_at": "2026-01-27T10:31:00.000000Z",
    "updated_at": "2026-01-27T10:31:00.000000Z"
  },
  {
    "id": 3,
    "name": "Martillo",
    "linea_id": 2,
    "created_at": "2026-01-27T10:32:00.000000Z",
    "updated_at": "2026-01-27T10:32:00.000000Z"
  }
]
```

---
### 2. **POST /herramentales** - Crear un nuevo herramental

**Descripción:** Crea un nuevo herramental asociado a una línea.

**Request:**
```http
POST /api/herramentales
Content-Type: application/json

{
  "name": "Llave Inglesa",
  "linea_id": 1
}
```

**Response (201 Created):**
```json
{
  "message": "Herramental creado correctamente.",
  "herramental": {
    "id": 1,
    "name": "Llave Inglesa",
    "linea_id": 1,
    "created_at": "2026-01-27T10:30:00.000000Z",
    "updated_at": "2026-01-27T10:30:00.000000Z"
  }
}
```

**Errores:**
```json
{
  "message": "El nombre es obligatorio.",
  "errors": {
    "name": ["El nombre es obligatorio."]
  }
}
```

```json
{
  "message": "El ID de la linea no existe.",
  "errors": {
    "linea_id": ["El ID de la linea no existe."]
  }
}
```

---

### 3. **GET /herramentales/{id}** - Obtener un herramental por ID

**Descripción:** Obtiene los detalles de un herramental específico.

**Request:**
```http
GET /api/herramentales/1
Content-Type: application/json
```

**Response (200 OK):**
```json
{
  "id": 1,
  "name": "Llave Inglesa",
  "linea_id": 1,
  "created_at": "2026-01-27T10:30:00.000000Z",
  "updated_at": "2026-01-27T10:30:00.000000Z"
}
```

**Response (404 Not Found):**
```json
{
  "message": "Herramental no encontrado."
}
```

---

### 4. **PUT /herramentales/{id}** - Actualizar un herramental

**Descripción:** Actualiza la información de un herramental existente.

**Request:**
```http
PUT /api/herramentales/1
Content-Type: application/json

{
  "name": "Llave Inglesa Grande",
  "linea_id": 2
}
```

**Response (200 OK):**
```json
{
  "message": "Herramental actualizado correctamente.",
  "herramental": {
    "id": 1,
    "name": "Llave Inglesa Grande",
    "linea_id": 2,
    "created_at": "2026-01-27T10:30:00.000000Z",
    "updated_at": "2026-01-27T11:45:00.000000Z"
  }
}
```

**Errores:**
```json
{
  "message": "El nombre ya existe.",
  "errors": {
    "name": ["El nombre ya existe."]
  }
}
```

---

### 5. **DELETE /herramentales/{id}** - Eliminar un herramental

**Descripción:** Elimina un herramental de la base de datos.

**Request:**
```http
DELETE /api/herramentales/1
Content-Type: application/json
```

**Response (200 OK):**
```json
{
  "message": "Herramental eliminado correctamente."
}
```

**Response (404 Not Found):**
```json
{
  "message": "Herramental no encontrado."
}
```

---

### 6. **GET /lineas/{linea_id}/herramentales** - Listar herramentales por línea

**Descripción:** Obtiene todos los herramentales asociados a una línea específica.

**Request:**
```http
GET /api/lineas/1/herramentales
Content-Type: application/json
```

**Response (200 OK):**
```json
[
  {
    "id": 1,
    "name": "Llave Inglesa",
    "linea_id": 1,
    "created_at": "2026-01-27T10:30:00.000000Z",
    "updated_at": "2026-01-27T10:30:00.000000Z"
  },
  {
    "id": 2,
    "name": "Destornillador",
    "linea_id": 1,
    "created_at": "2026-01-27T10:31:00.000000Z",
    "updated_at": "2026-01-27T10:31:00.000000Z"
  }
]
```

**Response (200 OK - Sin herramentales):**
```json
{
  "message": "No hay herramentales para esta línea.",
  "data": []
}
```

---

## 📊 Relación con Reportes

Cuando finalizas un reporte con falla de **Herramental**, se envía el `herramental_id`:

**Endpoint de Reporte:**
```http
POST /api/reportes/{reporte_id}/finalizar
Content-Type: application/json

{
  "descripcion_resultado": "Se cambió el herramental defectuoso",
  "refaccion_utilizada": "Llave Inglesa Nueva",
  "herramental_id": 1,
  "departamento": "Mantenimiento"
}
```

**Response:**
```json
{
  "id": 123,
  "status": "OK",
  "herramental": {
    "id": 1,
    "name": "Llave Inglesa",
    "linea_id": 1
  },
  "descripcion_resultado": "Se cambió el herramental defectuoso",
  "refaccion_utilizada": "Llave Inglesa Nueva",
  "departamento": "Mantenimiento",
  ...
}
```

---

## 📥 Exportar Reportes con Herramental

Al descargar reportes en Excel, la columna **"Herramental"** mostrará el nombre del herramental:

```http
GET /api/reportes/exportarexcel?status=OK&from=2026-01-01&to=2026-01-31
```

| ID | Status | Lider | Herramental | Descripción Resultado |
|---|---|---|---|---|
| 123 | OK | Juan | Llave Inglesa | Se cambió el herramental |
| 124 | OK | María | Destornillador | Se reparó el motor |

---

## ✅ Validaciones

- **name**: Requerido, máximo 255 caracteres, debe ser único (excepto al actualizar el mismo)
- **linea_id**: Requerido, debe existir en la tabla lineas
- **herramental_id** (en reportes): Opcional, si se proporciona debe existir en herramentals

---

## 🔄 Flujo Completo de Uso

1. **Obtener herramentales por línea:**
   ```
   GET /api/lineas/1/herramentales
   ```

2. **Crear reporte (sin herramental aún):**
   ```
   POST /api/reportes
   ```

3. **Aceptar reporte:**
   ```
   POST /api/reportes/123/aceptar
   ```

4. **Finalizar reporte con herramental:**
   ```
   POST /api/reportes/123/finalizar
   Body: { herramental_id: 1, ... }
   ```

5. **Descargar excel con herramental:**
   ```
   GET /api/reportes/exportarexcel
   ```
