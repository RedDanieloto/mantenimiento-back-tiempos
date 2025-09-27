<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Linea;
use Illuminate\Support\Facades\Validator;


class LineaController extends Controller
{
    //==================[Index]==========================
    public function index()
    {
        return Linea::all();
    }
    //==================[Show]==========================
    public function show($id)
    {
        $linea = Linea::find($id);
        if (!$linea) {
            return response()->json(['message' => 'Linea no encontrado.'], 404);
        }
        return response()->json($linea);
    }
    //==================[Store]==========================
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'area_id'                  => 'required|string|max:20',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.exist' => 'El nombre ya existe',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'area_id.exist' => 'Esa area no existe'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $linea = Linea::create([
            'name'      => $request->name,
            'area_id'              => $request->area_id,
        ]);
        return response()->json([
            'message' => 'Linea creado correctamente.',
            'linea' => $linea
        ], 201);
    }
    //==================[Update]==========================|
    public function update(Request $request, Linea $linea)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:lineas,name,' . $linea->id,
            'area_id' => 'required|integer|exists:areas,id',
        ], [
            'name.required' => 'El nombre de la línea es obligatorio.',
            'name.string' => 'El nombre de la línea debe ser una cadena de texto.',
            'name.max' => 'El nombre de la línea no debe exceder los 255 caracteres.',
            'name.unique' => 'El nombre de la línea ya existe.',
            'area_id.required' => 'El ID del área es obligatorio.',
            'area_id.integer' => 'El ID del área debe ser un entero.',
            'area_id.exists' => 'El área especificada no existe.',
        ]);

        $linea->update($data);
        return response()->json($linea);
    }
    //==================[Delete]==========================
    public function destroy(Linea $linea)
    {
        $linea->delete();
        return response()->json(['message' => 'Línea eliminada correctamente.']);       
    }
    //==================[Show by Area]==========================
    public function lineasPorArea($area_id)
    {
        $lineas = Linea::where('area_id', $area_id)->get();
        return response()->json($lineas);
    }

}