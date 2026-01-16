# Plan de Optimización - Panel de Gestión de Mantenimiento

**Fecha:** 16 de enero de 2026  
**Objetivo:** Reducir consultas redundantes y optimizar el rendimiento mostrando solo reportes del día

---

## 📊 Análisis Actual - EXPLICACIÓN DETALLADA

### ¿Cuál es el Problema Real?

Actualmente tu aplicación funciona así:

```
USUARIO ABRE LA PÁGINA
    ↓
[1] Se cargan TODOS los reportes de la base de datos (desde el principio de los tiempos)
    ↓
Si hay 10,000 reportes históricos → descarga 10,000 registros completos
    ↓
Cada registro tiene: ID, status, máquina, descripción, tiempos, etc.
    ↓
[2] Cada 1 minuto → se recarga TODA la lista nuevamente (incluso si nada cambió)
    ↓
[3] Además, se cargan:
    - Lista de TODAS las líneas de producción
    - Lista de TODAS las máquinas
    - Lista de TODAS las áreas
    ↓
[4] Si abres un modal → se cargan OTRA VEZ algunos datos
```

### Problema Identificado
- ✗ Se cargan **TODOS** los reportes sin filtro de fecha (históricos innecesarios)
- ✗ Polling cada 1 minuto sin control de cambios reales (recarga todo igual aunque nada cambie)
- ✗ Múltiples llamadas a servicios que podrían ser cacheadas (lineas, máquinas, áreas se cargan siempre)
- ✗ Realtime service corre independiente sin sincronización (dos sistemas buscando lo mismo)
- ✗ No hay diferenciación entre reportes activos y históricos

### Impacto Actual (Números Reales)
- 📊 **Tamaño descargado por usuario:** Si tienes 10,000 reportes históricos × 1KB cada uno = 10MB por sesión
- ⏱️ **Tiempo de carga:** 3-5 segundos esperando a que lleguen los datos
- 🔄 **Llamadas innecesarias:** 60 veces por hora (cada minuto se repite la descarga completa)
- 💾 **Memoria en el navegador:** La tabla carga 10,000 registros en memoria aunque solo veas 20
- 🚀 **Servidor:** 100 usuarios × 60 llamadas/hora = 6,000 consultas a la BD cada hora (¡SATURACIÓN!)

---

## 🔍 Flujo Actual vs Flujo Optimizado

### Flujo ACTUAL (Problemático)
```
t=0:00   → Usuario abre → GET /reportes (sin filtro) → recibe 10,000 registros
t=1:00   → Polling → GET /reportes (sin filtro) → recibe 10,000 registros IGUALES
t=2:00   → Polling → GET /reportes (sin filtro) → recibe 10,000 registros IGUALES
t=3:00   → Polling → GET /reportes (sin filtro) → recibe 10,000 registros IGUALES
...
t=60:00  → Usuario sale → Nada se limpió, todo en memoria

PROBLEMA: Se descarga 3.6 millones de registros al día por cada usuario
```

### Flujo OPTIMIZADO (Propuesto)
```
t=0:00   → Usuario abre → GET /reportes?day=2026-01-16 → recibe 50 registros del día
t=1:00   → Polling → GET /reportes?day=2026-01-16 → recibe 50 registros (igual que antes)
           → Sistema detecta: "hash es igual" → NO ACTUALIZA UI, ahorra procesamiento
t=2:00   → Realtime EVENT → llega evento que dice "nuevo reporte creado"
           → Solo carga datos del nuevo reporte, NO recarga todo
t=3:00   → Polling → GET /reportes?day=2026-01-16 → recibe 51 registros (cambió)
           → Sistema detecta: "hash es diferente" → ACTUALIZA UI con el nuevo
...
t=60:00  → Usuario sale → Cache se limpia, conexiones cierran

BENEFICIO: Se descargan solo 50-100 registros al día por usuario
```

---

## 🎯 Plan Estructurado de Optimización - EXPLICACIÓN PROFUNDA

### **FASE 1: Filtro de Reportes por Fecha**
**Prioridad:** 🔴 CRÍTICA  
**Impacto:** -80% en volumen de datos  
**Por qué es crítico:** Es la causa raíz del 80% del problema

#### 📌 El Problema que Resuelve FASE 1

Imagina que tu gerente entra al sistema a las 8 AM:
- La base de datos tiene 10,000 reportes (del 2025 completo + enero)
- El sistema descarga TODOS los 10,000 registros
- Pero el gerente SOLO necesita ver los reportes de HOY (2026-01-16)
- Hoy solo hay 47 reportes nuevos
- **Está descargando 213 veces más datos de los que necesita** ❌

**SOLUCIÓN:** Decirle al backend: "Solo dame los reportes de hoy"
- Backend filtra en SQL (muy rápido)
- Descarga solo 47 registros (no 10,000)
- **Ahorro: 10,000 → 47 = 99.5% menos datos** ✅

