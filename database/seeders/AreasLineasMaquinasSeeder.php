<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;
use App\Models\Linea;
use App\Models\Maquina;

class AreasLineasMaquinasSeeder extends Seeder
{
    public function run(): void
    {
        // === Área 1: Costura ===
        $costura = Area::firstOrCreate(['name' => 'Costura']);

        $t6   = Linea::firstOrCreate(['name' => 'T6',   'area_id' => $costura->id]);
        $d2uc = Linea::firstOrCreate(['name' => 'D2UC', 'area_id' => $costura->id]);
        $mea  = Linea::firstOrCreate(['name' => 'MEA',  'area_id' => $costura->id]);

        foreach (['Prensa-01','Prensa-02','Prensa-03'] as $n) {
            Maquina::firstOrCreate(['name' => $n, 'linea_id' => $t6->id]);
        }
        foreach (['CMM-01','CMM-02'] as $n) {
            Maquina::firstOrCreate(['name' => $n, 'linea_id' => $d2uc->id]);
        }
        foreach (['Robot-01','Robot-02','Robot-03'] as $n) {
            Maquina::firstOrCreate(['name' => $n, 'linea_id' => $mea->id]);
        }

        // === Área 2: Corte ===
        $corte = Area::firstOrCreate(['name' => 'Corte']);

        $corte = Linea::firstOrCreate(['name' => 'MOBIS KAB', 'area_id' => $corte->id]);

        foreach (['Focus 1','Focus 2'] as $n) {
            Maquina::firstOrCreate(['name' => $n, 'linea_id' => $corte->id]);
        }

    }
}
