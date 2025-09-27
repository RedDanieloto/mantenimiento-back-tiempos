<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    //============[Listar]============//
    public function index()
    {
        return User::all();
    }
    //============[Crear]============//
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_number' => 'required|integer|unique:users,employee_number',
            'name' => 'required|string|max:255',
            'role' => 'required|string|in:tecnico,lider',
            'turno' => 'string',
        ], [
            'employee_number.required' => 'El número de empleado es obligatorio.',
            'employee_number.integer' => 'El número de empleado debe ser un entero.',
            'employee_number.unique' => 'El número de empleado ya existe.',
            'role.in' => 'El rol debe ser uno de los siguientes: tecnico, lider.',
            'name.required' => 'El nombre es obligatorio.',
            'role.required' => 'El rol es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'employee_number'   => $request->employee_number,
            'name'              => $request->name,
            'role'              => $request->role,
            'turno'             => $request->turno,
        ]);
        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'usuario' => $user
        ], 201);
    }
    //============[Mostrar]============//
    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }
        return response()->json($user);
    }
    //============[Actualizar]============//
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'role' => 'sometimes|required|string|in:tecnico,lider',
            'turno' => 'sometimes|string',
        ],
        [
            'role.in' => 'El rol debe ser uno de los siguientes: tecnico, lider.',
            'name.required' => 'El nombre es obligatorio.',
            'role.required' => 'El rol es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
        ]
    );

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

    $user->update($request->only(['name', 'role', 'turno']));
        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'usuario' => $user
        ]);
    }
    //============[Eliminar]============//
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }
        $user->delete();
        return response()->json(['message' => 'Usuario eliminado correctamente.']);
    }
}