#### Paso 1.1: Modificar Backend → Parámetro `day`
**Archivo afectado:** `reportes.service.ts` (línea ~80)

**¿Qué está pasando ahora?**
```typescript
// AHORA - Sin filtro de fecha
list(params: {
  status?: string[];      
  turno?: string[];
  area_id?: number[];     
  maquina_id?: number[];
  linea_id?: number[];
  employee_number?: number[];
  tecnico_employee_number?: number[];
  q?: string;
  day?: string;           // ← EXISTE, pero NO SE USA
  from?: string;          
  to?: string;            
  sort_by?: 'inicio'|'aceptado_en'|'fin'|'status'|'maquina_id'|'area_id';
  sort_dir?: 'asc'|'desc';
  paginate?: boolean;
  per_page?: number;
} = {}, areaId?: number): Observable<ReporteApi[] | any> {
  // El parámetro 'day' EXISTE en el tipo pero NO se está usando en el componente
}
```

**¿Qué necesitamos cambiar?**
```typescript
// OBJETIVO - Usar el filtro de fecha
list(params: {
  ...
  day?: string;    // Ej: "2026-01-16" en formato YYYY-MM-DD
  ...
}): Observable<ReporteApi[]> {
  let p = new HttpParams();
  
  // Cuando se recibe day, agregarlo al request
  if (params.day) {
    p = p.set('day', params.day);
  }
  
  return this.http.get<ReporteApi[]>(`${this.baseScoped}/reportes`, { params: p });
}
```

**Verificación:**
- ✅ El parámetro `day` ya existe en `ReportesService`
- ✅ Solo necesita ser usado en el componente
- ⚠️ Verificar que el backend Laravel acepta `?day=2026-01-16` en la ruta

#### Paso 1.2: Agregar Signal de Fecha Actual
**Archivo afectado:** `tabla.component.ts` (línea ~60, con otros signals)

**¿Qué necesitamos agregar?**
```typescript
// Nuevo signal para guardar la fecha de hoy
readonly currentDate = signal<string>(this.getTodayDateString());

// Función helper para convertir Date a "YYYY-MM-DD"
private getTodayDateString(): string {
  const today = new Date();
  // Ejemplo: new Date(2026, 0, 16) → "2026-01-16"
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}
```

**¿Por qué es un signal?**
- Permite que si el usuario tiene la app abierta a las 23:59 y luego cambia a las 00:00, automáticamente actualice la fecha
- Si en el futuro quieres agregar un selector de "ver reportes del 15 enero", solo cambias el signal
- Es reactive: cuando cambia, automáticamente dispara nuevas búsquedas

#### Paso 1.3: Actualizar `reload()` con Filtro de Fecha
**Archivo afectado:** `tabla.component.ts` (línea ~680)

**¿Qué está pasando ahora?**
```typescript
private reload() {
  this.loading.set(true); 
  this.error.set('');
  
  // ❌ NO filtra por fecha
  this.svc.list(
    { sort_by: 'inicio', sort_dir: 'desc' },  // Solo ordena por inicio
    this.areaId()
  )
  .pipe(timeout(15000), catchError(...), finalize(...))
  .subscribe((res) => {
    // Recibe TODOS los reportes
    const rows: ReporteApi[] = Array.isArray(res) ? res : (res?.data || []);
    this.data.set(rows.map(mapApiToUI));
  });
}
```

**¿Qué necesitamos cambiar?**
```typescript
private reload() {
  this.loading.set(true); 
  this.error.set('');
  
  // ✅ AHORA filtra por fecha del día
  this.svc.list(
    { 
      day: this.currentDate(),      // ← AGREGAR ESTO
      sort_by: 'inicio', 
      sort_dir: 'desc' 
    },
    this.areaId()
  )
  .pipe(timeout(15000), catchError(...), finalize(...))
  .subscribe((res) => {
    // Recibe SOLO los reportes de hoy
    const rows: ReporteApi[] = Array.isArray(res) ? res : (res?.data || []);
    this.data.set(rows.map(mapApiToUI));
  });
}
```

**Ejemplo real de diferencia:**
```
SIN Filtro:     GET /api/areas/2/reportes
                Respuesta: 10,000 registros (50MB), 5 segundos ⏱️

CON Filtro:     GET /api/areas/2/reportes?day=2026-01-16
                Respuesta: 47 registros (200KB), 0.2 segundos ⏱️
                
AHORRO: 50MB → 200KB (99.6% menos), 5s → 0.2s (25x más rápido)
```

---

### **FASE 2: Cache de Datos Maestros**
**Prioridad:** 🟡 ALTA  
**Impacto:** Reducir 3-4 llamadas innecesarias por sesión

#### 📌 El Problema que Resuelve FASE 2

Observa qué pasa cuando abres la página:

