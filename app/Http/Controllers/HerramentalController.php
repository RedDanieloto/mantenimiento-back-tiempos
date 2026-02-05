<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\herramental;
use Illuminate\Support\Facades\Validator;


class HerramentalController extends Controller
{
    public function index()
    {
        return herramental::all();
    }

    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'linea_id' => 'required|exists:lineas,id',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',
            'linea_id.required' => 'El ID de la linea es obligatorio.',
            'linea_id.exists' => 'El ID de la linea no existe.',
        ])->validate();



        $herramental = herramental::create([
            'name' => $request->name,
            'linea_id' => $request->linea_id,
        ]);

        return response()->json([
            'message' => 'Herramental creado correctamente.',
            'herramental' => $herramental
        ], 201);
    }
    public function update(Request $request, herramental $herramental)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:herramentals,name,' . $herramental->id,
            'linea_id' => 'required|integer|exists:lineas,id',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',
            'name.unique' => 'El nombre ya existe.',
            'linea_id.required' => 'El ID de la linea es obligatorio.',
            'linea_id.integer' => 'El ID de la linea debe ser un número entero.',
            'linea_id.exists' => 'El ID de la linea no existe.',
        ]);

        $herramental->update($data);

        return response()->json([
            'message' => 'Herramental actualizado correctamente.',
            'herramental' => $herramental
        ], 200);
    }
    public function show($id)
    {
        $herramental = herramental::find($id);
        if (!$herramental) {
            return response()->json(['message' => 'Herramental no encontrado.'], 404);
        }
        return response()->json($herramental);
    }
    public function delete($id)
    {
        $herramental = herramental::find($id);
        if (!$herramental) {
            return response()->json(['message' => 'Herramental no encontrado.'], 404);
        }
        $herramental->delete();
        return response()->json(['message' => 'Herramental eliminado correctamente.']);
    }

    public function destroy(herramental $herramental)
    {
        $herramental->delete();
        return response()->json(['message' => 'Herramental eliminado correctamente.'], 200);
    }

    // Helper: obtener herramentales por línea
    public function herramentalesPorLinea($linea_id)
    {
        $herramentales = herramental::where('linea_id', $linea_id)->get();
        if ($herramentales->isEmpty()) {
            return response()->json(['message' => 'No hay herramentales para esta línea.', 'data' => []], 200);
        }
        return response()->json($herramentales, 200);
    }

}
