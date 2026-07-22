<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\MaquinaController;
use App\Http\Controllers\LineaController;
use App\Http\Controllers\HerramentalController;
use App\Http\Controllers\HerramentalStatsController;
use App\Http\Controllers\EstadisticasApiController;
// Rutas de usuarios
Route::get('/user', [UserController::class, 'index']);
Route::post('/user', [UserController::class, 'store']);
Route::put('/user/{id}', [UserController::class, 'update']);
Route::get('/user/{id}', [UserController::class, 'show']);
Route::delete('/user/{id}', [UserController::class, 'destroy']);

Route::prefix('/areas/{area}')->group(function () {
    Route::get('/reportes', [ReporteController::class, 'indexByArea']);
    Route::get('/reportes/pendientes', [ReporteController::class, 'pendientesByArea']);
    Route::post('/reportes', [ReporteController::class, 'storeByArea']);
    Route::get('/reportes/exportarexcel', [ReporteController::class, 'exportByArea'])->name('reportes.exportByArea');
});

// Rutas globales de reportes
Route::get('/reportes/lookup', [ReporteController::class, 'lookup']);
Route::get('/reportes/exportarexcel', [ReporteController::class, 'exportarexcel']);
Route::get('/reportes', [ReporteController::class, 'index']);
Route::post('/reportes', [ReporteController::class, 'store']);
Route::post('/reportes/{reporte}/aceptar', [ReporteController::class, 'accept']);
Route::post('/reportes/{reporte}/finalizar', [ReporteController::class, 'finish']);
Route::get('/reportesTotales', [ReporteController::class, 'pendientesTotales']);

// Rutas de lineas
Route::get('/lineas', [LineaController::class, 'index']);
Route::post('/lineas', [LineaController::class, 'store']);
Route::get('/lineas/{linea}', [LineaController::class, 'show']);
Route::put('/lineas/{linea}', [LineaController::class, 'update']);
Route::delete('/lineas/{linea}', [LineaController::class, 'destroy']);
Route::get('/areas/{area}/lineas', [LineaController::class, 'lineasPorArea']);

// Rutas de maquinas
Route::get('/maquinas', [MaquinaController::class, 'index']); 
Route::post('/maquinas', [MaquinaController::class, 'store']);
Route::get('/maquinas/{maquina}', [MaquinaController::class, 'show']);
Route::put('/maquinas/{maquina}', [MaquinaController::class, 'update']);
Route::delete('/maquinas/{maquina}', [MaquinaController::class, 'destroy']);
Route::get('/lineas/{linea}/maquinas', [MaquinaController::class, 'maquinasPorLinea']);
Route::get('/areas/{area}/maquinas', [MaquinaController::class, 'maquinasPorArea']);
Route::get('/maquinas/search/{name}', [MaquinaController::class, 'buscarPorNombre']);
Route::get('/maquinas/{maquina}/relations', [MaquinaController::class, 'show']);
Route::get('/maquinas-with-relations', [MaquinaController::class, 'index']);

// Rutas de areas
Route::get('/areas', [AreaController::class, 'index']);
Route::post('/areas', [AreaController::class, 'store']);
Route::get('/areas/{area}', [AreaController::class, 'show']);   
Route::put('/areas/{area}', [AreaController::class, 'update']);
Route::delete('/areas/{area}', [AreaController::class, 'destroy']);

// Rutas de herramentales
Route::get('/herramentales', [HerramentalController::class, 'index']);
Route::post('/herramentales', [HerramentalController::class, 'store']);
Route::get('/herramentales/{herramental}', [HerramentalController::class, 'show']);
Route::put('/herramentales/{herramental}', [HerramentalController::class, 'update']);
Route::delete('/herramentales/{herramental}', [HerramentalController::class, 'destroy']);
Route::get('/lineas/{linea}/herramentales', [HerramentalController::class, 'herramentalesPorLinea']);

// Estadisticas de herramentales
Route::get('/herramentales-estadisticas', [HerramentalStatsController::class, 'index']);

// Estadisticas de la API para dashboard
Route::prefix('estadisticas')->group(function () {
    Route::get('/health',        [EstadisticasApiController::class, 'health']);       
    Route::get('/resumen',       [EstadisticasApiController::class, 'resumen']);       
    Route::get('/mttr',          [EstadisticasApiController::class, 'mttr']);
    Route::get('/mtbf',          [EstadisticasApiController::class, 'mtbf']);
    Route::get('/tiempo-total',  [EstadisticasApiController::class, 'tiempoTotal']);
    Route::get('/reportes-abiertos', [EstadisticasApiController::class, 'reportesAbiertos']);
    Route::get('/graficas',      [EstadisticasApiController::class, 'graficas']);      
    Route::get('/scrap',         [EstadisticasApiController::class, 'scrap']);
    Route::get('/tendencias',    [EstadisticasApiController::class, 'tendencias']);    
    Route::get('/tiempo-real',   [EstadisticasApiController::class, 'tiempoReal']);   
    Route::get('/areas',         [EstadisticasApiController::class, 'porArea']);       
    Route::get('/herramentales', [EstadisticasApiController::class, 'herramentales']); 
    Route::get('/tecnicos',      [EstadisticasApiController::class, 'tecnicos']);      
    Route::get('/catalogos',     [EstadisticasApiController::class, 'catalogos']);    
});

