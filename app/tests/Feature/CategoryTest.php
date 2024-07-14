<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\FeatureTestSeeder;
class CategoryTest extends TestCase
{
    use RefreshDatabase;
    private $cat1;
    private $cat2;
    private $catType1;
    private $catType2;
    private $user;

    protected function setup(): void
    {
        parent::setUp();
        $this->seed(FeatureTestSeeder::class);
        $this->user = User::find(100000);
        $this->actingAs($this->user);
        $this->cat1 = Category::find(100003);
        $this->cat2 = Category::find(100004);
        $this->catType1 = $this->cat1->categoryType;
        $this->catType2 = $this->cat2->categoryType;
    }

    public function test_category_post()
    {
        $this->assertCount(3, $this->user->categories);
        $response = $this->post(
            '/settings/store_category',
            ['name' => 'a newly posted category', 'hex_color' => '#000000', 'category_type' => $this->catType1->id]
        );

        $response->assertStatus(302);
        $this->user->refresh();
        $this->assertCount(4, $this->user->categories);
        $newCat = $this->user->categories->last();
        $this->assertEquals('a newly posted category', $newCat->name);
        $this->assertEquals('#000000', $newCat->hex_color);
        $this->assertEquals($this->catType1->id, $newCat->category_type_id);
    }

    public function test_category_post_missing_name()
    {
        $this->assertCount(3, $this->user->categories);
        $response = $this->post('/settings/store_category', []);
        $response->assertStatus(302);
        $this->user->refresh();
        $this->assertCount(3, $this->user->categories);
    }
}
