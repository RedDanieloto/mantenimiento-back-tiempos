<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
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

Artisan::command('telegram:reenviar-atrasados {--dry-run : Solo lista candidatos, no envia mensajes} {--incluir-enviados : Incluye reportes ya marcados con alerta_1h_enviada=1} {--solo-abiertos : Solo considera reportes con status abierto}', function () {
    $this->info('Buscando reportes atrasados (20+ minutos)...');

    $threshold = Carbon::now()->subMinutes(20);
    $hasAlertFlag = Schema::hasColumn('reportes', 'alerta_1h_enviada');
    $statuses = $this->option('solo-abiertos')
        ? ['abierto']
        : ['en_mantenimiento', 'abierto'];

    $query = Reporte::with(['maquina.linea', 'area'])
        ->whereIn('status', $statuses)
        ->where(function ($q) use ($threshold) {
            $q->where(function ($sub1) use ($threshold) {
                $sub1->whereNotNull('aceptado_en')
                    ->where('aceptado_en', '<=', $threshold);
            })->orWhere(function ($sub2) use ($threshold) {
                $sub2->whereNull('aceptado_en')
                    ->where('inicio', '<=', $threshold);
            });
        });

    if (!$this->option('incluir-enviados') && $hasAlertFlag) {
        $query->where('alerta_1h_enviada', false);
    } elseif (!$this->option('incluir-enviados') && !$hasAlertFlag) {
        $this->warn('La columna alerta_1h_enviada no existe; no se puede filtrar solo pendientes.');
    }

    $reportes = $query->orderBy('inicio')->get();

    if ($reportes->isEmpty()) {
        $this->info('No hay reportes atrasados para notificar.');
        return 0;
    }

    $this->info("Se encontraron {$reportes->count()} reporte(s).\n");

    $dryRun = (bool) $this->option('dry-run');
    $tgService = new TelegramService();
    $enviados = 0;
    $fallidos = 0;
    $now = Carbon::now();

    foreach ($reportes as $reporte) {
        /** @var \App\Models\Reporte $reporte */
        $areaName = $reporte->area?->name ?? 'Sin area';
        $nombreMaquina = $reporte->maquina ? $reporte->maquina->name : 'N/A';
        $linea = $reporte->maquina?->linea?->name ?? 'N/A';
        $tecnico = $reporte->tecnico_nombre ?: 'Sin asignar';
        $tiempo = $reporte->aceptado_en ? 'mantenimiento' : 'reaccion';
        $minutos = (int) ($reporte->aceptado_en
            ? $reporte->aceptado_en->diffInMinutes($now)
            : $reporte->inicio->diffInMinutes($now));

        if ($dryRun) {
            $this->line("[DRY RUN] #{$reporte->id} | {$nombreMaquina} | {$tiempo} | {$minutos} min");
            continue;
        }

        $mensaje = "⏳ *Alerta de Tiempo (Atrasada)*\n"
            . "El reporte #{$reporte->id} de la máquina *{$nombreMaquina}* en la linea *{$linea}* lleva {$minutos} minutos en {$tiempo}.\n"
            . "🏭 Area: {$areaName}\n"
            . "👨‍🔧 Técnico: {$tecnico}\n"
            . "📝 Falla: {$reporte->descripcion_falla}";

        $sent = $tgService->sendMessageByArea($mensaje, $areaName);

        if ($sent) {
            if ($hasAlertFlag) {
                $reporte->alerta_1h_enviada = true;
                $reporte->save();
            }
            $enviados++;
            $this->line("✅ Enviado reporte #{$reporte->id}");
            continue;
        }

        $fallidos++;
        $this->error("❌ Fallo reporte #{$reporte->id}");
    }

    if ($dryRun) {
        $this->info("\nDry run finalizado. No se envio ningun mensaje.");
        return 0;
    }

    $this->info("\nProceso finalizado. Enviados: {$enviados}. Fallidos: {$fallidos}.");
    return $fallidos > 0 ? 1 : 0;
})->purpose('Reenviar alertas atrasadas de reportes con 20+ minutos');

Schedule::call(function () {
    if (!Schema::hasColumn('reportes', 'alerta_1h_enviada')) {
        Log::warning('Scheduler de Telegram omitido: falta columna reportes.alerta_1h_enviada. Ejecuta migraciones.');
        return;
    }

    $reportes = Reporte::with(['maquina.linea', 'area'])
        ->whereIn('status', ['en_mantenimiento', 'abierto'])
        ->where(function($q) {
            $q->where(function($sub1) {
                $sub1->whereNotNull('aceptado_en')
                     ->where('aceptado_en', '<=', Carbon::now()->subMinutes(20));
            })->orWhere(function($sub2) {
                $sub2->whereNull('aceptado_en')
                     ->where('inicio', '<=', Carbon::now()->subMinutes(20));
            });
        })
        ->where('alerta_1h_enviada', false)
        ->get();

    if ($reportes->isEmpty()) return;

    $tgService = new TelegramService();

    foreach ($reportes as $reporte) {
        /** @var \App\Models\Reporte $reporte */
        $areaName = $reporte->area?->name ?? 'Sin area';
        $nombreMaquina = $reporte->maquina ? $reporte->maquina->name : 'N/A';
        $linea = $reporte->maquina?->linea?->name ?? 'N/A';
        $tecnico = $reporte->tecnico_nombre ?: 'Sin asignar';
        $tiempo = $reporte->aceptado_en ? 'mantenimiento' : 'reacción';
        
        $mensaje = "⏳ *Alerta de Tiempo*\n"
                 . "El reporte #{$reporte->id} de la máquina *{$nombreMaquina}* en la linea *{$linea}* lleva más de 20 minutos en {$tiempo}.\n"
                 . "🏭 Area: {$areaName}\n"
                 . "👨‍🔧 Técnico: {$tecnico}\n"
                 . "📝 Falla: {$reporte->descripcion_falla}";

        $sent = $tgService->sendMessageByArea($mensaje, $areaName);

        if ($sent) {
            $reporte->alerta_1h_enviada = true;
            $reporte->save();
        }
    }
})->everyMinute();
