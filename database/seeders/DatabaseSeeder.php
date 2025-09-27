<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Linea;
use App\Models\Maquina;
use App\Models\Reporte;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuario admin con número de empleado fijo
        $admin = User::factory()->create([
            'employee_number' => 7218,
            'name' => 'Nestor Cabrera',
            'role' => 'admin',
            'turno' => 'A',
        ]);


    }
}