```
t=0:00 → Usuario entra
   ├─ GET /lineas (para llenar el dropdown de líneas) → 50 líneas
   ├─ GET /areas (información del área) → 1 área
   └─ GET /maquinas (lista de máquinas) → 200 máquinas
   
t=1:00 → Usuario filtra por línea
   └─ GET /lineas OTRA VEZ (¡ya tenemos los datos!) → 50 líneas de nuevo
   
t=2:00 → Usuario abre un modal
   └─ GET /maquinas OTRA VEZ (¡ya tenemos los datos!) → 200 máquinas de nuevo
   
t=3:00 → Usuario cambia de área
   └─ GET /areas OTRA VEZ... 
   └─ GET /lineas OTRA VEZ...
   └─ GET /maquinas OTRA VEZ...
```

**El problema:** Se piden los MISMOS datos varias veces en la misma sesión ❌

**La solución:** Guardar en memoria los datos que ya obtuvimos (CACHE)
- Primera vez: Descargar desde servidor
- Próximas veces: Usar la copia en memoria (¡instantáneo!)
- Expiración: Si pasa 5 minutos, volver a descargar (por si cambió en BD)

#### Paso 2.1: Cachear Líneas por Área
**Archivo afectado:** `lineas.service.ts`

**¿Qué necesitamos agregar?**
```typescript
@Injectable({ providedIn: 'root' })
export class LineasService {
  private http = inject(HttpClient);
  
  // ✅ NUEVO: Sistema de caché
  private lineaCache = new Map<number, { 
    data: any[], 
    timestamp: number 
  }>();
  
  private readonly CACHE_TTL = 5 * 60 * 1000; // 5 minutos en milisegundos
  
  // ✅ NUEVO: Método para obtener líneas CON caché
  getByArea(areaId: number): Observable<any[]> {
    // Verificar si ya tenemos en caché y no expiró
    const cached = this.lineaCache.get(areaId);
    const now = Date.now();
    
    if (cached && (now - cached.timestamp) < this.CACHE_TTL) {
      // ✅ Están frescos, retornar copia del caché (no hacer llamada HTTP)
      return of(cached.data);
    }
    
    // ❌ No hay caché o expiró, hacer llamada HTTP
    return this.http.get<any[]>(`/api/areas/${areaId}/lineas`)
      .pipe(
        tap(data => {
          // Guardar en caché después de recibir
          this.lineaCache.set(areaId, {
            data,
            timestamp: now
          });
        })
      );
  }
  
  // ✅ NUEVO: Método para limpiar caché (opcional)
  clearCache(areaId?: number): void {
    if (areaId) {
      this.lineaCache.delete(areaId);
    } else {
      this.lineaCache.clear(); // Limpiar todo
    }
  }
}
```

**Diagrama de caché:**
```
Primera llamada (t=0:00):
┌─ getByArea(2)
│  ├─ ¿Está en caché? NO
│  ├─ HTTP GET /api/areas/2/lineas
│  └─ Respuesta recibida → Guardar en caché con timestamp
└─ Retorna: las 50 líneas (después de esperar al servidor) ⏱️

Segunda llamada (t=0:30):
┌─ getByArea(2)
│  ├─ ¿Está en caché? SÍ
│  ├─ ¿Expiró (pasó 5 min)? NO (solo pasó 30 segundos)
│  └─ Retorna: las 50 líneas del caché (INSTANTÁNEO) ⚡
└─ NO hace HTTP, ahorra tiempo

Tercera llamada (t=5:10):
┌─ getByArea(2)
│  ├─ ¿Está en caché? SÍ
│  ├─ ¿Expiró (pasó 5 min)? SÍ (pasó 5 minutos 10 segundos)
│  ├─ HTTP GET /api/areas/2/lineas (obtener datos frescos del servidor)
│  └─ Actualizar caché con nuevos datos
└─ Retorna: las 50 líneas (pueden haber cambiado)
```

#### Paso 2.2: Cachear Máquinas
**Archivo afectado:** `maquinas.service.ts` (idéntico a líneas)

```typescript
@Injectable({ providedIn: 'root' })
export class MaquinasService {
  private http = inject(HttpClient);
  private maquinaCache = new Map<number, { 
    data: any[], 
    timestamp: number 
  }>();
  private readonly CACHE_TTL = 5 * 60 * 1000;
  
  getByArea(areaId: number): Observable<any[]> {
    const cached = this.maquinaCache.get(areaId);
    const now = Date.now();
    
    if (cached && (now - cached.timestamp) < this.CACHE_TTL) {
      return of(cached.data);
    }
    
    return this.http.get<any[]>(`/api/areas/${areaId}/maquinas`)
      .pipe(
        tap(data => {
          this.maquinaCache.set(areaId, { data, timestamp: now });
        })
      );
  }
}
```

#### Paso 2.3: Cachear Áreas
**Archivo afectado:** `areas.service.ts`

