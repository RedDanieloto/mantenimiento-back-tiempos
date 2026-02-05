<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reporte;
use App\Models\User;
use App\Models\Area;
use App\Models\Linea;
use App\Models\Maquina;
use App\Models\herramental;
use Carbon\Carbon;

class CreateTestReportes extends Command
{
    protected $signature = 'test:create-reportes';
    protected $description = 'Crear reportes de prueba con falla de herramental';

    public function handle()
    {
        $user = User::where('employee_number', 1111)->first();
        $area = Area::where('name', 'Producción')->first();
        $linea = Linea::where('name', 'Línea A')->first();
        $maquina = Maquina::where('name', 'Torno CNC-01')->first();
        $h1 = herramental::where('name', 'Llave Inglesa')->first();
        $h2 = herramental::where('name', 'Destornillador')->first();
        $h3 = herramental::where('name', 'Martillo')->first();

        if (!$user || !$area || !$linea || !$maquina || !$h1 || !$h2 || !$h3) {
            $this->error('Faltan datos base');
            return;
        }

        $reportes = [
            ['h' => $h1, 'desc' => 'Llave dañada', 'inicio' => '2026-02-01 08:00:00', 'fin' => '2026-02-01 08:20:00'],
            ['h' => $h1, 'desc' => 'Llave rota', 'inicio' => '2026-02-02 09:00:00', 'fin' => '2026-02-02 09:25:00'],
            ['h' => $h1, 'desc' => 'Llave gastada', 'inicio' => '2026-02-03 10:00:00', 'fin' => '2026-02-03 10:30:00'],
            ['h' => $h1, 'desc' => 'Llave rota', 'inicio' => '2026-02-04 14:00:00', 'fin' => '2026-02-04 14:25:00'],
            ['h' => $h1, 'desc' => 'Llave doblada', 'inicio' => '2026-02-05 16:00:00', 'fin' => '2026-02-05 16:35:00'],
            
            ['h' => $h2, 'desc' => 'Destornillador roto', 'inicio' => '2026-02-01 11:00:00', 'fin' => '2026-02-01 11:20:00'],
            ['h' => $h2, 'desc' => 'Punta desgastada', 'inicio' => '2026-02-02 13:00:00', 'fin' => '2026-02-02 13:18:00'],
            ['h' => $h2, 'desc' => 'Punta rota', 'inicio' => '2026-02-03 15:00:00', 'fin' => '2026-02-03 15:22:00'],
            ['h' => $h2, 'desc' => 'Mango roto', 'inicio' => '2026-02-04 12:00:00', 'fin' => '2026-02-04 12:28:00'],
            
            ['h' => $h3, 'desc' => 'Cabeza rota', 'inicio' => '2026-02-01 14:00:00', 'fin' => '2026-02-01 14:35:00'],
            ['h' => $h3, 'desc' => 'Martillo dañado', 'inicio' => '2026-02-02 15:00:00', 'fin' => '2026-02-02 15:40:00'],
            ['h' => $h3, 'desc' => 'Cabeza suelta', 'inicio' => '2026-02-04 17:00:00', 'fin' => '2026-02-04 17:30:00'],
        ];

        $count = 0;
        foreach ($reportes as $r) {
            $inicio = Carbon::parse($r['inicio']);
            Reporte::create([
                'employee_number' => $user->employee_number,
                'lider_nombre' => $user->name,
                'area_id' => $area->id,
                'maquina_id' => $maquina->id,
                'herramental_id' => $r['h']->id,
                'status' => 'OK',
                'falla' => 'Herramental',
                'turno' => 'A',
                'descripcion_falla' => $r['desc'],
                'descripcion_resultado' => 'Se reemplazó/reparó',
                'departamento' => 'Mantenimiento',
                'inicio' => $inicio,
                'fin' => Carbon::parse($r['fin']),
                'aceptado_en' => $inicio->addMinutes(5),
            ]);
            $count++;
        }

        $this->info("✅ $count reportes de prueba creados");
    }
}
