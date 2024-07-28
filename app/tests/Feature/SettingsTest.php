<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Account;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use \PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;
use Database\Seeders\TestHarnessSeeder;
class SettingsTest extends TestCase
{
    use RefreshDatabase;
    private $cat1;
    private $cat2;
    private $catType1;
    private $catType2;
    private $savingsAccount;
    private $creditCardAccount;
    private $debitAccountType;
    private $creditAccountType;
    private $user;

    protected function setup(): void
    {
        parent::setUp();
        $this->seed(TestHarnessSeeder::class);
        $this->user = User::find(TestHarnessSeeder::TESTING_USER_ID);
        $this->savingsAccount = Account::find(TestHarnessSeeder::SAVINGS_ACCOUNT_ID);
        $this->creditAccountType = $this->savingsAccount->accountType;
        $this->creditCardAccount = Account::find(TestHarnessSeeder::CREDIT_CARD_ACCOUNT_ID);
        $this->debitAccountType = $this->creditCardAccount->accountType;
        $this->actingAs($this->user);
        $this->cat1 = Category::find(TestHarnessSeeder::CAT1_ID);
        $this->cat2 = Category::find(TestHarnessSeeder::CAT2_ID);
        $this->catType1 = $this->cat1->categoryType;
        $this->catType2 = $this->cat2->categoryType;
    }

    #[Group('settings')]
    public function test_index()
    {
        $this->assertCount(3, $this->user->categories);
        $this->get(
            '/settings',
        )->assertInertia(
            function (Assert $page) {
                $data = $page->toArray()['props']['data'];
                $this->assertCount(2, $data);
                $this->assertCount(2, $data['accounts']);
                $this->assertCount(2, $data['account_types']);
                $expectedAcctIds = [$this->savingsAccount->id, $this->creditCardAccount->id];
                foreach ($data['accounts'] as $acct) {
                    $this->assertContains($acct['id'], $expectedAcctIds);
                }
                $expectedAcctTypeIds = [$this->savingsAccount->accountType->id, $this->creditCardAccount->accountType->id];
                foreach ($data['account_types'] as $acctType) {
                    $this->assertContains($acctType['id'], $expectedAcctTypeIds);
                }
            }
        );
    }

    #[Group('settings')]
    public function test_categories()
    {
        $this->get(
            route('settings.categories'),
        )->assertInertia(
            function (Assert $page) {
                $props = $page->toArray()['props'];
                $catsProp = $props['categories'];
                $catTypesProp = $props['category-types'];
                $this->assertCount(3, $catsProp);
                $this->assertCount(2, $catTypesProp);
            }
        );
    }

    #[Group('settings')]
    public function test_account_types()
    {
        $this->get(
            route('settings.account_types'),
        )->assertInertia(
            function (Assert $page) {
                $data = $page->toArray()['props']['data'];
                $catTypesProp = $data['account_types'];
                $this->assertCount(2, $catTypesProp);
            }
        );
    }


    #[Group('settings')]
    public function test_category_types()
    {
        $this->get(
            route('settings.category_types'),
        )->assertInertia(
            function (Assert $page) {
                $catTypesProp = $page->toArray()['props']['category-types'];
                $this->assertCount(2, $catTypesProp);
            }
        );
    }

    #[Group('settings')]
    public function test_store_account()
    {
        $this->assertCount(1, $this->creditAccountType->accounts);
        $this->assertEquals($this->user, $this->creditAccountType->user);
        $this->assertCount(2, $this->user->accounts);
        $payload = [
            'name' => 'New Account',
            'type' => $this->debitAccountType->id,
            'url' => 'https://kabt.example.org'
        ];
        $response = $this->post(route('accounts.store'), $payload);
        $response->assertStatus(302);
        $response->assertSessionDoesntHaveErrors();
        $this->user->refresh();
        $this->assertCount(3, $this->user->accounts);
        $newAcct = $this->user->accounts->last();
        $this->assertEquals($payload['name'], $newAcct->name);
        $this->assertEquals($payload['type'], $newAcct->type_id);
        $this->assertEquals($payload['url'], $newAcct->url);
    }

    #[Group('settings')]
    public function test_store_account_no_name()
    {
        $this->assertCount(2, $this->user->accounts);
        $response = $this->post(
            route('accounts.store'),
            [
                'type' => $this->debitAccountType->id,
            ]
        );
        $response->assertStatus(302);
        $response->assertSessionHasErrors();
        $this->user->refresh();
        $this->assertCount(2, $this->user->accounts);
        $errors = session()->get('errors');
        $this->assertEquals('The name field is required.', $errors->first());
    }

    #[Group('settings')]
    public function test_store_account_no_type_id()
    {
        $this->assertCount(2, $this->user->accounts);
        $response = $this->post(
            route('accounts.store'),
            [
                'name' => 'New Account',
            ]
        );
        $response->assertStatus(302);
        $response->assertSessionHasErrors();
        $this->user->refresh();
        $this->assertCount(2, $this->user->accounts);
        $errors = session()->get('errors');
        $this->assertEquals('The type field is required.', $errors->first());
    }

    #[Group('settings')]
    public function test_store_account_invalid_url()
    {
        $this->assertCount(2, $this->user->accounts);
        $response = $this->post(
            route('accounts.store'),
            [
                'name' => 'New Account',
                'type' => $this->debitAccountType->id,
                'url' => 'not a url'
            ]
        );
        $response->assertStatus(302);
        $response->assertSessionHasErrors();
        $this->user->refresh();
        $this->assertCount(2, $this->user->accounts);
        $errors = session()->get('errors');
        $this->assertEquals('The url must be a valid URL eg https://example.org', $errors->first());
    }

    #[Group('settings')]
    public function test_store_account_type()
    {
        $this->assertCount(2, $this->user->accountTypes);
        $payload = [
            'name' => 'New Account',
            'asset' => true
        ];
        $response = $this->post(route('account_types.store'), $payload);
        $response->assertStatus(302);
        $response->assertSessionDoesntHaveErrors();

        $this->user->refresh();
        $this->assertCount(3, $this->user->accountTypes);
        $newAcctType = $this->user->accountTypes->last();
        $this->assertEquals($payload['name'], $newAcctType->name);
        $this->assertEquals($payload['asset'], $newAcctType->asset);
    }

    #[Group('settings')]
    public function test_store_account_type_no_name()
    {
        $this->assertCount(2, $this->user->accountTypes);
        $response = $this->post(
            route('account_types.store'),
            [
                'asset' => true
            ]
        );
        $response->assertStatus(302);
        $response->assertSessionHasErrors();
        $this->user->refresh();
        $this->assertCount(2, $this->user->accountTypes);
        $errors = session()->get('errors');
        $this->assertEquals('The name field is required.', $errors->first());
    }

    #[Group('settings')]
    public function test_store_account_type_no_asset()
    {
        $this->assertCount(2, $this->user->accountTypes);
        $response = $this->post(
            route('account_types.store'),
            [
                'name' => "account type"
            ]
        );
        $response->assertStatus(302);
        $response->assertSessionHasErrors();
        $this->user->refresh();
        $this->assertCount(2, $this->user->accountTypes);
        $errors = session()->get('errors');
        $this->assertEquals('The asset field is required.', $errors->first());
    }
}