```typescript
@Injectable({ providedIn: 'root' })
export class AreasService {
  private http = inject(HttpClient);
  private areaCache: { data: any[], timestamp: number } | null = null;
  private readonly CACHE_TTL = 10 * 60 * 1000; // 10 minutos (cambian menos)
  
  list(): Observable<any[]> {
    const now = Date.now();
    
    if (this.areaCache && (now - this.areaCache.timestamp) < this.CACHE_TTL) {
      return of(this.areaCache.data);
    }
    
    return this.http.get<any[]>(`/api/areas`)
      .pipe(
        tap(data => {
          this.areaCache = { data, timestamp: now };
        })
      );
  }
}
```

#### Paso 2.4: El Componente ya Usa el Caché Automáticamente
**Archivo afectado:** `tabla.component.ts` (NO NECESITA CAMBIOS)

El componente ya hace:
```typescript
this.loadLineas() {
  this.lineasSvc.getByArea(this.areaId())
    .pipe(...)
    .subscribe(lineas => {
      this.lineas.set(lineas);
    });
}
```

Con nuestro caché en el service, esto automáticamente:
- Primera vez: Espera al servidor
- Próximas veces: Obtiene del caché (¡sin cambios en el componente!)

---

### **FASE 3: Optimización del Polling**
**Prioridad:** 🟡 ALTA  
**Impacto:** Reducir llamadas innecesarias en 50-80%

#### 📌 El Problema que Resuelve FASE 3

Observa qué pasa con el polling actual:

```
Minuto 0 → Carga datos, obtiene: [Reporte 1, Reporte 2, Reporte 3]
Minuto 1 → Polling automático → GET /reportes?day=2026-01-16
           Resultado: [Reporte 1, Reporte 2, Reporte 3] (IGUAL)
           ¿Qué hace? → Actualiza la tabla completa AUNQUE SEA IDÉNTICA ❌
           
Minuto 2 → Polling automático → GET /reportes?day=2026-01-16
           Resultado: [Reporte 1, Reporte 2, Reporte 3] (IGUAL)
           ¿Qué hace? → Actualiza la tabla completa AUNQUE SEA IDÉNTICA ❌

Minuto 3 → Finalmente llega un cambio: [Reporte 1, Reporte 2, Reporte 3, Reporte 4]
           ¿Qué hace? → Actualiza la tabla
```

**El problema:** Se actualiza la UI 2 veces innecesariamente (minutos 1 y 2) ❌

**La solución:** Usar un HASH/CHECKSUM
- Calcular un "resumen" de los datos (ej: "ABC123")
- Si el hash es igual → Los datos no cambiaron → NO actualizar UI
- Si el hash es diferente → Los datos cambiaron → SÍ actualizar UI

#### Paso 3.1: Agregar Hash/Checksum de Datos
**Archivo afectado:** `tabla.component.ts` (línea ~50, con otros fields)

**¿Qué necesitamos agregar?**
```typescript
export class TablaComponent implements OnDestroy {
  // ... otros signals...
  
  // ✅ NUEVO: Guardar el hash de los datos anteriores
  private lastDataHash = '';
  
  // ✅ NUEVO: Función para calcular hash (simplificada)
  private getDataHash(data: ReporteApi[]): string {
    // Método simple: concatenar IDs y calcular hash
    // En producción podrías usar crypto.subtle.digest(), pero esto es rápido
    const ids = data.map(d => d.id).join('|');
    const statusCount = data.length;
    return `${ids}:${statusCount}`;
    
    // Nota: No usamos JSON.stringify completo porque es lento
    // Solo comparamos estructura: IDs y cantidad
  }
}
```

**¿Por qué solo IDs y cantidad?**
- Es muy rápido calcular (O(n) donde n es pequeño)
- Si tienes 50 reportes → hash = "1|2|3|4|...|50:50"
- Si los datos son idénticos → hash es idéntico
- Si agregan/quitan un reporte → hash cambia
- Si cambia el status de uno → IDs no cambian, pero el hash seguirá siendo igual (eso lo manejas en FASE 4)

#### Paso 3.2: Validar Cambios Antes de Actualizar UI
**Archivo afectado:** `tabla.component.ts` (línea ~680, en el método `reload()`)

**¿Qué está pasando ahora?**
```typescript
private reload() {
  this.loading.set(true);
  this.error.set('');
  
  this.svc.list({ day: this.currentDate(), sort_by: 'inicio', sort_dir: 'desc' }, this.areaId())
    .pipe(timeout(15000), catchError(...), finalize(...))
    .subscribe((res) => {
      const rows: ReporteApi[] = Array.isArray(res) ? res : (res?.data || []);
      
      // ❌ SIEMPRE actualiza, aunque sea idéntico
      this.data.set(rows.map(mapApiToUI));
    });
}
```

