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
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EstadisticasApiController;


//====================[Usuarios]==============================
Route::get('/user', [UserController::class, 'index']);
Route::post('/user', [UserController::class, 'store']);
Route::put('/user/{id}', [UserController::class, 'update']);
Route::get('/user/{id}', [UserController::class, 'show']);
Route::delete('/user/{id}', [UserController::class, 'destroy']);

//=====================[Reportes]=============================
// ===== Rutas scoped por área =====
Route::prefix('/areas/{area}')->group(function () {
    Route::get('/reportes', [ReporteController::class, 'indexByArea']);
    Route::get('/reportes/pendientes', [ReporteController::class, 'pendientesByArea']);
    Route::post('/reportes', [ReporteController::class, 'storeByArea']);
    Route::get('/reportes/exportarexcel', [ReporteController::class, 'exportByArea']);
});

// ===== Rutas globales (puedes dejarlas para backoffice) =====
Route::get('/reportes/lookup', [ReporteController::class, 'lookup']);
Route::get('/reportes/exportarexcel', [ReporteController::class, 'exportarexcel']);
Route::get('/reportes', [ReporteController::class, 'index']);
Route::post('/reportes', [ReporteController::class, 'store']);
Route::post('/reportes/{reporte}/aceptar', [ReporteController::class, 'accept']);
Route::post('/reportes/{reporte}/finalizar', [ReporteController::class, 'finish']);
//============================================================
//=====================[Lineas]=============================
Route::get('/lineas', [LineaController::class, 'index']);
Route::post('/lineas', [LineaController::class, 'store']);
Route::get('/lineas/{linea}', [LineaController::class, 'show']);
Route::put('/lineas/{linea}', [LineaController::class, 'update']);
Route::delete('/lineas/{linea}', [LineaController::class, 'destroy']);
// Helpers
Route::get('/areas/{area}/lineas', [LineaController::class, 'lineasPorArea']);
//============================================================
//=====================[Máquinas]=============================
Route::get('/maquinas', [MaquinaController::class, 'index']); 
Route::post('/maquinas', [MaquinaController::class, 'store']);
Route::get('/maquinas/{maquina}', [MaquinaController::class, 'show']);
Route::put('/maquinas/{maquina}', [MaquinaController::class, 'update']);
Route::delete('/maquinas/{maquina}', [MaquinaController::class, 'destroy']);
// Helpers
Route::get('/lineas/{linea}/maquinas', [MaquinaController::class, 'maquinasPorLinea']);
Route::get('/areas/{area}/maquinas', [MaquinaController::class, 'maquinasPorArea']);
Route::get('/maquinas/search/{name}', [MaquinaController::class, 'buscarPorNombre']);
Route::get('/maquinas/{id}/relations', [MaquinaController::class, 'showWithRelations']);
Route::get('/maquinas-with-relations', [MaquinaController::class, 'listWithRelations']);
//============================================================
//=====================[Áreas]=============================
Route::get('/areas', [AreaController::class, 'index']);
Route::post('/areas', [AreaController::class, 'store']);
Route::get('/areas/{area}', [AreaController::class, 'show']);   
Route::put('/areas/{area}', [AreaController::class, 'update']);
Route::delete('/areas/{area}', [AreaController::class, 'destroy']);
//============================================================
//=====================[Herramentales]============================
Route::get('/herramentales', [HerramentalController::class, 'index']);
Route::post('/herramentales', [HerramentalController::class, 'store']);
Route::get('/herramentales/{herramental}', [HerramentalController::class, 'show']);
Route::put('/herramentales/{herramental}', [HerramentalController::class, 'update']);
Route::delete('/herramentales/{herramental}', [HerramentalController::class, 'destroy']);
// Helpers
Route::get('/lineas/{linea}/herramentales', [HerramentalController::class, 'herramentalesPorLinea']);

// Estadísticas de Herramentales
Route::get('/herramentales-estadisticas', [HerramentalStatsController::class, 'index']);
//============================================================

//====================[Estadísticas API - Dashboard Centralizado]==============================
Route::prefix('estadisticas')->group(function () {
    Route::get('/health',        [EstadisticasApiController::class, 'health']);        // Health check
    Route::get('/resumen',       [EstadisticasApiController::class, 'resumen']);       // KPIs principales
    Route::get('/graficas',      [EstadisticasApiController::class, 'graficas']);      // Datos para gráficas
    Route::get('/tendencias',    [EstadisticasApiController::class, 'tendencias']);    // Tendencias semanales/mensuales
    Route::get('/tiempo-real',   [EstadisticasApiController::class, 'tiempoReal']);    // Estado actual en vivo
    Route::get('/areas',         [EstadisticasApiController::class, 'porArea']);       // Desglose por área
    Route::get('/herramentales', [EstadisticasApiController::class, 'herramentales']); // Stats herramentales
    Route::get('/tecnicos',      [EstadisticasApiController::class, 'tecnicos']);      // Rendimiento técnicos
    Route::get('/catalogos',     [EstadisticasApiController::class, 'catalogos']);     // Catálogos para filtros
});
//============================================================
