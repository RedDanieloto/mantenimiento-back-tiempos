<?php

namespace App\Events;

use App\Models\Reporte;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReporteCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $reporte;

    public function __construct(Reporte $reporte)
    {
        $this->reporte = $reporte->load(['user','tecnico','maquina.linea.area'])->toArray();
    }

    public function broadcastOn(): Channel
    {
        return new Channel('reportes'); // público para demo; usa PrivateChannel si se requiere auth
    }

    public function broadcastAs(): string
    {
        return 'reporte.created';
    }
}