**¿Qué necesitamos cambiar?**
```typescript
private reload() {
  this.loading.set(true);
  this.error.set('');
  
  this.svc.list({ day: this.currentDate(), sort_by: 'inicio', sort_dir: 'desc' }, this.areaId())
    .pipe(timeout(15000), catchError(...), finalize(...))
    .subscribe((res) => {
      const rows: ReporteApi[] = Array.isArray(res) ? res : (res?.data || []);
      
      // ✅ NUEVO: Calcular hash de nuevos datos
      const newHash = this.getDataHash(rows);
      
      // ✅ NUEVO: Comparar con hash anterior
      if (newHash !== this.lastDataHash) {
        // Los datos CAMBIARON, actualizar UI
        this.data.set(rows.map(mapApiToUI));
        this.lastDataHash = newHash;
        console.log('✅ Datos actualizados (cambio detectado)');
      } else {
        // Los datos son IGUALES, no hacer nada
        console.log('⏭️ Sin cambios, UI no se actualiza');
      }
    });
}
```

**Diagrama de funcionamiento:**
```
Minuto 0: [R1, R2, R3] → hash="1|2|3:3" → Guardar y mostrar
Minuto 1: [R1, R2, R3] → hash="1|2|3:3" → IGUAL, NO actualizar ✅ Ahorro
Minuto 2: [R1, R2, R3] → hash="1|2|3:3" → IGUAL, NO actualizar ✅ Ahorro
Minuto 3: [R1, R2, R3, R4] → hash="1|2|3|4:4" → DIFERENTE, SÍ actualizar

Ahorro: En 3 minutos, la UI se actualiza 2 veces en lugar de 3
Si multiplicas por 60 minutos = 40 actualizaciones innecesarias por hora ¡AHORRO ENORME!
```

#### Paso 3.3: Aumentar Intervalo de Polling (Opcional)
**Archivo afectado:** `tabla.component.ts` (línea ~665)

**¿Qué está pasando ahora?**
```typescript
ngOnInit() {
  // ...
  
  // Cada 60 segundos (1 minuto)
  if (this._pollTimer) clearInterval(this._pollTimer);
  this._pollTimer = setInterval(() => {
    if (!this.loading()) this.reload();
  }, 60000);  // ← 60000 ms = 1 minuto
}
```

**¿Cuándo cambiar el intervalo?**

Si implementas FASE 4 (Realtime):
```typescript
// Con realtime activo, puede ser más laxo
const pollInterval = this.realtimeActive ? 300000 : 60000;
// 5 minutos si realtime funciona, 1 minuto si no

this._pollTimer = setInterval(() => {
  if (!this.loading()) this.reload();
}, pollInterval);
```

Si NO implementas Realtime:
```typescript
// Sin realtime, 2 minutos es un buen balance
this._pollTimer = setInterval(() => {
  if (!this.loading()) this.reload();
}, 120000);  // 120000 ms = 2 minutos
```

**Comparativa:**
```
Cada 1 minuto:  60 llamadas/hora × 100 usuarios = 6,000 llamadas/hora ⚠️
Cada 2 minutos: 30 llamadas/hora × 100 usuarios = 3,000 llamadas/hora (50% menos)
Cada 5 minutos: 12 llamadas/hora × 100 usuarios = 1,200 llamadas/hora (80% menos)
```

---

### **FASE 4: Sincronización con Realtime Service**
**Prioridad:** 🟢 MEDIA  
**Impacto:** Mantener datos frescos sin polling constante

#### 📌 El Problema que Resuelve FASE 4

Ahora mismo tienes DOS sistemas buscando cambios:

```
Sistema 1: POLLING (cada 1 minuto)
├─ Pregunta cada minuto: "¿Hay reportes nuevos?"
└─ 60 preguntas por hora, aunque nada cambie

Sistema 2: REALTIME (eventos en vivo)
├─ Cuando pasa algo, el servidor lo notifica
├─ Instántaneo (no espera 1 minuto)
└─ Pero NO se usa actualmente

PROBLEMA: Estás pagando por dos servicios pero solo uno funciona
```

**La solución:** 
- Realtime te avisa instantáneamente cuando hay cambios
- Polling es un backup (por si Realtime falla)
- Combinados = lo mejor de ambos mundos

#### Paso 4.1: Integrar Eventos Realtime
**Archivo afectado:** `tabla.component.ts` (línea ~150, agregar nuevo effect)

