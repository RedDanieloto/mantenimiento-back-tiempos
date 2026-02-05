# 🔧 Herramentales - Guía para Frontend

## ✅ Estado del Backend
- **Backend listo y validado**: Todos los tests pasados (6/6)
- **Cambios compatibles hacia atrás**: No rompe funcionalidad existente
- **herramental_id es opcional**: Los reportes sin falla de herramental siguen funcionando

---

## 📊 ¿Qué cambió?

### Base de datos
- Nueva tabla `herramentals` con herramentales por línea
- Campo `herramental_id` (nullable) en tabla `reportes`

### Respuestas de API
Todos los endpoints de reportes ahora incluyen:
```json
{
  "id": 1,
  "herramental_id": 5,  // ⬅️ NUEVO (puede ser null)
  "status": "OK",
  "employee_number": 1234,
  // ... otros campos existentes ...
  "herramental": {      // ⬅️ NUEVO (objeto completo, puede ser null)
    "id": 5,
    "name": "Llave Inglesa 10mm",
    "linea_id": 2
  },
  "herramental_nombre": "Llave Inglesa 10mm",  // ⬅️ NUEVO (atajo, puede ser null)
  "maquina": { ... },
  "user": { ... },
  "tecnico": { ... }
}
```

---

## 🔗 Nuevos Endpoints

### 1. Listar herramentales de una línea
```http
GET /api/lineas/{linea_id}/herramentales
```

**Uso típico:** Cuando el usuario selecciona una línea/máquina y necesita elegir un herramental.

**Respuesta:**
```json
[
  {
    "id": 1,
    "name": "Llave Inglesa 10mm",
    "linea_id": 2
  },
  {
    "id": 2,
    "name": "Destornillador Phillips",
    "linea_id": 2
  }
]
```

---

### 2. CRUD completo de herramentales (Admin)

**Listar todos:**
```http
GET /api/herramentales
```

**Ver uno:**
```http
GET /api/herramentales/{id}
```

**Crear:**
```http
POST /api/herramentales
Content-Type: application/json

{
  "name": "Llave Torx T20",
  "linea_id": 3
}
```

**Actualizar:**
```http
PUT /api/herramentales/{id}
Content-Type: application/json

{
  "name": "Llave Torx T20 (nuevo)",
  "linea_id": 3
}
```

**Eliminar:**
```http
DELETE /api/herramentales/{id}
```

---

## 🔄 Flujo de Trabajo Actualizado

### Escenario 1: Falla de Herramental

```
1. Usuario crea reporte
   POST /api/reportes
   {
     "employee_number": 1234,
     "maquina_id": 5,
     "turno": "A",
     "descripcion_falla": "No prende la máquina"
   }

2. Backend devuelve reporte con herramental_id: null
   {
     "id": 100,
     "herramental_id": null,  // ⬅️ Inicia sin herramental
     "status": "abierto",
     ...
   }

3. Técnico acepta
   POST /api/reportes/100/aceptar
   {
     "tecnico_employee_number": 5678
   }

4. Técnico diagnostica: "Es falla de herramental"
   Frontend obtiene herramentales de esa línea:
   GET /api/lineas/{linea_id}/herramentales

5. Técnico finaliza y asigna herramental
   POST /api/reportes/100/finalizar
   {
     "falla": "Herramental",
     "departamento": "Mantenimiento",
     "descripcion_resultado": "Se cambió llave dañada",
     "herramental_id": 7  // ⬅️ ASIGNAR AQUÍ
   }

6. Backend valida y guarda
   - Valida que herramental_id exista
   - Guarda el reporte con herramental asignado
```

### Escenario 2: Falla Normal (Sin Herramental)

```
POST /api/reportes/101/finalizar
{
  "falla": "Electrica",
  "departamento": "Mantenimiento",
  "descripcion_resultado": "Se cambió fusible"
  // herramental_id NO enviado = null (OK)
}
```

---

## ⚠️ IMPORTANTE para el Frontend

### ❌ Error común
**NO** enviar solo herramental_id al crear el reporte. El herramental se asigna **al finalizar**, no al crear.

### ✅ Correcto
1. **Crear reporte**: NO enviar herramental_id
2. **Finalizar con herramental**: Enviar herramental_id en el POST /finalizar

---

## 📋 Campos de Validación

### POST /api/reportes/{id}/finalizar

**Campos opcionales nuevos:**
- `herramental_id` (integer|exists:herramentals,id|nullable)

**Validación:**
- Si se envía, debe existir en la tabla herramentals
- Si no se envía, queda como null (falla normal)

---

## 🧪 Tests y Calidad

✅ **6 tests automatizados pasados**
- Creación sin herramental
- Flujo completo con herramental
- GET incluye herramental_id
- Exportación Excel funciona
- Validación de herramental_id inválido
- herramental_id opcional al finalizar

**Ejecutar tests:**
```bash
php artisan test tests/Feature/ReporteHerramentalTest.php
```

---

## 📊 Exportación Excel

El Excel ahora incluye columna **"Herramental"** en la posición 16:
```
| ... | Descripción Resultado | Herramental | Inicio | ...
| ... | Se cambió llave      | Llave 10mm  | ...    | ...
```

Si no hay herramental asignado, la celda queda vacía.

---

## 🔍 Respuestas en Diferentes Vistas

### Vista Normal (default)
```json
GET /api/reportes?page=1

[
  {
    "id": 1,
    "herramental_id": 5,
    "herramental": { "id": 5, "name": "Llave 10mm", "linea_id": 2 },
    "herramental_nombre": "Llave 10mm",
    ...
  }
]
```

### Vista Pretty (?pretty=true)
```json
GET /api/reportes/1?pretty=true

{
  "id": 1,
  "refs": {
    "herramental_id": 5,  // ⬅️ En sección refs
    ...
  },
  "details": {
    "herramental": {      // ⬅️ En sección details
      "id": 5,
      "name": "Llave 10mm"
    },
    ...
  }
}
```

---

## 📞 Soporte

- Documentación API: `/docs/RUTAS_HERRAMENTALES.md`
- Tests: `tests/Feature/ReporteHerramentalTest.php`
- Endpoints en: `routes/api.php`

---

## 🎯 Checklist Frontend

- [ ] Agregar selector de herramental en formulario de finalización
- [ ] Obtener lista de herramentales: `GET /api/lineas/{id}/herramentales`
- [ ] Enviar `herramental_id` en POST /finalizar solo si es falla de herramental
- [ ] Mostrar `herramental_nombre` en listado de reportes
- [ ] (Opcional) Pantalla admin para CRUD de herramentales
- [ ] (Opcional) Filtrar reportes por herramental_id

---

**Última actualización:** 5 de Febrero 2026  
**Versión Backend:** Laravel 11 + PHP 8.4.1  
**Estado:** ✅ Listo para integración
