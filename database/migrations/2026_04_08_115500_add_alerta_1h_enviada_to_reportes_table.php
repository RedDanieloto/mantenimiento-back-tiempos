<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reportes', 'alerta_1h_enviada')) {
            Schema::table('reportes', function (Blueprint $table) {
                $table->boolean('alerta_1h_enviada')->default(false)->after('fin');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reportes', 'alerta_1h_enviada')) {
            Schema::table('reportes', function (Blueprint $table) {
                $table->dropColumn('alerta_1h_enviada');
            });
        }
    }
};
