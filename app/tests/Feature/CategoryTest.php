<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use \PHPUnit\Framework\Attributes\Group;
use Tests\Util;
use Database\Seeders\FeatureTestSeeder;

class CategoryTest extends TestCase
{
    use RefreshDatabase;
    private $savingsTransaction0;
    private $savingsTransaction1;
    private $savingsTransaction2;
    private $creditTransaction1;
    private $creditTransaction2;
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
        $this->savingsTransaction0 = Transaction::find(100006);
        $this->savingsTransaction1 = Transaction::find(100007);
        $this->savingsTransaction2 = Transaction::find(100008);
        $this->creditTransaction1 = Transaction::find(100009);
        $this->creditTransaction2 = Transaction::find(100010);
    }

    #[Group('categories')]
    public function test_category_post()
    {
        $this->assertCount(3, $this->user->categories);
        $payload = [
            'name' => 'a newly posted category',
            'hex_color' => '#000000',
            'category_type' => $this->catType1->id
        ];
        $response = $this->post(
            '/settings/store_category',
            $payload
        );

        $response->assertStatus(302);
        $this->user->refresh();
        $this->assertCount(4, $this->user->categories);
        $newCat = $this->user->categories->last();
        $this->assertEquals($payload['name'], $newCat->name);
        $this->assertEquals($payload['hex_color'], $newCat->hex_color);
        $this->assertEquals($payload['category_type'], $newCat->categoryType->id);
    }

    #[Group('categories')]
    public function test_category_post_missing_name()
    {
        $this->assertCount(3, $this->user->categories);
        $response = $this->post('/settings/store_category', []);
        $response->assertStatus(302);
        $this->user->refresh();
        $this->assertCount(3, $this->user->categories);
    }

    #[Group('categories')]
    public function test_category_patch()
    {
        $this->assertCount(3, $this->user->categories);
        $payload = [
            'name' => 'an updated name',
            'hex_color' => '#ffff12',
            'category_type' => $this->catType2->id,
            'active' => false
        ];
        $this->assertEquals($this->catType1->id, $this->cat1->categoryType->id);
        $response = $this->patch(
            '/categories/update/' . $this->cat1->id,
            $payload
        );

        $response->assertStatus(302);
        $this->assertCount(3, $this->user->categories);
        $updatedCat = Category::find($this->cat1->id);
        $this->assertEquals($payload['name'], $updatedCat->name);
        $this->assertEquals($payload['hex_color'], $updatedCat->hex_color);
        $this->assertEquals($payload['category_type'], $updatedCat->categoryType->id);
        $this->assertEquals($payload['active'], $updatedCat->active);
    }

    #[Group('categories')]
    public function test_category_destroy_linked_transaction()
    {
        $this->assertCount(3, $this->user->categories);
        $response = $this->delete(route('categories.destroy', [ 'id' => $this->cat1->id ]));
        $response->assertStatus(302);
        $response->assertSessionHasErrors();
        $this->assertCount(3, $this->user->categories);
        $errors = session()->get('errors');
        $this->assertEquals('This category appears on at least 1 transaction and cannot be deleted', $errors->first());
    }

    #[Group('categories')]
    public function test_category_destroy_invalid_id()
    {
        $this->assertCount(3, $this->user->categories);
        $response = $this->delete(route('categories.destroy', [ 'id' => 9999999 ]));
        $response->assertStatus(302);
        $response->assertSessionHasErrors();
        $this->assertCount(3, $this->user->categories);
        $errors = session()->get('errors');
        $this->assertEquals('Invalid category id', $errors->first());
    }

    #[Group('categories')]
    public function test_category_destroy()
    {
        $this->assertCount(3, $this->user->categories);
        Util::deleteMockTransactions([
            $this->creditTransaction1->id,
            $this->creditTransaction2->id,
            $this->savingsTransaction0->id,
            $this->savingsTransaction1->id,
            $this->savingsTransaction2->id
        ]);
        $response = $this->delete(route('categories.destroy', [ 'id' => $this->cat1->id ]));
        $response->assertStatus(302);
        $response->assertSessionDoesntHaveErrors();
        $this->user->refresh();
        $this->assertCount(2, $this->user->categories);
    }
}
