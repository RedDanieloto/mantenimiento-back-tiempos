<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Denormalizar nombres en la tabla reportes ──
        // Evita N+1 queries: antes se calculaban via accessor ($this->user->name)
        // lo que disparaba 1 query por reporte × 2 (lider + tecnico) = 14,000 queries con 7k reportes
        if (!Schema::hasColumn('reportes', 'lider_nombre')) {
            Schema::table('reportes', function (Blueprint $table) {
                $table->string('lider_nombre')->nullable()->after('employee_number');
            });
        }

        if (!Schema::hasColumn('reportes', 'tecnico_nombre')) {
            Schema::table('reportes', function (Blueprint $table) {
                $table->string('tecnico_nombre')->nullable()->after('tecnico_employee_number');
            });
        }

        // ── 2. Backfill: poblar lider_nombre y tecnico_nombre desde users ──
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                UPDATE reportes r
                JOIN users u ON r.employee_number = u.employee_number
                SET r.lider_nombre = u.name
                WHERE r.lider_nombre IS NULL
            ");

            DB::statement("
                UPDATE reportes r
                JOIN users u ON r.tecnico_employee_number = u.employee_number
                SET r.tecnico_nombre = u.name
                WHERE r.tecnico_employee_number IS NOT NULL AND r.tecnico_nombre IS NULL
            ");
        } else {
            // SQLite / otros drivers: backfill con subqueries
            DB::statement("
                UPDATE reportes
                SET lider_nombre = (SELECT name FROM users WHERE users.employee_number = reportes.employee_number)
                WHERE lider_nombre IS NULL AND employee_number IS NOT NULL
            ");

            DB::statement("
                UPDATE reportes
                SET tecnico_nombre = (SELECT name FROM users WHERE users.employee_number = reportes.tecnico_employee_number)
                WHERE tecnico_employee_number IS NOT NULL AND tecnico_nombre IS NULL
            ");
        }

        // ── 3. Índice para queries de herramental stats ──
        Schema::table('reportes', function (Blueprint $table) {
            // Índice compuesto para: WHERE herramental_id IS NOT NULL AND inicio BETWEEN ...
            $table->index(['herramental_id', 'inicio'], 'idx_herramental_inicio');

            // Índice para búsqueda full-text en nombres denormalizados
            $table->index('lider_nombre', 'idx_lider_nombre');
        });
    }

    public function down(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            $table->dropIndex('idx_herramental_inicio');
            $table->dropIndex('idx_lider_nombre');
        });

        if (Schema::hasColumn('reportes', 'lider_nombre')) {
            Schema::table('reportes', function (Blueprint $table) {
                $table->dropColumn('lider_nombre');
            });
        }

        if (Schema::hasColumn('reportes', 'tecnico_nombre')) {
            Schema::table('reportes', function (Blueprint $table) {
                $table->dropColumn('tecnico_nombre');
            });
        }
    }
};
