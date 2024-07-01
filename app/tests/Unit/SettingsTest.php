<?php

namespace Tests\Unit;

//use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\FeatureTestSeeder;


class SettingsTest extends TestCase
{
    use RefreshDatabase;
    private $savingsTransaction0;
    private $savingsTransaction1;
    private $savingsTransaction2;
    private $creditTransaction1;
    private $creditTransaction2;
    private $savingsAccount;
    private $creditCardAccount;
    private $cat1;
    private $cat2;
    private $cat3;
    private $catType1;
    private $catType2;
    private $actualCatPercentage1;
    private $actualCatPercentage2;
    private $user;

    protected function setup(): void
    {
        parent::setUp();
        $this->seed(FeatureTestSeeder::class);
        $this->user = User::find(100000);
        $this->actingAs($this->user);
        $this->savingsAccount = Account::find(100001);
        $this->creditCardAccount = Account::find(100002);

        $this->assertEquals($this->savingsAccount->user_id, $this->user->id);
        $this->cat1 = Category::find(100003);
        $this->cat2 = Category::find(100004);
        $this->cat3 = Category::find(100005);
        $this->catType1 = $this->cat1->categoryType;
        $this->catType2 = $this->cat2->categoryType;

        // 100% cat1
        $this->savingsTransaction0 = Transaction::find(100006);
        $this->savingsTransaction1 = Transaction::find(100007);
        // savingsTransaction1 is cat1 and cat3.. we need to get the actual percentages for the tests
        foreach ($this->savingsTransaction1->categories as $cat) {
            $percent = $cat->pivot->percentage;
            switch ($cat->id) {
                case $this->cat1->id:
                    $this->actualCatPercentage1 = $percent / 10000;;
                    break;
                case $this->cat3->id:
                    $this->actualCatPercentage2 = $percent / 10000;;
                    break;
            }
        }

        $this->savingsTransaction2 = Transaction::find(100008);
        $this->creditTransaction1 = Transaction::find(100009);
        $this->creditTransaction2 = Transaction::find(100010);
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_store_account()
    {
        $this->assertTrue(true);
    }
}
