<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReporteManagementController;

// [Gestión de Reportes]
Route::prefix('/reportes')->name('reportes.manage.')->group(function () {
    Route::get('/', [ReporteManagementController::class, 'index'])->name('index');
    Route::get('/{reporte}/edit', [ReporteManagementController::class, 'edit'])->name('edit');
    Route::put('/{reporte}', [ReporteManagementController::class, 'update'])->name('update');
    Route::get('/{reporte}/confirmar-eliminar', [ReporteManagementController::class, 'confirmDelete'])->name('confirm-delete');
    Route::delete('/{reporte}', [ReporteManagementController::class, 'destroy'])->name('destroy');
    Route::post('/eliminar-multiples', [ReporteManagementController::class, 'destroyMultiple'])->name('destroy-multiple');
});