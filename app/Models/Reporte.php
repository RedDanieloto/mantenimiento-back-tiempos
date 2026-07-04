<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reporte extends Model
{
    protected $fillable = [
        'employee_number',
        'tecnico_employee_number',
        'area_id',
        'maquina_id',
        'herramental_id',
        'status',
        'falla',
        'scrap',
        'departamento',
        'turno',
        'descripcion_falla',
        'descripcion_resultado',
        'refaccion_utilizada',
        'inicio',
        'aceptado_en',
        'fin',
        'lider_nombre',
        'tecnico_nombre',
        'alerta_1h_enviada',
    ];

    protected $casts = [
        'scrap' => 'integer',
        'alerta_1h_enviada' => 'boolean',
        'inicio' => 'datetime',
        'aceptado_en' => 'datetime',
        'fin' => 'datetime',
    ];

    // Campos computados cargados automaticamente
    protected $appends = [
        'tiempo_reaccion_segundos',
        'tiempo_mantenimiento_segundos',
        'tiempo_total_segundos',
    ];

    // Relacion con el usuario creador del reporte
    public function user()
    {
        return $this->belongsTo(User::class, 'employee_number', 'employee_number');
    }

    // Relacion con la maquina del reporte
    public function maquina()
    {
        return $this->belongsTo(Maquina::class);
    }

    // Relacion con el area del reporte
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    // Relacion con el tecnico asignado
    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_employee_number', 'employee_number');
    }

    // Relacion con el herramental asociado
    public function herramental()
    {
        return $this->belongsTo(Herramental::class);
    }

    // Obtiene el tiempo de reaccion en segundos
    public function getTiempoReaccionSegundosAttribute(): ?int
    {
        if (!$this->inicio) return null;
        $to = $this->aceptado_en ?: now();
        return (int) abs($this->inicio->diffInSeconds($to));
    }

    // Obtiene el tiempo de mantenimiento en segundos
    public function getTiempoMantenimientoSegundosAttribute(): ?int
    {
        if (!$this->aceptado_en) return null;
        $to = $this->fin ?: now();
        return (int) abs($this->aceptado_en->diffInSeconds($to));
    }

    // Obtiene el tiempo total de paro en segundos
    public function getTiempoTotalSegundosAttribute(): ?int
    {
        if (!$this->inicio) return null;
        $to = $this->fin ?: now();
        return (int) abs($this->inicio->diffInSeconds($to));
    }
}
