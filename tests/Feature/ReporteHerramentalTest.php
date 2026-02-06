<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Area;
use App\Models\Linea;
use App\Models\Maquina;
use App\Models\Reporte;
use App\Models\herramental;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReporteHerramentalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear datos base necesarios
        $this->area = Area::create(['name' => 'Test Area']);
        $this->linea = Linea::create(['name' => 'Test Linea', 'area_id' => $this->area->id]);
        $this->maquina = Maquina::create(['name' => 'Test Maquina', 'linea_id' => $this->linea->id]);
        
        $this->lider = User::create([
            'employee_number' => 1111,
            'name' => 'Test Lider',
            'role' => 'lider',
            'turno' => 'A',
            'password' => bcrypt('password')
        ]);
        
        $this->tecnico = User::create([
            'employee_number' => 2222,
            'name' => 'Test Tecnico',
            'role' => 'tecnico',
            'turno' => 'A',
            'password' => bcrypt('password')
        ]);
        
        $this->herramental = herramental::create([
            'name' => 'Llave Inglesa Test',
            'linea_id' => $this->linea->id
        ]);
    }

    /** @test */
    public function puede_crear_reporte_sin_herramental()
    {
        $response = $this->postJson('/api/reportes', [
            'employee_number' => $this->lider->employee_number,
            'maquina_id' => $this->maquina->id,
            'turno' => 'A',
            'descripcion_falla' => 'Falla de prueba'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'herramental_id', // Debe existir aunque sea null
                'status',
                'employee_number'
            ]);

        $this->assertDatabaseHas('reportes', [
            'id' => $response->json('id'),
            'herramental_id' => null
        ]);
    }

    /** @test */
    public function flujo_completo_reporte_con_herramental()
    {
        // 1. Crear reporte CON herramental (ahora va en la creación)
        $responseCreate = $this->postJson('/api/reportes', [
            'employee_number' => $this->lider->employee_number,
            'maquina_id' => $this->maquina->id,
            'turno' => 'A',
            'descripcion_falla' => 'Falla de herramental',
            'herramental_id' => $this->herramental->id,
        ]);

        $responseCreate->assertStatus(201)
            ->assertJson([
                'herramental_id' => $this->herramental->id,
            ]);
        $reporteId = $responseCreate->json('id');

        // Verificar en BD que herramental_id se guardó al crear
        $this->assertDatabaseHas('reportes', [
            'id' => $reporteId,
            'herramental_id' => $this->herramental->id,
        ]);
        
        // 2. Aceptar reporte
        $responseAccept = $this->postJson("/api/reportes/{$reporteId}/aceptar", [
            'tecnico_employee_number' => $this->tecnico->employee_number
        ]);

        $responseAccept->assertStatus(200);

        // 3. Finalizar reporte (herramental_id ya no va aquí)
        $responseFinish = $this->postJson("/api/reportes/{$reporteId}/finalizar", [
            'descripcion_resultado' => 'Se cambió el herramental defectuoso',
            'refaccion_utilizada' => 'N/A',
            'departamento' => 'Mantenimiento'
        ]);

        $responseFinish->assertStatus(200)
            ->assertJsonStructure([
                'herramental_id',
                'herramental',
                'herramental_nombre'
            ])
            ->assertJson([
                'herramental_id' => $this->herramental->id,
                'herramental_nombre' => 'Llave Inglesa Test'
            ]);

        // Verificar en BD
        $this->assertDatabaseHas('reportes', [
            'id' => $reporteId,
            'herramental_id' => $this->herramental->id,
            'status' => 'OK'
        ]);
    }

    /** @test */
    public function get_reportes_incluye_herramental_id()
    {
        // Crear reporte con herramental
        $reporte = Reporte::create([
            'employee_number' => $this->lider->employee_number,
            'area_id' => $this->area->id,
            'maquina_id' => $this->maquina->id,
            'herramental_id' => $this->herramental->id,
            'status' => 'OK',
            'falla' => 'Herramental',
            'turno' => 'A',
            'descripcion_falla' => 'Falla de herramental',
            'inicio' => now()
        ]);

        $response = $this->getJson('/api/reportes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'herramental_id',
                        'herramental',
                        'herramental_nombre'
                    ]
                ]
            ]);

        $reporteData = collect($response->json('data'))->firstWhere('id', $reporte->id);
        
        $this->assertEquals($this->herramental->id, $reporteData['herramental_id']);
        $this->assertEquals('Llave Inglesa Test', $reporteData['herramental_nombre']);
        $this->assertNotNull($reporteData['herramental']);
    }

    /** @test */
    public function export_excel_incluye_herramental()
    {
        // Crear reporte con herramental  
        Reporte::create([
            'employee_number' => $this->lider->employee_number,
            'area_id' => $this->area->id,
            'maquina_id' => $this->maquina->id,
            'herramental_id' => $this->herramental->id,
            'status' => 'OK',
            'falla' => 'Herramental',
            'turno' => 'A',
            'descripcion_falla' => 'Falla de herramental',
            'descripcion_resultado' => 'Se cambió',
            'departamento' => 'Mantenimiento',
            'inicio' => now(),
            'fin' => now()
        ]);

        $response = $this->get('/api/reportes/exportarexcel');

        // Solo verificamos que la exportación funciona (200)
        // El contenido del Excel es binario, difícil de verificar en test
        $response->assertStatus(200);
    }

    /** @test */
    public function no_acepta_herramental_id_invalido()
    {
        // Intentar crear reporte con herramental_id inválido
        $response = $this->postJson('/api/reportes', [
            'employee_number' => $this->lider->employee_number,
            'maquina_id' => $this->maquina->id,
            'turno' => 'A',
            'descripcion_falla' => 'Falla de herramental',
            'herramental_id' => 99999, // ID inexistente
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['herramental_id']);
    }

    /** @test */
    public function herramental_id_es_opcional_al_crear()
    {
        // Crear reporte SIN herramental (debe ser válido)
        $responseCreate = $this->postJson('/api/reportes', [
            'employee_number' => $this->lider->employee_number,
            'maquina_id' => $this->maquina->id,
            'turno' => 'A',
            'descripcion_falla' => 'Falla sin herramental',
        ]);

        $responseCreate->assertStatus(201);
        $reporteId = $responseCreate->json('id');

        $this->assertDatabaseHas('reportes', [
            'id' => $reporteId,
            'herramental_id' => null,
        ]);

        // Aceptar
        $this->postJson("/api/reportes/{$reporteId}/aceptar", [
            'tecnico_employee_number' => $this->tecnico->employee_number
        ]);

        // Finalizar SIN herramental
        $response = $this->postJson("/api/reportes/{$reporteId}/finalizar", [
            'descripcion_resultado' => 'Se reparó',
            'refaccion_utilizada' => 'Cable',
            'departamento' => 'Mantenimiento'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reportes', [
            'id' => $reporteId,
            'herramental_id' => null,
            'status' => 'OK'
        ]);
    }
}
