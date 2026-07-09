<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Crear un área ficticia para que no falle la vista graficas.index si es requerida
        \App\Models\Area::create(['name' => 'Test Area']);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
