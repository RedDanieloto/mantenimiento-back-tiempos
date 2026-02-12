# API de Reportes - Documentación para Frontend

## Endpoint Principal

```
GET /api/areas/{areaId}/reportes?day=YYYY-MM-DD
```

---

## Lógica de Visualización de Reportes

### REGLA PRINCIPAL

| Status del Reporte | ¿Cuándo se muestra? |
|-------------------|---------------------|
| `abierto` | **SIEMPRE** (cualquier fecha) |
| `en_mantenimiento` | **SIEMPRE** (cualquier fecha) |
| `en_proceso` | **SIEMPRE** (cualquier fecha) |
| `pendiente` | **SIEMPRE** (cualquier fecha) |
| `asignado` | **SIEMPRE** (cualquier fecha) |
| `ok` | Solo del día seleccionado |
| `finalizado` | Solo del día seleccionado |
| `cerrado` | Solo del día seleccionado |

### Ejemplo Práctico

Si hoy es **12 de febrero** y el usuario selecciona `day=2026-02-12`:

```
RESPUESTA:
├── Reporte #43 (abierto, 6 feb)        ← SE MUESTRA (pendiente)
├── Reporte #22 (en_mantenimiento, 5 feb) ← SE MUESTRA (pendiente)
├── Reporte #4 (en_mantenimiento, 16 ene) ← SE MUESTRA (pendiente)
├── Reporte #50 (ok, 12 feb)            ← SE MUESTRA (del día)
├── Reporte #51 (finalizado, 12 feb)    ← SE MUESTRA (del día)
└── Reporte #49 (ok, 11 feb)            ← NO SE MUESTRA (terminado de otro día)
```

---

## Parámetros del Endpoint

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `day` | string | No | Fecha en formato `YYYY-MM-DD`. Si no se envía, trae todos los reportes. |
| `page` | int | No | Página actual (default: 1) |
| `per_page` | int | No | Registros por página (default: 50, max: 100) |
| `status` | string | No | Filtrar por status: `abierto,en_mantenimiento` |
| `turno` | string | No | Filtrar por turno: `MATUTINO,VESPERTINO,NOCTURNO` |

---

## Ejemplo de Request

```bash
# Reportes del día 12 de febrero + TODOS los pendientes
GET /api/areas/4/reportes?day=2026-02-12

# Con paginación
GET /api/areas/4/reportes?day=2026-02-12&page=1&per_page=50

# Filtrar solo los abiertos
GET /api/areas/4/reportes?day=2026-02-12&status=abierto,en_mantenimiento
```

---

## Estructura de la Respuesta

```json
{
  "data": [
    {
      "id": 43,
      "area_id": 4,
      "maquina_id": 5,
      "employee_number": 12345,
      "tecnico_employee_number": 67890,
      "status": "abierto",
      "falla": "Presión alta",
      "turno": "MATUTINO",
      "descripcion_falla": "La máquina reportó presión...",
      "descripcion_resultado": null,
      "refaccion_utilizada": null,
      "departamento": "Mantenimiento",
      "lider_nombre": "Juan Pérez",
      "tecnico_nombre": "Carlos López",
      "herramental_id": null,
      "inicio": "2026-02-06T19:01:21.000000Z",
      "aceptado_en": null,
      "fin": null,
      "created_at": "2026-02-06T19:01:21.000000Z",
      "updated_at": "2026-02-06T19:01:21.000000Z",
      "maquina": {
        "id": 5,
        "name": "Prensa A-01",
        "linea_id": 2
      },
      "user": {
        "employee_number": 12345,
        "name": "Operador 1",
        "role": "operador",
        "turno": "MATUTINO"
      },
      "tecnico": {
        "employee_number": 67890,
        "name": "Técnico 1",
        "role": "tecnico",
        "turno": "MATUTINO"
      },
      "area": {
        "id": 4,
        "name": "Área 4"
      },
      "herramental": null
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/areas/4/reportes?day=2026-02-12&page=1",
    "last": "http://localhost:8000/api/areas/4/reportes?day=2026-02-12&page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 50,
    "to": 4,
    "total": 4
  }
}
```

