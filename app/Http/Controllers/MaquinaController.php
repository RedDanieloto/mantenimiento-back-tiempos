<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maquina;
use Illuminate\Support\Facades\Validator;
use App\Models\Linea;

class MaquinaController extends Controller
{
    // Obtiene todas las maquinas con sus relaciones de linea y area
    public function index()
    {
        return Maquina::with('linea.area')->get();
    }

    // Muestra una maquina especifica
    public function show(Maquina $maquina)
    {
        $maquina->load('linea.area');
        return response()->json($maquina);
    }

    // Registra una nueva maquina
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:maquinas,name',
            'linea_id' => 'required|integer|exists:lineas,id',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',
            'name.unique' => 'Esa máquina ya existe.',
            'linea_id.required' => 'La línea es obligatoria.',
            'linea_id.integer' => 'El ID de la línea debe ser un entero.',
            'linea_id.exists' => 'La línea especificada no existe.',
        ]);

        $maquina = Maquina::create($data);
        return response()->json([
            'message' => 'Máquina creada correctamente.',
            'maquina' => $maquina
        ], 201);
    }

    // Actualiza los datos de una maquina
    public function update(Request $request, Maquina $maquina)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:maquinas,name,' . $maquina->id,
            'linea_id' => 'required|integer|exists:lineas,id',
        ], [
            'name.required' => 'El nombre de la máquina es obligatorio.',
            'name.string' => 'El nombre de la máquina debe ser una cadena de texto.',
            'name.max' => 'El nombre de la máquina no debe exceder los 255 caracteres.',
            'name.unique' => 'El nombre de la máquina ya existe.',
            'linea_id.required' => 'El ID de la línea es obligatorio.',
            'linea_id.integer' => 'El ID de la línea debe ser un entero.',
            'linea_id.exists' => 'La línea especificada no existe.',
        ]);

        $maquina->update($data);
        return response()->json($maquina);
    }

    // Elimina una maquina
    public function destroy(Maquina $maquina)
    {
        $maquina->delete();
        return response()->json(['message' => 'Máquina eliminada correctamente.']);
    }

    // Obtiene las maquinas de una linea especifica
    public function maquinasPorLinea($linea_id)
    {
        $maquinas = Maquina::where('linea_id', $linea_id)->get();
        return response()->json($maquinas);
    }

    // Obtiene las maquinas de un area especifica
    public function maquinasPorArea($area_id)
    {
        $maquinas = Maquina::whereHas('linea', function ($query) use ($area_id) {
            $query->where('area_id', $area_id);
        })->get();
        return response()->json($maquinas);
    }

    // Busca maquinas por coincidencia de nombre
    public function buscarPorNombre($name)
    {
        $maquinas = Maquina::where('name', 'like', '%' . $name . '%')->get();
        return response()->json($maquinas);
    }
}