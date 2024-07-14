<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Account;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use \PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;
use Database\Seeders\FeatureTestSeeder;
class SettingsTest extends TestCase
{
    use RefreshDatabase;
    private $cat1;
    private $cat2;
    private $catType1;
    private $catType2;
    private $savingsAccount;
    private $creditCardAccount;
    private $user;

    protected function setup(): void
    {
        parent::setUp();
        $this->seed(FeatureTestSeeder::class);
        $this->user = User::find(100000);
        $this->savingsAccount = Account::find(100001);
        $this->creditCardAccount = Account::find(100002);
        $this->actingAs($this->user);
        $this->cat1 = Category::find(100003);
        $this->cat2 = Category::find(100004);
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
}