---

## Status Posibles

| Status | Descripción | Color Sugerido | Siempre Visible |
|--------|-------------|----------------|-----------------|
| `abierto` | Reporte recién creado, sin asignar | 🔴 Rojo | ✅ Sí |
| `en_mantenimiento` | Técnico trabajando en él | 🟡 Amarillo | ✅ Sí |
| `en_proceso` | En proceso de atención | 🟠 Naranja | ✅ Sí |
| `pendiente` | Esperando acción | 🟣 Morado | ✅ Sí |
| `asignado` | Asignado a técnico | 🔵 Azul | ✅ Sí |
| `ok` | Terminado exitosamente | 🟢 Verde | ❌ Solo del día |
| `finalizado` | Cerrado/Completado | ⚪ Gris | ❌ Solo del día |
| `cerrado` | Cerrado administrativamente | ⚫ Negro | ❌ Solo del día |

---

## Cómo Implementar en Angular

### Servicio

```typescript
getReportes(areaId: number, day: string, page = 1, perPage = 50) {
  const params = new HttpParams()
    .set('day', day)
    .set('page', page.toString())
    .set('per_page', perPage.toString());
  
  return this.http.get(`/api/areas/${areaId}/reportes`, { params });
}
```

### Componente

```typescript
// El backend ya hace el filtrado correcto
// Solo necesitas llamar con el día actual
loadReportes() {
  const today = format(new Date(), 'yyyy-MM-dd');
  this.reportesService.getReportes(this.areaId, today).subscribe(res => {
    this.reportes = res.data;
    // Los pendientes de otros días ya vienen incluidos automáticamente
  });
}
```

### Template - Separar por Status

```html
<!-- Sección: Reportes Pendientes (de cualquier fecha) -->
<div class="pendientes">
  <h2>🔴 Reportes Pendientes</h2>
  <div *ngFor="let r of reportes | filterByStatus:['abierto','en_mantenimiento','en_proceso']">
    <span class="fecha-warning" *ngIf="!isToday(r.inicio)">
      ⚠️ Del {{ r.inicio | date:'dd/MM' }}
    </span>
    {{ r.maquina?.name }} - {{ r.falla }}
  </div>
</div>

<!-- Sección: Reportes del Día -->
<div class="del-dia">
  <h2>🟢 Terminados Hoy</h2>
  <div *ngFor="let r of reportes | filterByStatus:['ok','finalizado']">
    {{ r.maquina?.name }} - {{ r.falla }}
  </div>
</div>
```

### Pipe para Filtrar

```typescript
@Pipe({ name: 'filterByStatus' })
export class FilterByStatusPipe implements PipeTransform {
  transform(reportes: any[], statuses: string[]): any[] {
    return reportes.filter(r => statuses.includes(r.status));
  }
}
```

---

## Casos de Uso Comunes

### 1. Ver SOLO Reportes Pendientes

```typescript
// Opción A: Filtrar en frontend
const pendientes = this.reportes.filter(r => 
  ['abierto', 'en_mantenimiento', 'en_proceso'].includes(r.status)
);

// Opción B: Filtrar desde el backend
GET /api/areas/4/reportes?status=abierto,en_mantenimiento,en_proceso
```

### 2. Mostrar Indicador de "Día Anterior"

```typescript
isToday(fecha: string): boolean {
  const today = format(new Date(), 'yyyy-MM-dd');
  const reportDate = format(new Date(fecha), 'yyyy-MM-dd');
  return today === reportDate;
}

// En el template
<span *ngIf="!isToday(reporte.inicio)" class="badge-warning">
  📅 Reporte del {{ reporte.inicio | date:'dd/MM/yyyy' }}
</span>
```

### 3. Ordenar: Pendientes Primero