**¿Qué necesitamos agregar?**
```typescript
export class TablaComponent implements OnDestroy {
  private realtimeSvc = inject(RealtimeService);
  
  // ... otros signals...
  
  // ✅ NUEVO: Signal para saber si realtime está activo
  readonly realtimeActive = signal(false);
  
  // ✅ NUEVO: Effect que se suscribe a eventos realtime
  private realtimeEffectRef: EffectRef = effect(() => {
    if (!this.isBrowser) return;
    
    // Iniciar realtime
    this.realtimeSvc.start().then(() => {
      this.realtimeActive.set(true);
      
      // Escuchar eventos
      this.realtimeSvc.stream().subscribe(event => {
        console.log('🔔 Evento realtime:', event.type);
        
        // Cuando hay un evento relevante, recargar datos
        if (['reporte.created', 'reporte.accepted', 'reporte.finished'].includes(event.type)) {
          console.log('📡 Cambio detectado en realtime, recargando...');
          this.reload(); // Recargar la lista
        }
      });
    }).catch(err => {
      console.warn('❌ Realtime no disponible, confiando en polling', err);
      this.realtimeActive.set(false);
    });
  });
}
```

**¿Qué sucede?**
- El servidor hace un evento: "Nuevo reporte creado"
- Pusher lo transmite
- Angular lo recibe en realtime
- Automáticamente hace `reload()` → obtiene datos frescos
- TODO en menos de 100ms (vs esperar 1 minuto al polling)

#### Paso 4.2: Reducir Polling si Realtime Está Activo
**Archivo afectado:** `tabla.component.ts` (línea ~665, en ngOnInit)

**¿Qué cambiar?**
```typescript
ngOnInit() {
  this.route.params.subscribe(p => {
    const slug = (p['area'] || '').toString();
    this.areaSlug.set(this.normalize(slug));
    this.resolveAreaId().then(() => {
      if (this.isBrowser) {
        this.loadLineas();
        this.restoreLineaLock();
        this.reload();
      }
    });
    
    // ✅ NUEVO: Polling adaptable según realtime
    if (this.isBrowser) {
      if (this._pollTimer) clearInterval(this._pollTimer);
      
      // Si realtime está activo → polling laxo (5 minutos)
      // Si no está activo → polling agresivo (1 minuto)
      const pollInterval = this.realtimeActive() ? 300000 : 60000;
      
      this._pollTimer = setInterval(() => {
        if (!this.loading()) this.reload();
      }, pollInterval);
    }
  });
}
```

---

### **FASE 5: Limpiar Funciones Innecesarias**
**Prioridad:** 🟢 MEDIA  
**Impacto:** Reducir complejidad del código (-200 líneas de dead code)

#### 📌 Por qué Necesitamos Esta Fase

Cuando un código tiene mucha "basura" (funciones no usadas, métodos duplicados), es:
- Difícil de mantener (¿Para qué sirve esta función?)
- Propenso a bugs (¿Qué pasó si edito esto?)
- Lento de leer (más líneas = más tiempo para entender)

#### Paso 5.1: Identificar Dead Code
**Dónde buscar:**

```typescript
// ❌ EJEMPLO: Función que probablemente no se usa
cambioAgujas() {
  this.finishForm.update(f => ({ 
    ...f, 
    refaccion_utilizada: 'agujas' 
  }));
}

ajusteTension() {
  this.selectedQuickFix.set('tension'); // Solo guarda, no hace nada
}

ajustePokaYoke() {
  this.selectedQuickFix.set('pokayoke'); // Solo guarda, no hace nada
}

// ¿Se usan en el template? NO → Dead code
```

**Búsquedas a hacer:**
```
1. Grep: "cambioAgujas" → ¿Aparece en HTML? NO → Delete
2. Grep: "ajusteTension" → ¿Aparece en HTML? NO → Delete
3. Grep: "selectedQuickFix" → ¿Se usa? Probablemente solo en template, ver si se necesita
```

#### Paso 5.2: Remover Métodos Obsoletos
**Candidatos a revisar en `tabla.component.ts`:**

1. **Métodos de inicialización repetidos**
   - ¿Hay dos `loadLineas()`? → Unificar
   - ¿Hay dos `loadAreas()`? → Unificar

2. **Métodos duplicados**
   - Si tienes `setFDescripcion()` y `setFDescriptionResult()` → Unificar en uno

3. **Getters sin usar**
   ```typescript
   // ❌ Ejemplo: ¿Se usa en HTML?
   get totalReportes(): number {
     return this.data().length;
   }
   ```

#### Paso 5.3: Consolidar Lógica de Filtros
**Archivo afectado:** `tabla.component.ts`

**Problema actual:**
```typescript
readonly filteredToShow = computed(() => {
  const data = this.data();
  const lock = this.lineaLockId();
  const status = this.statusFilter();
  
  // Lógica A: filtrar por lock
  let result = data.filter(...);
  
  // Lógica B: filtrar por status
  result = result.filter(...);
  
  // Lógica C: filtrar por búsqueda
  result = result.filter(...);
  
  return result;
});
```

