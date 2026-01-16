<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Agregar índices para optimizar queries de reportes
     */
    public function up(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            // ✅ Índice para filtros por área
            $table->index('area_id');
            
            // ✅ Índice para filtro de fecha (whereDate)
            $table->index('inicio');
            
            // ✅ Índice compuesto para búsquedas combinadas frecuentes
            $table->index(['area_id', 'status']);
            $table->index(['area_id', 'inicio']);
            
            // ✅ Índices para filtros de búsqueda
            $table->index('status');
            $table->index('tecnico_employee_number');
            $table->index('maquina_id');
            $table->index('turno');
            
            // ✅ Índice para ordenamiento
            $table->index(['area_id', 'inicio'], 'idx_area_inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            $table->dropIndex('reportes_area_id_index');
            $table->dropIndex('reportes_inicio_index');
            $table->dropIndex('reportes_area_id_status_index');
            $table->dropIndex('reportes_area_id_inicio_index');
            $table->dropIndex('reportes_status_index');
            $table->dropIndex('reportes_tecnico_employee_number_index');
            $table->dropIndex('reportes_maquina_id_index');
            $table->dropIndex('reportes_turno_index');
            $table->dropIndex('idx_area_inicio');
        });
    }
};