```typescript
ngOnInit() {
  this.loadReportes();
}

loadReportes() {
  this.reportesService.getReportes(this.areaId, today).subscribe(res => {
    // Ordenar: pendientes primero, luego por fecha
    this.reportes = res.data.sort((a, b) => {
      const pendingStatuses = ['abierto', 'en_mantenimiento', 'en_proceso'];
      const aIsPending = pendingStatuses.includes(a.status);
      const bIsPending = pendingStatuses.includes(b.status);
      
      if (aIsPending && !bIsPending) return -1;
      if (!aIsPending && bIsPending) return 1;
      return new Date(b.inicio).getTime() - new Date(a.inicio).getTime();
    });
  });
}
```

---

## Resumen

1. **El frontend NO necesita pedir reportes de múltiples días**
2. **El backend ya incluye los pendientes automáticamente**
3. **Solo envía el día actual**: `?day=2026-02-12`
4. **Los reportes pendientes de días anteriores vienen incluidos**
5. **Los reportes terminados solo vienen si son del día**

---

## Contexto del Negocio

La empresa trabaja **24/5** (24 horas, 5 días a la semana). Esto significa:

- Un reporte puede abrirse a las 11:59 PM y seguir abierto a las 12:01 AM del día siguiente
- Los técnicos del turno nocturno necesitan ver reportes que quedaron abiertos del turno vespertino
- Los reportes terminados de días anteriores no son relevantes para el panel actual

Por eso, los reportes **pendientes siempre se muestran**, sin importar la fecha.

---

**Última actualización:** 12 de febrero de 2026

---

## NUEVO: Endpoint de Reportes Pendientes

### `GET /api/areas/{areaId}/reportes/pendientes`

**Propósito:** Obtener TODOS los reportes que NO están terminados, SIN importar la fecha.

Este endpoint es ideal para el panel de técnicos donde necesitan ver todos los reportes abiertos o en mantenimiento de cualquier día.

### Características

| Característica | Valor |
|----------------|-------|
| Filtro de fecha | ❌ NO (muestra todos) |
| Status excluidos | `ok`, `finalizado`, `cerrado` |
| Status incluidos | `abierto`, `en_mantenimiento`, `en_proceso`, `pendiente`, `asignado` |
| Paginación | ✅ Sí (default: 50, max: 100) |

### Parámetros

| Parámetro | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| `page` | int | 1 | Número de página |
| `per_page` | int | 50 | Registros por página (máx: 100) |

### Ejemplo de Request

```bash
# Todos los pendientes del área 4
GET /api/areas/4/reportes/pendientes

# Con paginación
GET /api/areas/4/reportes/pendientes?page=1&per_page=50
```

### Ejemplo de Response

```json
{
  "data": [
    {
      "id": 43,
      "area_id": 4,
      "maquina_id": 5,
      "employee_number": 1234,
      "tecnico_employee_number": null,
      "status": "abierto",
      "falla": "Presión alta",
      "turno": "A",
      "descripcion_falla": "Máquina atorada",
      "descripcion_resultado": null,
      "refaccion_utilizada": null,
      "departamento": null,
      "lider_nombre": "Juan Pérez",
      "tecnico_nombre": null,
      "herramental_id": null,
      "inicio": "2026-02-06T19:01:21.000000Z",
      "aceptado_en": null,
      "fin": null,
      "created_at": "2026-02-06T19:01:21.000000Z",
      "updated_at": "2026-02-06T19:01:21.000000Z",
      "maquina": {
        "id": 5,
        "name": "5067",
        "linea_id": 2,
        "linea": {
          "id": 2,
          "name": "MX5A",
          "area_id": 4
        }
      },
      "user": {
        "employee_number": 1234,
        "name": "Operador 1",
        "role": "lider",
        "turno": "A"
      },
      "tecnico": null,
      "area": {
        "id": 4,
        "name": "Costura"
      },
      "herramental": null
    },
    {
      "id": 22,
      "status": "en_mantenimiento",
      "inicio": "2026-02-05T17:40:01.000000Z",
      ...
    },
    {
      "id": 4,
      "status": "en_mantenimiento", 
      "inicio": "2026-01-16T20:42:43.000000Z",
      ...
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/areas/4/reportes/pendientes?page=1",
    "last": "http://localhost:8000/api/areas/4/reportes/pendientes?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 50,
    "to": 4,
    "total": 4
  }
}
```

