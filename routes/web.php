<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GraficasController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/graficas', [GraficasController::class, 'index'])->name('graficas.index');
Route::get('/graficas/export', [GraficasController::class, 'export'])->name('graficas.export');
