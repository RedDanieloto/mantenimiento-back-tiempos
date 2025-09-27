<?php

namespace App\Events;

use App\Models\Reporte;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReporteFinished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $reporte;

    public function __construct(Reporte $reporte)
    {
        $this->reporte = $reporte->load(['user','tecnico','maquina.linea.area'])->toArray();
    }

    public function broadcastOn(): Channel
    {
        return new Channel('reportes');
    }

    public function broadcastAs(): string
    {
        return 'reporte.finished';
    }
}
