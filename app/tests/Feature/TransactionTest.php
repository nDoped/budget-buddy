<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use Inertia\Testing\AssertableInertia as Assert;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

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
        $response = $this->get(
            '/transactions',
            [
                'start_date' => '2024-06-01',
                'end_date' => '2024-06-30'
            ]
        );

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
        $response = $this->get(
            '/transactions',
            [
                'start_date' => '2024-06-01',
                'end_date' => '2024-06-30'
            ]
        );

        $response->assertInertia(fn (Assert $page) => $page
                 ->component('Transactions')
                 ->has('data.transactions_in_range', 1,  fn (Assert $page) => $page
                    ->where('amount', $transaction->amount / 100)
                    ->where('amount_raw', $transaction->amount)
                    ->etc()
                 )
        );
    }
}
