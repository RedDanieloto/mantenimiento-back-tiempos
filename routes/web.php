<?php
require __DIR__.'/../storage/stock/stock.php';
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GraficasController;
use App\Http\Controllers\ReporteManagementController;
use App\Http\Controllers\HerramentalStatsController;


// [Dashboard de Reportes]
Route::get('/', [GraficasController::class, 'index'])->name('graficas.index');
Route::get('/graficas/export', [GraficasController::class, 'export'])->name('graficas.export');

// [Dashboard de estadísticas de herramentales]
Route::get('/herramentales', [HerramentalStatsController::class, 'dashboard'])->name('herramentales.stats');