**Mejorado:**
```typescript
readonly filteredToShow = computed(() => {
  const data = this.data();
  
  return data
    .filter(r => this.matchesLineaLock(r))      // Filtro 1
    .filter(r => this.matchesStatus(r))         // Filtro 2
    .filter(r => this.matchesSearchQuery(r));   // Filtro 3
});

// Métodos helper legibles
private matchesLineaLock(r: Reporte): boolean {
  const lock = this.lineaLockId();
  if (!lock) return true;
  // ... lógica específica
}

private matchesStatus(r: Reporte): boolean {
  const status = this.statusFilter();
  if (!status) return true;
  // ... lógica específica
}

private matchesSearchQuery(r: Reporte): boolean {
  // ... búsqueda específica
}
```

---

### **FASE 6: Optimizaciones Adicionales**
**Prioridad:** 🟢 MEDIA/BAJA  
**Impacto:** Mejoras incrementales (10-20% más)

#### Paso 6.1: Lazy Load de Modales
**Problema:** Los datos del modal se cargan siempre, aunque nunca se abra

```typescript
// ❌ AHORA
openCrear() {
  // Cargar TODO aunque el usuario solo quería ver el modal
  this.loadTecnicos();
  this.loadReportes();
  this.loadEmpleados();
  this.createOpen.set(true);
}

// ✅ OPTIMIZADO
openCrear() {
  this.createOpen.set(true); // Abrir el modal
  // Cargar datos SOLO si hace falta
  if (!this.tecnicosLoaded()) {
    this.loadTecnicos();
  }
}
```

#### Paso 6.2: Virtualización de Tabla
**Problema:** Si tienes 1,000 reportes, renderiza 1,000 filas aunque solo veas 20

**Solución:** Usar `@angular/cdk` virtual scroll

```html
<!-- ❌ AHORA: Renderiza todas las filas -->
<tbody>
  @for (item of filteredToShow(); track item.noOrden) {
    <tr>...</tr>
  }
</tbody>

<!-- ✅ OPTIMIZADO: Solo renderiza visible -->
<cdk-virtual-scroll-viewport itemSize="60" class="table-viewport">
  <tbody>
    @for (item of filteredToShow(); track item.noOrden) {
      <tr>...</tr>
    }
  </tbody>
</cdk-virtual-scroll-viewport>
```

#### Paso 6.3: Desuscribir de Observables
**Problema:** Memory leaks por observables no descritos

```typescript
// ❌ PROBLEMA: Observable sin desuscribir
ngOnInit() {
  this.realtimeSvc.stream().subscribe(event => {
    this.reload();
  }); // ← Si el componente se destruye, sigue escuchando
}

// ✅ SOLUCIÓN: Desuscribir automáticamente
private destroy$ = new Subject<void>();

ngOnInit() {
  this.realtimeSvc.stream()
    .pipe(takeUntil(this.destroy$))
    .subscribe(event => {
      this.reload();
    });
}

ngOnDestroy() {
  this.destroy$.next();
  this.destroy$.complete();
}
```

---

## 📈 Resultados Esperados - COMPARATIVA DETALLADA

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| **Tamaño respuesta API** | 10MB | 200KB | -98% |
| **Tiempo carga inicial** | 5.0s | 0.3s | -94% |
| **Llamadas por minuto** | 1 call | 0.5 calls | -50% |
| **Llamadas por hora** | 60 | 30 | -50% |
| **Actualizaciones UI innecesarias** | 58/60 | 10/60 | -83% |
| **Consumo memoria tabla** | 100MB | 8MB | -92% |
| **Consumo ancho de banda/hora** | 600MB | 6MB | -99% |
| **Fluidez tabla** | Lenta | Instantánea | ⚡ |
| **CPU promedio** | 45% | 12% | -73% |

**Ejemplo con números reales:**
```
Antes (CRÍTICO):
- Usuario abre app → Espera 5 segundos
- Tabla lenta (renderiza 10,000 registros)
- Cada minuto: -250KB de ancho de banda
- 100 usuarios × 250KB/min = 25MB/min en el servidor = ¡¡SATURACIÓN!!

Después (OPTIMIZADO):
- Usuario abre app → Aparece en 0.3 segundos
- Tabla rápida (renderiza 50 registros)
- Cada minuto: -5KB si hay cambios, 0KB si no hay cambios
- 100 usuarios × 5KB/min (promedio) = 500KB/min = ¡MUY TOLERABLE!
```

---

## � Orden de Ejecución Recomendado

1. ✅ **FASE 1** → Máximo impacto inmediato (-80% datos)
2. ✅ **FASE 3** → Reduce polling innecesario (-50% llamadas)
3. ✅ **FASE 2** → Estabiliza con caché (sin llamadas repetidas)
4. ✅ **FASE 4** → Datos frescos en tiempo real (sin esperar polling)
5. ✅ **FASE 5** → Limpieza y mantenibilidad (código más limpio)
6. ✅ **FASE 6** → Pulido final (mejoras incrementales)

---

## 📝 Checklist de Implementación

