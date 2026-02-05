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
        Schema::table('reportes', function (Blueprint $table) {
            // Agregar referencia a herramental (nullable porque no todas las fallos son de herramental)
            $table->foreignId('herramental_id')->nullable()->constrained('herramentals')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            $table->dropForeignIdFor('herramental_id');
            $table->dropColumn('herramental_id');
        });
    }
};
