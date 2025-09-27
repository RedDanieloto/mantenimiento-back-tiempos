<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use App\Models\Maquina;
use App\Models\Linea;
use App\Models\Area;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
// Websockets removed: no broadcasting events


class ReporteController extends Controller
{
    private string $tz = 'America/Mexico_City';

    /** Mapea variantes de texto a los estados internos */
    private function normalizeStatus(?string $s): ?string
    {
        if ($s === null) return null;
        $s = strtolower(trim($s));
        return match ($s) {
            'ok', 'o.k.', 'okay'         => 'OK',
            'mtto', 'mantenimiento'      => 'en_mantenimiento',
            'en_mantenimiento'           => 'en_mantenimiento',
            'abierto', 'open'            => 'abierto',
            default                      => $s,
        };
    }

    // =========================================================
    // GET /reportes  (lista con filtros)
    // =========================================================
    public function index(Request $request)
    {
        $q = Reporte::with(['user', 'tecnico', 'maquina.linea.area']);

        // ----- Filtros básicos -----
        if ($request->filled('id')) {
            $ids = collect(explode(',', $request->string('id')))->map('intval')->filter()->values()->all();
            if ($ids) $q->whereIn('id', $ids);
        }
        if ($request->filled('status')) {
            $statuses = collect(explode(',', $request->string('status')))
                ->map(fn($s) => $this->normalizeStatus($s))
                ->filter()->values()->all();
            if ($statuses) $q->whereIn('status', $statuses);
        }

        if ($request->filled('turno')) {
            $turnos = collect(explode(',', $request->string('turno')))
                ->map(fn($t) => trim($t))
                ->filter()->values()->all();
            if ($turnos) $q->whereIn('turno', $turnos);
        }

        if ($request->filled('area_id')) {
            $q->whereIn('area_id', explode(',', $request->string('area_id')));
        }

        if ($request->filled('maquina_id')) {
            $q->whereIn('maquina_id', explode(',', $request->string('maquina_id')));
        }

        if ($request->filled('linea_id')) {
            $lineas = explode(',', $request->string('linea_id'));
            $q->whereHas('maquina', fn($mq) => $mq->whereIn('linea_id', $lineas));
        }

        if ($request->filled('employee_number')) {
            $q->whereIn('employee_number', explode(',', $request->string('employee_number')));
        }

        if ($request->filled('tecnico_employee_number')) {
            $q->whereIn('tecnico_employee_number', explode(',', $request->string('tecnico_employee_number')));
        }

        // Presencia de datos
        if ($request->filled('has_tecnico')) {
            $flag = filter_var($request->string('has_tecnico'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($flag === true) $q->whereNotNull('tecnico_employee_number');
            elseif ($flag === false) $q->whereNull('tecnico_employee_number');
        }
        if ($request->filled('has_fin')) {
            $flag = filter_var($request->string('has_fin'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($flag === true) $q->whereNotNull('fin');
            elseif ($flag === false) $q->whereNull('fin');
        }

        // ----- Búsqueda de texto -----
        if ($request->filled('q')) {
            $term = '%' . str_replace(' ', '%', $request->string('q')) . '%';
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

        // ----- Filtro por fecha (ventana 7am–7am) sobre 'inicio' -----
        $dateField = 'inicio';
        if ($request->filled('day')) {
            $start = Carbon::parse($request->string('day'), $this->tz)->setTime(7, 0, 0);
            $end   = (clone $start)->addDay();
            $q->whereBetween($dateField, [$start, $end]);
        } elseif ($request->filled('from') || $request->filled('to')) {
            $fromDay = $request->string('from');
            $toDay   = $request->string('to', $fromDay);
            if ($fromDay) {
                $start = Carbon::parse($fromDay, $this->tz)->setTime(7, 0, 0);
                $end   = Carbon::parse($toDay, $this->tz)->setTime(7, 0, 0)->addDay();
                $q->whereBetween($dateField, [$start, $end]);
            }
        }

        // Rango de horas dentro del día (aplicado sobre 'inicio' en su fecha)
        if ($request->filled('hour_from') || $request->filled('hour_to')) {
            $hf = max(0, min(23, (int)$request->integer('hour_from', 0)));
            $ht = max(0, min(23, (int)$request->integer('hour_to', 23)));
            $q->whereRaw('HOUR(inicio) BETWEEN ? AND ?', [$hf, $ht]);
        }

        // Bucket de turno por hora (shift)
        if ($request->filled('shift')) {
            $shift = strtolower($request->string('shift'));
            if ($shift === '1') {
                $q->whereRaw('(TIME(inicio) >= "07:00:00" AND TIME(inicio) < "15:00:00")');
            } elseif ($shift === '2') {
                $q->whereRaw('(TIME(inicio) >= "15:00:00" AND TIME(inicio) < "23:00:00")');
            } elseif ($shift === '3') {
                $q->whereRaw('(TIME(inicio) >= "23:00:00" OR TIME(inicio) < "07:00:00")');
            }
        }

        // ----- Ordenamiento -----
        $sortBy = $request->string('sort_by', 'inicio');
        $sortDir = strtolower($request->string('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (!in_array($sortBy, ['inicio', 'aceptado_en', 'fin', 'status', 'maquina_id', 'area_id', 'created_at', 'updated_at'], true)) {
            $sortBy = 'inicio';
        }
        $q->orderBy($sortBy, $sortDir);

        // ----- Paginación opcional -----
        if ($request->boolean('paginate')) {
            $perPage = (int) $request->integer('per_page', 15);
            $p = $q->paginate($perPage);
            $p->getCollection()->transform(fn(Reporte $r) => $this->presentReporteOut($request, $r));
            return response()->json($p);
        }

        // Selección de campos opcional (para payloads ligeros)
        if ($request->filled('select')) {
            $columns = collect(explode(',', $request->string('select')))->map(fn($c) => trim($c))->filter()->values()->all();
            if ($columns) $q->select($columns);
        }

        $reportes = $q->get();
        $data = $reportes->map(fn(Reporte $r) => $this->presentReporteOut($request, $r));
        return response()->json($data);
    }

    /** Presenta un reporte con TODO: atributos crudos, relaciones completas y calculados */
    private function presentReporte(Reporte $r): array
    {
        // Asegura relaciones cargadas
        $r->loadMissing(['user', 'tecnico', 'maquina.linea.area']);

        // Conversión base: incluye appends (nombres y tiempos)
        $data = $r->toArray();

        // Normaliza fechas a ISO 8601
        foreach (['inicio','aceptado_en','fin','created_at','updated_at'] as $f) {
            $data[$f] = $r->$f ? $r->$f->toIso8601String() : null;
        }

        // Relaciones ya vienen en $data: user, tecnico, maquina{ linea{ area } }
        // Añadimos alias planos convenientes
        $data['maquina_nombre'] = optional($r->maquina)->name;
        $data['linea_nombre']   = optional(optional($r->maquina)->linea)->name;
        $data['area_nombre']    = optional(optional(optional($r->maquina)->linea)->area)->name;

        return $data;
    }

    /** Presentación bonita (agrupada por secciones) */
    private function presentReportePretty(Reporte $r): array
    {
        $r->loadMissing(['user', 'tecnico', 'maquina.linea.area']);
        $maquina = $r->maquina;
        $linea   = optional($maquina)->linea;
        $area    = optional($linea)->area;

        return [
            'id' => $r->id,
            'status' => $r->status,
            'leader' => $r->user ? [
                'employee_number' => $r->user->employee_number,
                'name' => $r->user->name,
                'role' => $r->user->role,
                'turno' => $r->user->turno,
            ] : null,
            'technician' => $r->tecnico ? [
                'employee_number' => $r->tecnico->employee_number,
                'name' => $r->tecnico->name,
                'role' => $r->tecnico->role,
                'turno' => $r->tecnico->turno,
            ] : null,
            'location' => [
                'area' => $area ? ['id' => $area->id, 'name' => $area->name] : null,
                'linea' => $linea ? ['id' => $linea->id, 'name' => $linea->name] : null,
                'maquina' => $maquina ? ['id' => $maquina->id, 'name' => $maquina->name] : null,
            ],
            'refs' => [
                'area_id' => $r->area_id,
                'linea_id' => $linea?->id,
                'maquina_id' => $r->maquina_id,
                'employee_number' => $r->employee_number,
                'tecnico_employee_number' => $r->tecnico_employee_number,
            ],
            'details' => [
                'turno' => $r->turno,
                'falla' => $r->falla,
                'departamento' => $r->departamento,
                'descripcion_falla' => $r->descripcion_falla,
                'descripcion_resultado' => $r->descripcion_resultado,
                'refaccion_utilizada' => $r->refaccion_utilizada,
            ],
            'timestamps' => [
                'inicio' => optional($r->inicio)->toIso8601String(),
                'aceptado_en' => optional($r->aceptado_en)->toIso8601String(),
                'fin' => optional($r->fin)->toIso8601String(),
                'created_at' => optional($r->created_at)->toIso8601String(),
                'updated_at' => optional($r->updated_at)->toIso8601String(),
            ],
            'metrics' => [
                'reaction_seconds' => $r->tiempo_reaccion_segundos,
                'maintenance_seconds' => $r->tiempo_mantenimiento_segundos,
                'total_seconds' => $r->tiempo_total_segundos,
            ],
            'display' => [
                'lider_nombre' => $r->lider_nombre,
                'tecnico_nombre' => $r->tecnico_nombre,
                'area_nombre' => $area?->name,
                'linea_nombre' => $linea?->name,
                'maquina_nombre' => $maquina?->name,
            ],
        ];
    }

    private function presentReporteOut(Request $request, Reporte $r): array
    {
        $pretty = $request->boolean('pretty') || strtolower((string)$request->string('view')) === 'pretty';
        return $pretty ? $this->presentReportePretty($r) : $this->presentReporte($r);
    }

    // =========================================================
    // GET /reportes/lookup  (autocompletado: maquina/linea/empleados)
    // =========================================================
    public function lookup(Request $request)
    {
        $out = [];

        if ($request->filled('maquina_id')) {
            $m = Maquina::with('linea.area')->find($request->integer('maquina_id'));
            if ($m) {
                $out['maquina'] = ['id' => $m->id, 'name' => $m->name];
                $out['linea']   = ['id' => optional($m->linea)->id, 'name' => optional($m->linea)->name];
                $out['area']    = ['id' => optional(optional($m->linea)->area)->id, 'name' => optional(optional($m->linea)->area)->name];
            }
        }

        if ($request->filled('linea_id')) {
            $l = Linea::with(['area', 'maquinas:id,name,linea_id'])->find($request->integer('linea_id'));
            if ($l) {
                $out['linea']    = ['id' => $l->id, 'name' => $l->name];
                $out['area']     = ['id' => optional($l->area)->id, 'name' => optional($l->area)->name];
                $out['maquinas'] = $l->maquinas->map(fn($m) => ['id' => $m->id, 'name' => $m->name])->values();
            }
        }

        if ($request->filled('employee_number')) {
            $u = User::where('employee_number', $request->integer('employee_number'))->first();
            if ($u) $out['lider'] = ['employee_number' => $u->employee_number, 'name' => $u->name];
        }

        if ($request->filled('tecnico_employee_number')) {
            $t = User::where('employee_number', $request->integer('tecnico_employee_number'))->first();
            if ($t) $out['tecnico'] = ['employee_number' => $t->employee_number, 'name' => $t->name];
        }

        return response()->json($out);
    }

    // =========================================================
    // POST /reportes  (crear por líder, con regla 15 min)
    // =========================================================
    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'employee_number'   => 'required|integer|digits:4|exists:users,employee_number',
            'maquina_id'        => 'required|integer|exists:maquinas,id',
            'turno'             => 'required|string|',
            'descripcion_falla' => 'required|string',
        ])->validate();

        // Regla de rol: solo líderes pueden crear
        $creator = User::where('employee_number', $data['employee_number'])->firstOrFail();
        if (strtolower((string)$creator->role) !== 'lider' && strtolower((string)$creator->role) !== 'líder' && strtolower((string)$creator->role) !== 'leader') {
            return response()->json(['message' => 'Solo los líderes pueden crear reportes.'], 403);
        }

        // Bloqueo: misma máquina en < 15 minutos
        $now = now();
        $duplicado = Reporte::where('maquina_id', $data['maquina_id'])
            ->where('inicio', '>=', (clone $now)->subMinutes(15))
            ->exists();
        if ($duplicado) {
            return response()->json(['message' => 'Ya existe un reporte para esta máquina en los últimos 15 minutos.'], 422);
        }

    $user    = $creator; // ya validado como líder
        $maquina = Maquina::with('linea.area')->findOrFail($data['maquina_id']);
        $areaId  = optional(optional($maquina->linea)->area)->id;

        $reporte = null;
        DB::transaction(function () use (&$reporte, $data, $user, $maquina, $areaId) {
            $reporte = Reporte::create([
                'employee_number'         => $user->employee_number,
                'lider_nombre'            => $user->name,
                'area_id'                 => $areaId,
                'maquina_id'              => $maquina->id,
                'status'                  => 'abierto',
                'falla'                   => 'por definir',
                'departamento'            => null,
                'turno'                   => $data['turno'],
                'descripcion_falla'       => $data['descripcion_falla'],
                'descripcion_resultado'   => '',
                'refaccion_utilizada'     => null,
                'inicio'                  => now(),
                'fin'                     => null,
                'aceptado_en'             => null,
                'tecnico_employee_number' => null,
                'tecnico_nombre'          => null,
            ]);
        });

        $reporte->load(['user','tecnico','maquina.linea.area']);
        // Broadcasting disabled
        return response()->json($this->presentReporteOut($request, $reporte), 201);
    }

    // =========================================================
    // POST /reportes/{reporte}/aceptar  (técnico toma la orden)
    // =========================================================
    public function accept(Request $request, Reporte $reporte)
    {
        $data = Validator::make($request->all(), [
            'tecnico_employee_number' => 'required|integer|digits:4|exists:users,employee_number',
        ])->validate();

        // Regla de rol: solo técnicos pueden aceptar
        $tec = User::where('employee_number', $data['tecnico_employee_number'])->firstOrFail();
        if (strtolower((string)$tec->role) !== 'tecnico' && strtolower((string)$tec->role) !== 'técnico' && strtolower((string)$tec->role) !== 'technician') {
            return response()->json(['message' => 'Solo los técnicos pueden aceptar reportes.'], 403);
        }

        if ($reporte->aceptado_en) {
            return response()->json(['message' => 'El reporte ya fue aceptado por un técnico.'], 409);
        }
        if ($reporte->status === 'OK') {
            return response()->json(['message' => 'El reporte ya fue finalizado.'], 409);
        }

        $reporte->update([
            'tecnico_employee_number' => $tec->employee_number,
            'tecnico_nombre'          => $tec->name,
            'aceptado_en'             => now(),
            'status'                  => 'en_mantenimiento',
        ]);

        $fresh = $reporte->fresh(['user','tecnico','maquina.linea.area']);
        // Broadcasting disabled
        return response()->json($this->presentReporteOut($request, $fresh));
    }

    // =========================================================
    // POST /reportes/{reporte}/finalizar  (técnico cierra)
    // =========================================================
    public function finish(Request $request, Reporte $reporte)
    {
        $data = Validator::make($request->all(), [
            'descripcion_resultado' => 'required|string',
            'refaccion_utilizada'   => 'nullable|string',
            'departamento'          => 'required|string',
        ])->validate();

        if ($reporte->status === 'OK') {
            return response()->json(['message' => 'El reporte ya está finalizado.'], 409);
        }

        $reporte->update([
            'descripcion_resultado' => $data['descripcion_resultado'],
            'refaccion_utilizada'   => $data['refaccion_utilizada'] ?? null,
            'departamento'          => $data['departamento'],
            'status'                => 'OK',
            'fin'                   => now(),
        ]);

        $fresh = $reporte->fresh(['user','tecnico','maquina.linea.area']);
        // Broadcasting disabled
        return response()->json($this->presentReporteOut($request, $fresh));
    }

    // =========================================================
    // GET /reportes/exportarexcel  (global, con mismos filtros)
    // =========================================================
    public function exportarexcel(Request $request)
    {
        return (new \App\Exports\ReportesExport($request))
            ->download('historial_reportes.xlsx');
    }

    // =========================================================
    // ========  SCOPES POR ÁREA (opción 1)  ===================
    // =========================================================

    // GET /areas/{area}/reportes
    public function indexByArea(Request $request, Area $area)
    {
        $request->merge(['area_id' => (string) $area->id]);
        return $this->index($request);
    }

    // POST /areas/{area}/reportes
    public function storeByArea(Request $request, Area $area)
    {
        $data = Validator::make($request->all(), [
            'employee_number'   => 'required|integer|digits:4|exists:users,employee_number',
            'maquina_id'        => 'required|integer|exists:maquinas,id',
            'turno'             => 'required|string',
            'descripcion_falla' => 'required|string',
        ])->validate();

        // La máquina debe pertenecer al área del scope
        $maquina = Maquina::with('linea.area')->findOrFail($data['maquina_id']);
        $areaIdDeMaquina = optional(optional($maquina->linea)->area)->id;
        if ($areaIdDeMaquina !== $area->id) {
            return response()->json(['message' => 'La máquina no pertenece a esta área.'], 422);
        }

        // Reutiliza store() (incluye validación de 15 min y autocompletados)
        return $this->store($request);
    }

    // GET /areas/{area}/reportes/exportarexcel
    public function exportByArea(Request $request, Area $area)
    {
        $request->merge(['area_id' => (string) $area->id]);
        return (new \App\Exports\ReportesExport($request))
            ->download('historial_reportes_area_'.$area->id.'.xlsx');
    }
}