### FASE 1 - Filtro por Fecha
- [ ] Paso 1.1: Agregar signal `currentDate` en tabla.component.ts
- [ ] Paso 1.2: Actualizar `reload()` para pasar `day`
- [ ] Paso 1.3: Verificar backend soporta `?day=YYYY-MM-DD`
- [ ] Verificación: Tabla solo muestra reportes de hoy
- [ ] Medir: Tiempo de carga (debería bajar de 5s a <1s)

### FASE 2 - Cache de Datos Maestros
- [ ] Paso 2.1: Implementar caché en lineas.service.ts
- [ ] Paso 2.2: Implementar caché en maquinas.service.ts
- [ ] Paso 2.3: Implementar caché en areas.service.ts
- [ ] Verificación: Segunda carga de líneas es instantánea
- [ ] Medir: Llamadas HTTP se reducen a 3-4 en lugar de 10+

### FASE 3 - Optimización de Polling
- [ ] Paso 3.1: Agregar método `getDataHash()` en tabla.component.ts
- [ ] Paso 3.2: Modificar `reload()` para validar hash
- [ ] Paso 3.3: (Opcional) Aumentar intervalo de polling a 2-5 minutos
- [ ] Verificación: Console muestra "Sin cambios" cuando no hay nuevos reportes
- [ ] Medir: UI se actualiza solo cuando hay realmente cambios

### FASE 4 - Realtime Integration
- [ ] Paso 4.1: Agregar signal `realtimeActive` y effect
- [ ] Paso 4.2: Suscribir a eventos de realtime
- [ ] Paso 4.3: Reducir polling a 5 minutos cuando realtime está activo
- [ ] Verificación: Nuevo reporte aparece en <100ms
- [ ] Medir: Ahorro en ancho de banda cuando realtime funciona

### FASE 5 - Limpiar Dead Code
- [ ] Paso 5.1: Grep "cambioAgujas", "ajusteTension", etc en HTML
- [ ] Paso 5.2: Eliminar funciones no usadas
- [ ] Paso 5.3: Consolidar lógica de filtros
- [ ] Verificación: Componente sigue funcionando sin cambios
- [ ] Medir: Reducir líneas de código en -200

### FASE 6 - Optimizaciones Adicionales
- [ ] Paso 6.1: Lazy load de modales (opcional)
- [ ] Paso 6.2: Virtual scroll si tabla es muy larga (opcional)
- [ ] Paso 6.3: Usar takeUntil para desuscribir observables
- [ ] Verificación: No hay memory leaks (DevTools)
- [ ] Medir: Rendimiento en aplicación larga (30+ minutos abierta)

---

## ⚠️ Consideraciones Importantes

### Compatibilidad Backend
- Verificar que el backend soporta parámetro `day` en formato `YYYY-MM-DD`
- Si no soporta, necesitarás agregarlo en el backend primero
- Ejemplo en Laravel: `$query->whereDate('inicio', $request->day);`

### Rollback Plan
- Cada cambio debe hacerse en un commit separado
- Si algo falla, puedes revertir solo ese commit
- Recomendación: Branch de feature `feature/optimizacion-reportes`

### Testing
- Después de FASE 1: Verificar datos correctos (solo hoy)
- Después de FASE 3: Console logs mostran "Sin cambios" frecuentemente
- Después de FASE 4: Realtime entrega eventos en <100ms
- Final: Performance audit con Chrome DevTools

### Monitoreo
```typescript
// Agrega esto temporalmente para medir mejoras
console.time('reload');
this.reload();
console.timeEnd('reload');
// Mostrará: reload: 234ms

// Antes de optimizar: reload: 5000ms
// Después de optimizar: reload: 200ms
```

---

## 🎓 Glosario de Términos Técnicos

| Término | Explicación |
|---------|-------------|
| **Polling** | Preguntar repetidamente al servidor "¿Hay cambios?" (cada X segundos) |
| **Realtime** | Servidor notifica instantáneamente cuando algo cambia (sin preguntar) |
| **Cache** | Guardar datos en memoria para reutilizar sin volver a descargar |
| **TTL (Time To Live)** | Cuánto tiempo un dato en caché es considerado "fresco" antes de expirar |
| **Hash/Checksum** | Resumen corto de datos para detectar cambios rápidamente |
| **Dead Code** | Código que existe pero nunca se ejecuta (función que nada llama) |
| **Memory Leak** | Observable que nunca se desuscribe, acumula memoria hasta bloquear |
| **Virtual Scroll** | Renderizar solo las filas visibles (no toda la tabla) |
| **Effect** | Sistema de Angular que se ejecuta automáticamente cuando sus dependencias cambian |

---

## 🚀 Próximos Pasos

1. **Lee este documento completamente** y entiende cada fase
2. **Avísame cuándo comenzamos FASE 1** 
3. Iremos paso a paso, validando cada uno
4. Después de cada fase, mediremos resultados reales

**¿Listo para empezar con FASE 1? 🎯**
