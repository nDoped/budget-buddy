<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Database\Seeders\TestHarnessSeeder;
use \PHPUnit\Framework\Attributes\Group;

class AnalysisTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setup(): void
    {
        parent::setUp();
        $this->seed(TestHarnessSeeder::class);
        $this->user = User::find(TestHarnessSeeder::TESTING_USER_ID);
        $this->actingAs($this->user);
    }

    #[Group('analysis')]
    public function test_analyze_receipt_success()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'output' => [
                    [
                        'type' => 'message',
                        'role' => 'assistant',
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => json_encode([
                                    'store_name' => 'Test Store',
                                    'line_items' => [
                                        ['description' => 'Milk', 'price' => 4.99, 'suggested_category' => 'Groceries'],
                                        ['description' => 'Bread', 'price' => 2.49, 'suggested_category' => 'Groceries'],
                                    ],
                                    'subtotal' => 7.48,
                                    'tax' => 0.60,
                                    'total' => 8.08,
                                ]),
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->postJson(route('receipt.analyze'), [
            'image' => 'data:image/jpeg;base64,' . base64_encode('fake-image-data'),
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'store_name' => 'Test Store',
            'line_items' => [
                ['description' => 'Milk', 'price' => 4.99, 'suggested_category' => 'Groceries'],
                ['description' => 'Bread', 'price' => 2.49, 'suggested_category' => 'Groceries'],
            ],
            'subtotal' => 7.48,
            'tax' => 0.60,
            'total' => 8.08,
        ]);
    }

    #[Group('analysis')]
    public function test_analyze_receipt_invalid_image_format()
    {
        $response = $this->postJson(route('receipt.analyze'), [
            'image' => 'not-a-data-uri',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }

    #[Group('analysis')]
    public function test_analyze_receipt_missing_image()
    {
        $response = $this->postJson(route('receipt.analyze'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }

    #[Group('analysis')]
    public function test_analyze_receipt_unauthenticated()
    {
        $this->actingAs(User::factory()->create());

        Http::fake([
            'api.openai.com/*' => Http::response([
                'output' => [
                    [
                        'type' => 'message',
                        'role' => 'assistant',
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => json_encode([
                                    'store_name' => 'Test Store',
                                    'line_items' => [],
                                ]),
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->postJson(route('receipt.analyze'), [
            'image' => 'data:image/jpeg;base64,' . base64_encode('fake-image-data'),
        ]);

        $response->assertStatus(200);
    }

    #[Group('analysis')]
    public function test_analyze_receipt_openai_error()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([], 500),
        ]);

        $response = $this->postJson(route('receipt.analyze'), [
            'image' => 'data:image/jpeg;base64,' . base64_encode('fake-image-data'),
        ]);

        $response->assertStatus(500);
    }
}
