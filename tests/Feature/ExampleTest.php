<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Create an admin to pass EnsureSetupComplete middleware
        User::factory()->create(['is_admin' => true]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