### Implementación en Angular

#### Servicio

```typescript
// reportes.service.ts

/**
 * Obtener TODOS los reportes pendientes (abierto, en_mantenimiento, etc.)
 * SIN filtro de fecha - muestra de cualquier día
 */
getPendientes(areaId: number, page = 1, perPage = 50): Observable<any> {
  const params = new HttpParams()
    .set('page', page.toString())
    .set('per_page', perPage.toString());
  
  return this.http.get(`/api/areas/${areaId}/reportes/pendientes`, { params });
}
```

#### Componente

```typescript
// panel-tecnicos.component.ts

export class PanelTecnicosComponent implements OnInit {
  pendientes: any[] = [];
  
  constructor(private reportesService: ReportesService) {}
  
  ngOnInit() {
    this.loadPendientes();
    
    // Refresh cada 2 minutos
    setInterval(() => this.loadPendientes(), 120000);
  }
  
  loadPendientes() {
    this.reportesService.getPendientes(this.areaId).subscribe(res => {
      this.pendientes = res.data;
    });
  }
}
```

#### Template

```html
<!-- panel-tecnicos.component.html -->

<div class="pendientes-container">
  <h2>🔴 Reportes Pendientes ({{ pendientes.length }})</h2>
  
  <div *ngIf="pendientes.length === 0" class="empty">
    ✅ No hay reportes pendientes
  </div>
  
  <table *ngIf="pendientes.length > 0">
    <thead>
      <tr>
        <th>ID</th>
        <th>Máquina</th>
        <th>Línea</th>
        <th>Status</th>
        <th>Fecha</th>
        <th>Días abierto</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <tr *ngFor="let r of pendientes" [class]="r.status">
        <td>#{{ r.id }}</td>
        <td>{{ r.maquina?.name }}</td>
        <td>{{ r.maquina?.linea?.name }}</td>
        <td>
          <span class="badge" [class]="r.status">
            {{ r.status | uppercase }}
          </span>
        </td>
        <td>{{ r.inicio | date:'dd/MM/yyyy HH:mm' }}</td>
        <td>
          <span class="dias" [class.warning]="getDiasAbierto(r.inicio) > 1">
            {{ getDiasAbierto(r.inicio) }} día(s)
          </span>
        </td>
        <td>
          <button (click)="verDetalles(r.id)">Ver</button>
          <button (click)="asignar(r.id)" *ngIf="r.status === 'abierto'">
            Asignar
          </button>
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

```typescript
// Helper para calcular días abierto
getDiasAbierto(inicio: string): number {
  const diff = new Date().getTime() - new Date(inicio).getTime();
  return Math.floor(diff / (1000 * 60 * 60 * 24));
}
```

### Cuándo usar cada endpoint

| Endpoint | Cuándo usarlo |
|----------|---------------|
| `GET /areas/{id}/reportes?day=YYYY-MM-DD` | Panel principal: reportes del día + pendientes |
| `GET /areas/{id}/reportes/pendientes` | Panel de técnicos: SOLO pendientes de cualquier fecha |

### Comparativa

| Característica | `/reportes?day=...` | `/reportes/pendientes` |
|----------------|---------------------|------------------------|
| Filtro fecha | ✅ Sí (requerido) | ❌ No |
| Muestra terminados | ✅ Del día | ❌ Nunca |
| Muestra pendientes | ✅ De cualquier fecha | ✅ De cualquier fecha |
| Caso de uso | Panel general | Panel de técnicos |
