<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GraficasController;
use App\Http\Controllers\ReporteManagementController;
use App\Http\Controllers\HerramentalStatsController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/graficas', [GraficasController::class, 'index'])->name('graficas.index');
Route::get('/graficas/export', [GraficasController::class, 'export'])->name('graficas.export');

// Dashboard de estadísticas de herramentales
Route::get('/herramentales', [HerramentalStatsController::class, 'dashboard'])->name('herramentales.stats');

// Gestión de Reportes (editar/eliminar)
Route::prefix('/reportes')->name('reportes.manage.')->group(function () {
    Route::get('/', [ReporteManagementController::class, 'index'])->name('index');
    Route::get('/{reporte}/edit', [ReporteManagementController::class, 'edit'])->name('edit');
    Route::put('/{reporte}', [ReporteManagementController::class, 'update'])->name('update');
    Route::get('/{reporte}/confirmar-eliminar', [ReporteManagementController::class, 'confirmDelete'])->name('confirm-delete');
    Route::delete('/{reporte}', [ReporteManagementController::class, 'destroy'])->name('destroy');
    Route::post('/eliminar-multiples', [ReporteManagementController::class, 'destroyMultiple'])->name('destroy-multiple');
});
