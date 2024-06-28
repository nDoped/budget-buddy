<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\FeatureTestSeeder;

use Inertia\Testing\AssertableInertia as Assert;

class TransactionTest extends TestCase
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

    protected function setup(): void
    {
        parent::setUp();
        $this->seed(FeatureTestSeeder::class);
        $user = User::find(100000);
        $this->actingAs($user);
        $this->savingsAccount = Account::find(100001);
        $this->creditCardAccount = Account::find(100002);

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
     *
     * @return void
     */
    public function test_transaction_post()
    {
        $this->actingAs($user = User::factory()->create());
        $account = Account::factory()->for($user)->create();
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();
        $response = $this->post(
            '/transactions/store',
            [
                'account_id' => $account->id,
                'amount' => 1000,
                'credit' => true,
                'note' => 'test note',
                'trans_buddy' => false,
                'recurring' => false,
                'categories' => [
                    [
                        'cat_data' => [
                            'hex_color' => $cat1->hex_color,
                            'cat_id' => $cat1->id,
                            'name' => $cat1->name,
                        ],
                        'percent' => 50

                    ],
                    [
                        'cat_data' => [
                            'hex_color' => $cat2->hex_color,
                            'cat_id' => $cat2->id,
                            'name' => $cat2->name,
                        ],
                        'percent' => 50
                    ]
                ],
                'transaction_date' => '2021-09-01',
            ]
        );
        $response->assertStatus(302);

        $this->assertEquals(1, count($user->transactions));
        $this->assertEquals(1, count($user->accounts));
        $trans =  $user->transactions[0];
        $account =  $user->accounts[0];
        $this->assertEquals(1000 * 100, $trans->amount);
        $this->assertEquals($account->id, $trans->account_id);
        //$this->assertEquals(1000, $user->transactions->pluck('amount'));
        $this->assertEquals(2, count($trans->categories));

        /* var_dump([ */
        /*     'Feature/TransactionTest.php:48 amound' => $user->transactions[0]->categories, */
        /*     //'Feature/TransactionTest.php:48 cats' => $user->transactions->pluck('categories')->toArray(), */
        /* ]); */
    }

    /**
     *
     * @return void
     */
    public function test_transaction_get_two_transactions_in_range()
    {
        $this->actingAs($user = User::factory()->create());
        $account = Account::factory()->for($user)->create();
        $transaction = Transaction::factory()->for($account)->create();
        $transaction->transaction_date = '2024-06-15';
        $transaction->save();
        $transaction2 = Transaction::factory()->for($account)->create();
        $transaction2->transaction_date = '2024-06-14';
        $transaction2->save();
        $params = [
            'start' => '2024-06-01',
            'end' => '2024-06-30',
        ];
        $query = http_build_query($params);
        $response = $this->get('/transactions?' . $query);

        $response->assertInertia(fn (Assert $page) => $page
                 ->component('Transactions')
                 ->has('data.transactions_in_range', 2,  fn (Assert $page) => $page
                    ->where('amount', $transaction->amount / 100)
                    ->where('amount_raw', $transaction->amount)
                    ->etc()
                 )
                 ->has('data.transactions_in_range.1', fn (Assert $page) => $page
                    ->where('amount', $transaction2->amount / 100)
                    ->where('amount_raw', $transaction2->amount)
                    ->etc()
                 )
        );
    }

    /**
     *
     * @return void
     */
    public function test_transaction_get_one_transaction_in_range()
    {
        $this->actingAs($user = User::factory()->create());
        $account = Account::factory()->for($user)->create();
        $transaction = Transaction::factory()->for($account)->create();
        $transaction->transaction_date = '2024-06-15';
        $transaction->save();
        $transaction2 = Transaction::factory()->for($account)->create();
        $transaction2->transaction_date = '2024-05-14';
        $transaction2->save();
        $params = [
            'start' => '2024-06-01',
            'end' => '2024-06-30',
        ];
        $query = http_build_query($params);
        $response = $this->get('/transactions?' . $query);

        $response->assertInertia(fn (Assert $page) => $page
                 ->component('Transactions')
                 ->has('data.transactions_in_range', 1,  fn (Assert $page) => $page
                    ->where('amount', $transaction->amount / 100)
                    ->where('amount_raw', $transaction->amount)
                    ->etc()
                 )
        );
    }

    /**
     *
     * @return void
     */
    public function test_transaction_get_with_account_filter()
    {
        $this->savingsTransaction0->transaction_date = '2024-06-15';
        $this->savingsTransaction0->save();
        $this->creditTransaction2->transaction_date = '2024-06-17';
        $this->creditTransaction2->save();
        $params = [
            'start' => '2024-06-01',
            'end' => '2024-06-30',
            'filter_accounts' => [
                $this->savingsAccount->id
            ]
        ];
        $query = http_build_query($params);
        $this->get('/transactions?' . $query)->assertInertia(
            function (Assert $page) {
                $data = $page->toArray()['props']['data'];
                $transactions_in_range = $data['transactions_in_range'];
                $this->assertEquals(3, count($transactions_in_range));
                $expected_transactions = [
                    $this->savingsTransaction0,
                    $this->savingsTransaction1,
                    $this->savingsTransaction2
                ];
                foreach ($transactions_in_range as $trans) {
                    $this->_checkTransaction($trans, $expected_transactions, $this->savingsAccount);
                }

                return $page->component('Transactions');
            }
        );
    }

    private function _checkTransaction(
        array $trans_to_check,
        array $expected_transactions,
        Account $account
    ) {
        $current_trans = array_filter(
            $expected_transactions,
            function ($trans) use ($trans_to_check) {
                return $trans->id === $trans_to_check['id'];
            }
        );
        $this->_checkValues($trans_to_check, array_pop($current_trans), $account);
    }
    private function _checkValues(array $trans_array, Transaction $transaction, Account $account)
    {
        $this->assertEquals($transaction->transaction_date, $trans_array['transaction_date']);
        $this->assertEquals($transaction->amount, $trans_array['amount_raw']);
        $this->assertEquals($transaction->amount / 100, $trans_array['amount']);
        $this->assertEquals($account->name, $trans_array['account']);
        $this->assertEquals($transaction->note, $trans_array['note']);
        $this->_checkCatVal($trans_array['categories'], $transaction);
    }
    private function _checkCatVal(array $categories, Transaction $transaction)
    {
        foreach ($categories as $cat) {
            $cat_data = $cat['cat_data'];
            $cat_id = $cat_data['cat_id'];
            $cat_obj = Category::find($cat_id);
            $this->assertEquals($cat_obj->name, $cat_data['name']);
            $percentage = null;
            foreach ($transaction->categories as $trans_cat) {
                if ($trans_cat->id === $cat_id) {
                    $percentage = $trans_cat->pivot->percentage;
                    break;
                }
            }
            $this->assertEquals($percentage / 100, $cat['percent']);
        }
    }
}
