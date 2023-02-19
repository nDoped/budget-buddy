<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @group dashboard
     */
    public function test_dashboard(): void
    {
        $this->actingAs($user = User::factory()->create());

        $response = $this->get('/dashboard');
        Log::info([
            'response' => $response

        ]);

        $this->assertTrue(true);
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }
}
