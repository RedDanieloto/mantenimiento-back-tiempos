# Realtime (Pusher) - Guía de integración Frontend

Esta app emite eventos en tiempo real usando Pusher. Ya hay 3 eventos del ciclo de vida de reportes:

- `reporte.created`
- `reporte.accepted`
- `reporte.finished`

Todos viajan por el canal público `reportes` (Channel). Si requieres seguridad por área/rol, podemos migrarlo a `PrivateChannel` y añadir autenticación.

## Variables de entorno (backend)

Asegúrate de tener en `.env`:

```
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=xxxx
PUSHER_APP_KEY=xxxx
PUSHER_APP_SECRET=xxxx
PUSHER_APP_CLUSTER=mt1
```

Nota: no publiques tus llaves en documentos. Usa solo variables de entorno. Si tu clúster real es, por ejemplo, `us2`, actualiza `PUSHER_APP_CLUSTER=us2`.

## Eventos que emite el backend

Payload: es el reporte completo con relaciones (`user`, `tecnico`, `maquina.linea.area`) y campos calculados según el modelo. Ejemplo simplificado:

```json
{
  "id": 123,
  "status": "abierto|en_mantenimiento|OK",
  "employee_number": 7218,
  "maquina_id": 10,
  "inicio": "2025-09-27T10:15:00-06:00",
  "user": { "employee_number": 7218, "name": "Líder" },
  "tecnico": { "employee_number": 6685, "name": "Técnico" },
  "maquina": { "id": 10, "name": "5115", "linea": { "name": "P13C", "area": { "name": "Costura"}}}
}
```

## Opción A: Pusher JS (vanilla)

Incluye el SDK:

```html
<script src="https://js.pusher.com/8.4/pusher.min.js"></script>
```

Conéctate y suscríbete:

```js
const pusher = new Pusher(import.meta?.env?.VITE_PUSHER_APP_KEY || 'YOUR_KEY', {
  cluster: import.meta?.env?.VITE_PUSHER_APP_CLUSTER || 'mt1',
  forceTLS: true,
});

const channel = pusher.subscribe('reportes');
channel.bind('reporte.created', (data) => {
  console.log('Nuevo reporte', data);
  // TODO: refrescar tabla/lista, o mutar el estado de las gráficas
});
channel.bind('reporte.accepted', (data) => {
  console.log('Reporte aceptado', data);
});
channel.bind('reporte.finished', (data) => {
  console.log('Reporte finalizado', data);
});
```

## Opción B: Laravel Echo (recomendada en apps Laravel)

Instala en tu frontend:

```
npm i laravel-echo pusher-js
```

Bootstrap:

```js
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

window.Echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
  forceTLS: true,
})

window.Echo.channel('reportes')
  .listen('.reporte.created', e => console.log('created', e))
  .listen('.reporte.accepted', e => console.log('accepted', e))
  .listen('.reporte.finished', e => console.log('finished', e))
```

Nota: con `broadcastAs()` los eventos llevan prefijo `.` al escucharlos en Echo.

## Cómo reaccionar en el dashboard

- Estrategia simple: al recibir un evento, hace un `fetch` a `/api/reportes?...` con los mismos filtros actuales y actualiza datasets.
- Estrategia optimizada: muta el estado en memoria (agregar/actualizar/eliminar un reporte en la colección y re-calcular las métricas afectadas).

## Pruebas manuales

1. Abre `/graficas` y la consola del navegador.
2. Crea un reporte desde el API o UI → deberías ver `reporte.created` llegar.
3. Acepta y finaliza el reporte → verás `reporte.accepted` y `reporte.finished`.

## Endurecer seguridad (opcional)

- Usa `PrivateChannel` y define un callback de auth en `routes/channels.php`.
- Requiere firma del backend para suscripción privada; en frontend usa Echo con auth `withCredentials` o token Bearer.
