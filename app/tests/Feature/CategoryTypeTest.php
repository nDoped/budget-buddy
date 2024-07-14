<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\CategoryType;
use \PHPUnit\Framework\Attributes\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Util;
use Database\Seeders\FeatureTestSeeder;
class CategoryTypeTest extends TestCase
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

    #[Group('category_types')]
    public function test_category_type_post()
    {
        $this->assertCount(2, $this->user->categoryTypes);
        $payload = [
            'name' => 'a new type',
            'hex_color' => '#000000',
            'note' => 'test note is test'
        ];
        $response = $this->post(
            '/settings/store_category_type',
            $payload
        );

        $response->assertStatus(302);
        $this->user->refresh();
        $this->assertCount(3, $this->user->categories);
        $newCat = $this->user->categoryTypes->last();
        $this->assertEquals($payload['name'], $newCat->name);
        $this->assertEquals($payload['hex_color'], $newCat->hex_color);
        $this->assertEquals($payload['note'], $newCat->note);
    }

    #[Group('category_types')]
    public function test_category_type_post_missing_name()
    {
        $this->assertCount(2, $this->user->categoryTypes);
        $response = $this->post('/settings/store_category_type', []);
        $response->assertStatus(302);
        $this->user->refresh();
        $this->assertCount(2, $this->user->categoryTypes);
    }

    #[Group('category_types')]
    public function test_category_type_patch()
    {
        $this->assertCount(2, $this->user->categoryTypes);
        $payload = [
            'name' => 'an updated name',
            'hex_color' => '#ffff12',
            'active' => false
        ];
        $response = $this->patch(
            '/category_types/update/' . $this->catType1->id,
            $payload
        );

        $response->assertStatus(302);
        $this->assertCount(2, $this->user->categoryTypes);
        $updatedCatType = CategoryType::find($this->catType1->id);
        $this->assertEquals($payload['name'], $updatedCatType->name);
        $this->assertEquals($payload['hex_color'], $updatedCatType->hex_color);
        $this->assertEquals($payload['active'], $updatedCatType->active);
    }

    #[Group('category_types')]
    public function test_category_type_destroy_linked_category()
    {
        $this->assertCount(2, $this->user->categoryTypes);
        $response = $this->delete(route('category_types.destroy', [ 'id' => $this->catType1->id ]));
        $response->assertStatus(302);
        $response->assertSessionHasErrors();
        $this->assertCount(2, $this->user->categoryTypes);
        $errors = session()->get('errors');
        $this->assertEquals('This category type is used by at least 1 category and cannot be deleted', $errors->first());
    }

    #[Group('category_types')]
    public function test_category_type_destroy_invalid_id()
    {
        $this->assertCount(2, $this->user->categoryTypes);
        $response = $this->delete(route('category_types.destroy', [ 'id' => 9999999 ]));
        $response->assertStatus(302);
        $response->assertSessionHasErrors();
        $this->assertCount(2, $this->user->categoryTypes);
        $errors = session()->get('errors');
        $this->assertEquals('Invalid category type id', $errors->first());
    }

    #[Group('category_types')]
    public function test_category_type_destroy()
    {
        $this->assertCount(2, $this->user->categoryTypes);
        Util::deleteMockCategories([
            $this->cat1->id,
        ]);
        $response = $this->delete(route('category_types.destroy', [ 'id' => $this->catType1->id ]));
        $response->assertStatus(302);
        $this->user->refresh();
        $response->assertSessionDoesntHaveErrors();
        $this->assertCount(1, $this->user->categoryTypes);
    }
}
