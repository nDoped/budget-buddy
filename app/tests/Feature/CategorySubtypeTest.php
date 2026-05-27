<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\CategoryType;
use App\Models\CategorySubtype;
use \PHPUnit\Framework\Attributes\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Util;
use Database\Seeders\TestHarnessSeeder;

class CategorySubtypeTest extends TestCase
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
        $this->seed(TestHarnessSeeder::class);
        $this->user = User::find(TestHarnessSeeder::TESTING_USER_ID);
        $this->actingAs($this->user);
        $this->cat1 = Category::find(TestHarnessSeeder::CAT1_ID);
        $this->cat2 = Category::find(TestHarnessSeeder::CAT2_ID);
        $this->catType1 = $this->cat1->categoryType;
        $this->catType2 = $this->cat2->categoryType;
    }

    #[Group('category_subtypes')]
    public function test_category_subtype_post()
    {
        $this->assertCount(0, $this->user->categorySubtypes);
        $payload = [
            'name' => 'Meat',
            'category_type_id' => $this->catType1->id,
        ];
        $response = $this->post(
            '/settings/store_category_subtype',
            $payload
        );

        $response->assertStatus(302);
        $this->user->refresh();
        $this->assertCount(1, $this->user->categorySubtypes);
        $newSubtype = $this->user->categorySubtypes->last();
        $this->assertEquals($payload['name'], $newSubtype->name);
        $this->assertEquals($payload['category_type_id'], $newSubtype->category_type_id);
    }

    #[Group('category_subtypes')]
    public function test_category_subtype_post_missing_name()
    {
        $this->assertCount(0, $this->user->categorySubtypes);
        $response = $this->post('/settings/store_category_subtype', []);
        $response->assertStatus(302);
        $this->user->refresh();
        $this->assertCount(0, $this->user->categorySubtypes);
    }

    #[Group('category_subtypes')]
    public function test_category_subtype_patch()
    {
        $subtype = CategorySubtype::factory()->for($this->user)->create([
            'name' => 'Original Name',
            'category_type_id' => $this->catType1->id,
        ]);
        $this->assertCount(1, $this->user->categorySubtypes);

        $payload = [
            'name' => 'Updated Name',
        ];
        $response = $this->patch(
            '/category_subtypes/update/' . $subtype->id,
            $payload
        );

        $response->assertStatus(302);
        $this->assertCount(1, $this->user->categorySubtypes);
        $updatedSubtype = CategorySubtype::find($subtype->id);
        $this->assertEquals($payload['name'], $updatedSubtype->name);
    }

    #[Group('category_subtypes')]
    public function test_category_subtype_destroy_linked_category()
    {
        $subtype = CategorySubtype::factory()->for($this->user)->create([
            'name' => 'Test Subtype',
            'category_type_id' => $this->catType1->id,
        ]);
        $this->cat1->category_subtype_id = $subtype->id;
        $this->cat1->save();

        $response = $this->delete(route('category_subtypes.destroy', [ 'id' => $subtype->id ]));
        $response->assertStatus(302);
        $response->assertSessionHasErrors();
        $this->assertCount(1, $this->user->categorySubtypes);
        $errors = session()->get('errors');
        $this->assertEquals('This subtype is used by at least 1 category and cannot be deleted', $errors->first());
    }

    #[Group('category_subtypes')]
    public function test_category_subtype_destroy_invalid_id()
    {
        $this->assertCount(0, $this->user->categorySubtypes);
        $response = $this->delete(route('category_subtypes.destroy', [ 'id' => 9999999 ]));
        $response->assertStatus(302);
        $response->assertSessionHasErrors();
        $this->assertCount(0, $this->user->categorySubtypes);
        $errors = session()->get('errors');
        $this->assertEquals('Invalid category subtype id', $errors->first());
    }

    #[Group('category_subtypes')]
    public function test_category_subtype_destroy()
    {
        $subtype = CategorySubtype::factory()->for($this->user)->create([
            'name' => 'Test Subtype',
            'category_type_id' => $this->catType1->id,
        ]);
        $this->assertCount(1, $this->user->categorySubtypes);

        $response = $this->delete(route('category_subtypes.destroy', [ 'id' => $subtype->id ]));
        $response->assertStatus(302);
        $this->user->refresh();
        $response->assertSessionDoesntHaveErrors();
        $this->assertCount(0, $this->user->categorySubtypes);
    }

    #[Group('category_subtypes')]
    public function test_settings_page_lists_subtypes()
    {
        $subtype = CategorySubtype::factory()->for($this->user)->create([
            'name' => 'Test Subtype',
            'category_type_id' => $this->catType1->id,
        ]);
        $response = $this->get(route('settings.category_subtypes'));
        $response->assertInertia(function ($page) use ($subtype) {
            $page->component('Settings/Subtypes')
                ->has('category-subtypes', 1)
                ->where('category-subtypes.0.name', 'Test Subtype')
                ->where('category-subtypes.0.category_type_name', $this->catType1->name);
        });
    }

    #[Group('categories')]
    public function test_category_post_with_subtype()
    {
        $subtype = CategorySubtype::factory()->for($this->user)->create([
            'name' => 'Meat',
            'category_type_id' => $this->catType1->id,
        ]);
        $this->assertCount(3, $this->user->categories);

        $payload = [
            'name' => 'Chicken',
            'hex_color' => '#000000',
            'category_type' => $this->catType1->id,
            'category_subtype' => $subtype->id,
        ];
        $response = $this->post('/settings/store_category', $payload);
        $response->assertStatus(302);
        $this->user->refresh();
        $this->assertCount(4, $this->user->categories);
        $newCat = $this->user->categories->last();
        $this->assertEquals($payload['name'], $newCat->name);
        $this->assertEquals($payload['category_subtype'], $newCat->category_subtype_id);
    }

    #[Group('categories')]
    public function test_category_patch_with_subtype()
    {
        $subtype = CategorySubtype::factory()->for($this->user)->create([
            'name' => 'Meat',
            'category_type_id' => $this->catType2->id,
        ]);
        $this->assertNull($this->cat1->category_subtype_id);

        $payload = [
            'name' => $this->cat1->name,
            'hex_color' => $this->cat1->hex_color,
            'category_type' => $this->catType2->id,
            'category_subtype' => $subtype->id,
            'active' => $this->cat1->active,
        ];
        $response = $this->patch('/categories/update/' . $this->cat1->id, $payload);
        $response->assertStatus(302);
        $updatedCat = Category::find($this->cat1->id);
        $this->assertEquals($payload['category_subtype'], $updatedCat->category_subtype_id);
    }
}
