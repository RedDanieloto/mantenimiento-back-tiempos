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
        'departamento',
        'turno',
        'descripcion_falla',
        'descripcion_resultado',
        'refaccion_utilizada',
        'inicio',
        'aceptado_en',
        'fin',
    ];

    protected $casts = [
        'inicio' => 'datetime',
        'aceptado_en' => 'datetime',
        'fin' => 'datetime',
    ];

    protected $appends = [
        'lider_nombre',
        'tecnico_nombre',
        'tiempo_reaccion_segundos',
        'tiempo_mantenimiento_segundos',
        'tiempo_total_segundos',
    ];

    //Un reporte pertenece a un usuario
    public function user()
    {
        // Foreign key on reportes is 'employee_number' referencing users.employee_number
        return $this->belongsTo(User::class, 'employee_number', 'employee_number');
    }

    //Un reporte pertenece a una maquina
    public function maquina()
    {
        return $this->belongsTo(Maquina::class);
    }

    //Un reporte pertenece a un area
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    // Un reporte tiene un técnico asignado (opcional)
    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_employee_number', 'employee_number');
    }

    // Un reporte puede tener un herramental asociado (opcional, solo si falla es de herramental)
    public function herramental()
    {
        return $this->belongsTo(herramental::class);
    }

    // Nombres convenientes
    public function getLiderNombreAttribute(): ?string
    {
        return $this->user->name ?? null;
    }

    public function getTecnicoNombreAttribute(): ?string
    {
        return $this->tecnico->name ?? null;
    }

    // Tiempos calculados en segundos
    public function getTiempoReaccionSegundosAttribute(): ?int
    {
        if (!$this->inicio) return null;
        $to = $this->aceptado_en ?: now();
        return $this->inicio->diffInSeconds($to);
    }

    public function getTiempoMantenimientoSegundosAttribute(): ?int
    {
        if (!$this->aceptado_en) return null;
        $to = $this->fin ?: now();
        return $this->aceptado_en->diffInSeconds($to);
    }

    public function getTiempoTotalSegundosAttribute(): ?int
    {
        if (!$this->inicio) return null;
        $to = $this->fin ?: now();
        return $this->inicio->diffInSeconds($to);
    }
}
