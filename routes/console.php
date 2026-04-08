<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
use App\Models\Reporte;
use App\Services\TelegramService;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('telegram:test', function () {
    $this->info('Enviando mensaje de prueba a Telegram...');
    
    $tgService = new TelegramService();
    $sent = $tgService->sendMessage("Hola este mensaje es de prueba para saber si funciona la conexion");
    
    if ($sent) {
        $this->info('✅ ¡Mensaje enviado con éxito! Revisa tu celular.');
    } else {
        $this->error('❌ Error al enviar el mensaje. Revisa tu .env y los logs en storage/logs/laravel.log');
    }
})->purpose('Test Telegram Bot credentials sending a message');

Schedule::call(function () {
    $reportes = Reporte::with('maquina')
        ->whereIn('status', ['en_mantenimiento', 'abierto'])
        ->where(function($q) {
            $q->where(function($sub1) {
                $sub1->whereNotNull('aceptado_en')
                     ->where('aceptado_en', '<=', Carbon::now()->subMinutes(60));
            })->orWhere(function($sub2) {
                $sub2->whereNull('aceptado_en')
                     ->where('inicio', '<=', Carbon::now()->subMinutes(60));
            });
        })
        ->where('alerta_1h_enviada', false)
        ->get();

    if ($reportes->isEmpty()) return;

    $tgService = new TelegramService();

    foreach ($reportes as $reporte) {
        /** @var \App\Models\Reporte $reporte */
        $nombreMaquina = $reporte->maquina ? $reporte->maquina->name : 'N/A';
        $tecnico = $reporte->tecnico_nombre ?: 'Sin asignar';
        $tiempo = $reporte->aceptado_en ? 'mantenimiento' : 'espera';
        
        $mensaje = "⏳ *Alerta de Tiempo*\n"
                 . "El reporte #{$reporte->id} de la máquina *{$nombreMaquina}* lleva más de 1 hora en {$tiempo}.\n"
                 . "👨‍🔧 Técnico: {$tecnico}\n"
                 . "📝 Falla: {$reporte->descripcion_falla}";

        $sent = $tgService->sendMessage($mensaje);

        if ($sent) {
            $reporte->alerta_1h_enviada = true;
            $reporte->save();
        }
    }
})->everyMinute();
