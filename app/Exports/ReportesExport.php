<?php

namespace App\Exports;

use App\Models\Reporte;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Concerns\Exportable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    protected Request $request;
    protected string $tz = 'America/Mexico_City';

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $q = Reporte::query()->with(['user','tecnico','maquina.linea.area']);

        // --- Filtros espejo del controller ---
        if ($this->request->filled('id')) {
            $ids = collect(explode(',', $this->request->string('id')))->map('intval')->filter()->values()->all();
            if ($ids) $q->whereIn('id', $ids);
        }
        if ($this->request->filled('status')) {
            $statuses = collect(explode(',', $this->request->string('status')))
                ->map(function ($s) {
                    $s = strtolower(trim($s));
                    return match ($s) {
                        'ok' => 'OK',
                        'mtto', 'en_mantenimiento' => 'en_mantenimiento',
                        'abierto' => 'abierto',
                        default => $s,
                    };
                })
                ->filter()->values()->all();
            if ($statuses) $q->whereIn('status', $statuses);
        }

        if ($this->request->filled('turno')) {
            $turnos = collect(explode(',', $this->request->string('turno')))
                ->map(fn($t) => trim($t))->filter()->values()->all();
            if ($turnos) $q->whereIn('turno', $turnos);
        }

        if ($this->request->filled('area_id')) {
            $q->whereIn('area_id', explode(',', $this->request->string('area_id')));
        }

        if ($this->request->filled('maquina_id')) {
            $q->whereIn('maquina_id', explode(',', $this->request->string('maquina_id')));
        }

        if ($this->request->filled('linea_id')) {
            $lineas = explode(',', $this->request->string('linea_id'));
            $q->whereHas('maquina', fn($mq) => $mq->whereIn('linea_id', $lineas));
        }

        if ($this->request->filled('employee_number')) {
            $q->whereIn('employee_number', explode(',', $this->request->string('employee_number')));
        }

        if ($this->request->filled('tecnico_employee_number')) {
            $q->whereIn('tecnico_employee_number', explode(',', $this->request->string('tecnico_employee_number')));
        }

        if ($this->request->filled('has_tecnico')) {
            $flag = filter_var($this->request->string('has_tecnico'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($flag === true) $q->whereNotNull('tecnico_employee_number');
            elseif ($flag === false) $q->whereNull('tecnico_employee_number');
        }
        if ($this->request->filled('has_fin')) {
            $flag = filter_var($this->request->string('has_fin'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($flag === true) $q->whereNotNull('fin');
            elseif ($flag === false) $q->whereNull('fin');
        }

        if ($this->request->filled('q')) {
            $term = '%' . str_replace(' ', '%', $this->request->string('q')) . '%';
            $q->where(function ($w) use ($term) {
                $w->where('falla', 'like', $term)
                  ->orWhere('descripcion_falla', 'like', $term)
                  ->orWhere('descripcion_resultado', 'like', $term)
                  ->orWhere('refaccion_utilizada', 'like', $term)
                  ->orWhere('lider_nombre', 'like', $term)
                  ->orWhere('tecnico_nombre', 'like', $term)
                  ->orWhereHas('maquina', fn($mq) => $mq->where('name', 'like', $term))
                  ->orWhereHas('maquina.linea', fn($lq) => $lq->where('name', 'like', $term))
                  ->orWhereHas('maquina.linea.area', fn($aq) => $aq->where('name', 'like', $term));
            });
        }

        $dateField = 'inicio';
        if ($this->request->filled('day')) {
            $start = Carbon::parse($this->request->string('day'), $this->tz)->setTime(7, 0, 0);
            $end   = (clone $start)->addDay();
            $q->whereBetween($dateField, [$start, $end]);
        } elseif ($this->request->filled('from') || $this->request->filled('to')) {
            $fromDay = $this->request->string('from');
            $toDay   = $this->request->string('to', $fromDay);
            if ($fromDay) {
                $start = Carbon::parse($fromDay, $this->tz)->setTime(7, 0, 0);
                $end   = Carbon::parse($toDay, $this->tz)->setTime(7, 0, 0)->addDay();
                $q->whereBetween($dateField, [$start, $end]);
            }
        } elseif ($this->request->filled('week')) { // ISO week YYYY-Www
            $weekStr = (string)$this->request->input('week');
            if (preg_match('/^(\d{4})-W(\d{2})$/', $weekStr, $m)) {
                $year = (int)$m[1];
                $week = (int)$m[2];
                $start = Carbon::now($this->tz)->setISODate($year, $week, 1)->setTime(7, 0, 0);
                $end   = (clone $start)->addDays(7);
                $q->whereBetween($dateField, [$start, $end]);
            }
        } elseif ($this->request->filled('month')) { // YYYY-MM
            $month = $this->request->input('month');
            try {
                $start = Carbon::parse($month.'-01', $this->tz)->setTime(7, 0, 0);
                $end   = (clone $start)->addMonth();
                $q->whereBetween($dateField, [$start, $end]);
            } catch (\Throwable $e) {
                // formato inválido: ignorar
            }
        }

        if ($this->request->filled('hour_from') || $this->request->filled('hour_to')) {
            $hf = max(0, min(23, (int)$this->request->integer('hour_from', 0)));
            $ht = max(0, min(23, (int)$this->request->integer('hour_to', 23)));
            $q->whereRaw('HOUR(inicio) BETWEEN ? AND ?', [$hf, $ht]);
        }

        if ($this->request->filled('shift')) {
            $shift = strtolower($this->request->string('shift'));
            if ($shift === '1') {
                $q->whereRaw('(TIME(inicio) >= "07:00:00" AND TIME(inicio) < "15:00:00")');
            } elseif ($shift === '2') {
                $q->whereRaw('(TIME(inicio) >= "15:00:00" AND TIME(inicio) < "23:00:00")');
            } elseif ($shift === '3') {
                $q->whereRaw('(TIME(inicio) >= "23:00:00" OR TIME(inicio) < "07:00:00")');
            }
        }

        $sortBy = $this->request->string('sort_by', 'inicio');
        $sortDir = strtolower($this->request->string('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (!in_array($sortBy, ['inicio', 'aceptado_en', 'fin', 'status', 'maquina_id', 'area_id', 'created_at', 'updated_at'], true)) {
            $sortBy = 'inicio';
        }
        return $q->orderBy($sortBy, $sortDir);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Status',
            'Líder #',
            'Líder Nombre',
            'Técnico #',
            'Técnico Nombre',
            'Área',
            'Línea',
            'Máquina',
            'Turno',
            'Falla',
            'Departamento',
            'Descripción Falla',
            'Descripción Resultado',
            'Refacción Utilizada',
            'Inicio',
            'Aceptado En',
            'Fin',
            'T. Reacción (min)',
            'T. Mantenimiento (min)',
            'T. Total (min)',
        ];
    }

    public function map($r): array
    {
        $maquina = $r->maquina;
        $linea   = optional($maquina)->linea;
        $area    = optional($linea)->area;

        $inicio = $r->inicio ? Carbon::parse($r->inicio) : null;
        $acept  = $r->aceptado_en ? Carbon::parse($r->aceptado_en) : null;
        $fin    = $r->fin ? Carbon::parse($r->fin) : null;

        $mins = fn($secs) => is_null($secs) ? null : floor($secs / 60);

        $tReaccion = ($inicio && $acept) ? $mins($inicio->diffInSeconds($acept, false)) : null;
        $tMantto   = ($acept && $fin) ? $mins($acept->diffInSeconds($fin, false)) : null;
        $tTotal    = ($inicio && $fin) ? $mins($inicio->diffInSeconds($fin, false)) : null;

        $inicioStr = $inicio ? $inicio->setTimezone($this->tz)->toDateTimeString() : null;
        $aceptStr  = $acept ? $acept->setTimezone($this->tz)->toDateTimeString() : null;
        $finStr    = $fin ? $fin->setTimezone($this->tz)->toDateTimeString() : null;

        return [
            $r->id,
            $r->status,
            $r->employee_number,
            $r->lider_nombre,
            $r->tecnico_employee_number,
            $r->tecnico_nombre,
            optional($area)->name,
            optional($linea)->name,
            optional($maquina)->name,
            $r->turno,
            $r->falla,
            $r->departamento,
            $r->descripcion_falla,
            $r->descripcion_resultado,
            $r->refaccion_utilizada,
            $inicioStr,
            $aceptStr,
            $finStr,
            $tReaccion,
            $tMantto,
            $tTotal,
        ];
    }

}
