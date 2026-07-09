<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Area;
use Illuminate\Support\Facades\Validator;

class AreaController extends Controller
{
    // [Obtiene todas las areas registradas]
    public function index()
    {
        $areas = Area::all();
        return response()->json($areas);
    }

    // [Muestra una area especifica]
    public function show(Area $area)
    {
        return response()->json($area);
    }

    // [Registra una nueva area]
    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:areas,name',
        ],
        [
            'name.required' => 'El nombre del área es obligatorio.',
            'name.string' => 'El nombre del área debe ser una cadena de texto.',
            'name.max' => 'El nombre del área no debe exceder los 255 caracteres.',
            'name.unique' => 'El nombre del área ya existe.',
        ])->validate();

        $area = Area::create($data);
        return response()->json($area, 201);
    }

    // [Actualiza una area existente]
    public function update(Request $request, Area $area)
    {
        $data = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:areas,name,' . $area->id,
        ],
        [
            'name.required' => 'El nombre del área es obligatorio.',
            'name.string' => 'El nombre del área debe ser una cadena de texto.',
            'name.max' => 'El nombre del área no debe exceder los 255 caracteres.',
            'name.unique' => 'El nombre del área ya existe.',
        ])->validate();

        $area->update($data);
        return response()->json($area);
    }

    // [Elimina una area]
    public function destroy(Area $area)
    {
        $area->delete();
        return response()->json(['message' => 'Área eliminada correctamente.']);
    }
}