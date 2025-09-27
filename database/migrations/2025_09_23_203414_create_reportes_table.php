<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->string('falla');
            $table->string('departamento')->nullable();
            $table->string('turno');
            $table->text('descripcion_falla');
            $table->text('descripcion_resultado')->nullable();
            $table->string('refaccion_utilizada')->nullable();
            $table->foreignId('area_id')->constrained()->onDelete('cascade');
            $table->foreignId('maquina_id')->constrained()->onDelete('cascade');
            // Users table uses 'employee_number' as primary key, so use same name in reportes
            $table->unsignedBigInteger('employee_number');
            $table->foreign('employee_number')
                ->references('employee_number')
                ->on('users')
                ->onDelete('cascade');
            // Técnico asignado (opcional) y marca de aceptación
            $table->unsignedBigInteger('tecnico_employee_number')->nullable();
            $table->foreign('tecnico_employee_number')
                ->references('employee_number')
                ->on('users')
                ->nullOnDelete();
            $table->dateTime('aceptado_en')->nullable();
            $table->dateTime('inicio');
            $table->dateTime('fin')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reportes');
    }
};
