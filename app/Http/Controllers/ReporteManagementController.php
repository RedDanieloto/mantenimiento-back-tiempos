<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use App\Models\Area;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReporteManagementController extends Controller
{
    /**
     * Mostrar lista de reportes para gestionar (editar/eliminar)
     */
    public function index(Request $request)
    {
        $query = Reporte::with(['user', 'tecnico', 'maquina.linea.area']);

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('area_id')) {
            $query->whereHas('maquina.linea.area', fn($q) => $q->where('area_id', $request->integer('area_id')));
        }

        if ($request->filled('maquina_id')) {
            $query->where('maquina_id', $request->integer('maquina_id'));
        }

        if ($request->filled('search')) {
            $term = '%' . $request->string('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('descripcion_falla', 'like', $term)
                  ->orWhere('descripcion_resultado', 'like', $term)
                  ->orWhereHas('maquina', fn($m) => $m->where('name', 'like', $term));
            });
        }

        if ($request->filled('from_date')) {
            $query->where('inicio', '>=', Carbon::parse($request->string('from_date'))->startOfDay());
        }

        if ($request->filled('to_date')) {
            $query->where('inicio', '<=', Carbon::parse($request->string('to_date'))->endOfDay());
        }

        // Ordenar por fecha más reciente
        $reportes = $query->latest('inicio')->paginate(15);
        
        // Para el filtro de áreas
        $areas = Area::all();

        return view('reportes.manage', compact('reportes', 'areas'));
    }

    /**
     * Mostrar formulario para editar un reporte
     */
    public function edit(Reporte $reporte)
    {
        $reporte->load(['user', 'tecnico', 'maquina.linea.area']);
        
        // Lista de técnicos disponibles
        $tecnicos = User::where('role', 'tecnico')->get();
        
        return view('reportes.edit', compact('reporte', 'tecnicos'));
    }

    /**
     * Actualizar un reporte
     */
    public function update(Request $request, Reporte $reporte)
    {
        try {
            \Log::info('Update request received', ['data' => $request->all()]);
            
            $validated = $request->validate([
                'inicio' => 'required|date_format:Y-m-d\TH:i',
                'aceptado_en' => 'nullable|date_format:Y-m-d\TH:i',
                'fin' => 'nullable|date_format:Y-m-d\TH:i',
                'status' => 'required|in:abierto,en_mantenimiento,OK',
                'tecnico_employee_number' => 'nullable|integer|exists:users,employee_number',
                'descripcion_falla' => 'nullable|string',
                'descripcion_resultado' => 'nullable|string',
                'refaccion_utilizada' => 'nullable|string',
                'departamento' => 'nullable|string',
            ]);

            \Log::info('Validated data', ['validated' => $validated]);

            // Convertir a Carbon para validación
            $inicio = Carbon::createFromFormat('Y-m-d\TH:i', $validated['inicio']);
            $aceptado = $validated['aceptado_en'] ? Carbon::createFromFormat('Y-m-d\TH:i', $validated['aceptado_en']) : null;
            $fin = $validated['fin'] ? Carbon::createFromFormat('Y-m-d\TH:i', $validated['fin']) : null;

            // Validar que los tiempos sean coherentes
            if ($aceptado && $inicio->greaterThanOrEqualTo($aceptado)) {
                return back()->withErrors(['aceptado_en' => 'Aceptado debe ser posterior al inicio']);
            }
            if ($fin && $inicio->greaterThan($fin)) {
                return back()->withErrors(['fin' => 'Fin debe ser posterior o igual al inicio']);
            }
            if ($aceptado && $fin && $aceptado->greaterThan($fin)) {
                return back()->withErrors(['fin' => 'Fin debe ser posterior o igual a aceptado']);
            }

            // Actualizar
            $reporte->update([
                'inicio' => $inicio,
                'aceptado_en' => $aceptado,
                'fin' => $fin,
                'status' => $validated['status'],
                'tecnico_employee_number' => $validated['tecnico_employee_number'],
                'descripcion_falla' => $validated['descripcion_falla'],
                'descripcion_resultado' => $validated['descripcion_resultado'],
                'refaccion_utilizada' => $validated['refaccion_utilizada'],
                'departamento' => $validated['departamento'],
            ]);

            \Log::info('Reporte updated successfully', ['reporte_id' => $reporte->id]);

            return redirect()->route('reportes.manage.index')
                           ->with('success', 'Reporte actualizado correctamente');
        } catch (\Exception $e) {
            \Log::error('Error updating reporte', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar página de confirmación de eliminación
     */
    public function confirmDelete(Reporte $reporte)
    {
        return view('reportes.confirm-delete', compact('reporte'));
    }

    /**
     * Eliminar un reporte
     */
    public function destroy(Reporte $reporte)
    {
        $reporte->delete();
        
        return redirect()->route('reportes.manage.index')
                       ->with('success', 'Reporte eliminado correctamente');
    }

    /**
     * Eliminar múltiples reportes
     */
    public function destroyMultiple(Request $request)
    {
        try {
            \Log::info('Destroy multiple request received', ['data' => $request->all()]);
            
            $idsString = $request->input('ids', '');
            
            if (empty($idsString)) {
                \Log::warning('No IDs provided for deletion');
                return back()->with('error', 'Selecciona al menos un reporte');
            }

            // Convertir string de IDs separados por comas a array
            $ids = array_filter(explode(',', $idsString), function($id) {
                return is_numeric(trim($id));
            });
            
            if (empty($ids)) {
                \Log::warning('No valid IDs after parsing', ['raw' => $idsString]);
                return back()->with('error', 'IDs inválidos');
            }

            \Log::info('Deleting reportes', ['ids' => $ids]);

            // Eliminar
            $count = Reporte::whereIn('id', $ids)->delete();

            \Log::info('Reportes deleted', ['count' => $count]);

            return redirect()->route('reportes.manage.index')
                           ->with('success', "$count reporte(s) eliminado(s) correctamente");
        } catch (\Exception $e) {
            \Log::error('Error deleting multiple reportes', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Error al eliminar: ' . $e->getMessage());
        }
    }
}
