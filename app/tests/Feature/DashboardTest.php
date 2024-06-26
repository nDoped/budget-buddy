<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\CategoryType;
use App\Models\Transaction;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @group dashboard
     */
    public function test_dashboard(): void
    {
        $this->actingAs($user = User::factory()->create());
        $account1 = Account::factory()->for($user)->create([
            'name' => "My Kick Ass Savings Account"
        ]);
        $accountType1 = $account1->accountType;
        $accountType1->name = 'Savings';
        $accountType1->asset = true;
        $accountType1->save();
        $account2 = Account::factory()->for($user)->create([
            'name' => "My Credit Card"
        ]);
        $accountType2 = $account2->accountType;
        $accountType2->name = 'Credit';
        $accountType2->asset = false;
        $accountType2->save();
        $cat1 = Category::factory()->for($user)->create([
            'name' => 'cat1',
            'hex_color' => '#000000',
        ]);
        $cat2 = Category::factory()->for($user)->create([
            'name' => 'cat2',
            'hex_color' => '#ffffff',
        ]);
        $catType1 = $cat1->categoryType;
        $catType1->name = 'CatType1';
        $catType1->save();
        $catType2 = $cat2->categoryType;
        $catType2->name = 'CatType2';
        $catType2->save();
        $transaction0 = Transaction::factory()->for($account1)->create([
            'transaction_date' => '2024-05-14',
            'amount' => 42000, // $420.00
            'credit' => true,
        ]);
        // like amounts, we store percentages as integers
        $transaction0->categories()->save($cat1, ['percentage' => 100 * 100]);
        $transaction0->save();
        $transaction1 = Transaction::factory()->for($account1)->create([
            'transaction_date' => '2024-06-14',
            'amount' => 223444, // $2233.44
            'credit' => true,
        ]);
        // like amounts, we store percentages as integers
        $rawCatPercentage1 = 2513;
        $rawCatPercentage2 = 7487;
        $actualCatPercentage1 = $rawCatPercentage1 / 10000;
        $actualCatPercentage2 = $rawCatPercentage2 / 10000;
        $transaction1->categories()->save($cat1, [ 'percentage' => $rawCatPercentage1 ]);
        $transaction1->categories()->save($cat2, [ 'percentage' => $rawCatPercentage2 ]);
        $transaction1->save();
        $transaction2 = Transaction::factory()->for($account1)->create([
            'transaction_date' => '2024-06-14',
            'amount' => 25119, // $251.19
            'credit' => false,
        ]);
        $transaction2->categories()->save($cat1, [ 'percentage' => $rawCatPercentage1 ]);
        $transaction2->categories()->save($cat2, [ 'percentage' => $rawCatPercentage2 ]);
        $transaction2->save();
        $response = $this->get(
            '/dashboard',
            [
                'start_date' => '2024-06-01',
                'end_date' => '2024-06-30'
            ]
        );
        $transaction3 = Transaction::factory()->for($account2)->create([
            'transaction_date' => '2024-06-01',
            'amount' => 52523, // $525.23
            'credit' => false,
        ]);
        $transaction3->categories()->save($cat1, [ 'percentage' => $rawCatPercentage1 ]);
        $transaction3->categories()->save($cat2, [ 'percentage' => $rawCatPercentage2 ]);
        $transaction3->save();
        $transaction4 = Transaction::factory()->for($account2)->create([
            'transaction_date' => '2024-05-01',
            'amount' => 145634, // $1456.34
            'credit' => false,
        ]);
        $transaction4->categories()->save($cat1, [ 'percentage' => $rawCatPercentage1 ]);
        $transaction4->categories()->save($cat2, [ 'percentage' => $rawCatPercentage2 ]);
        $transaction4->save();
        $response = $this->get(
            '/dashboard',
            [
                'start_date' => '2024-06-01',
                'end_date' => '2024-06-30'
            ]
        );

        $response->assertInertia(fn (Assert $page) => $page
                 ->component('Dashboard')
                 ->has('data', 7)
                 ->where('data.total_economic_growth', ($transaction1->amount - $transaction2->amount - $transaction3->amount) / 100)
                 ->has('data.account_growth_line_data', 2)
                 ->has('data.account_growth_line_data.daily_economic_growth', 2)
                 ->where('data.account_growth_line_data.daily_economic_growth.2024-06-01', -$transaction3->amount / 100)
                 ->where('data.account_growth_line_data.daily_economic_growth.2024-06-14', ($transaction1->amount  - $transaction2->amount) / 100)
                 ->has('data.account_growth_line_data.total_economic_growth', 2)
                 ->where('data.account_growth_line_data.total_economic_growth.2024-06-01', -$transaction3->amount / 100)
                 ->where('data.account_growth_line_data.total_economic_growth.2024-06-14', ($transaction1->amount  - $transaction2->amount - $transaction3->amount) / 100)
                 ->has('data.debt_accounts', 2, fn (Assert $page) => $page
                    ->where('name', $account2->name)
                    ->where('in_range_net_growth', $transaction3->amount / 100)
                    ->where('pre_range_net_growth', $transaction4->amount) // this is a raw value, i don't think the front end uses it, but it's included so lets check it
                    ->where('start_balance', $transaction4->amount / 100)
                    ->where('end_balance', ($transaction4->amount + $transaction3->amount) / 100)
                    ->where('overdrawn_or_overpaid', false)
                    ->has('daily_balance_line_graph_data', 2)
                    ->where('daily_balance_line_graph_data.Start', $transaction4->amount / 100)
                    ->where('daily_balance_line_graph_data.2024-06-01', ($transaction4->amount + $transaction3->amount) / 100)
                    ->where('asset', 0)
                    ->etc()
                 )
                 ->has('data.debt_accounts.1', 6)
                 ->where('data.debt_accounts.1.name', 'Totals')
                 ->where('data.debt_accounts.1.start_balance', $transaction4->amount / 100)
                 ->where('data.debt_accounts.1.in_range_net_growth', $transaction3->amount / 100)
                 ->where('data.debt_accounts.1.end_balance', ($transaction4->amount + $transaction3->amount) / 100)
                 ->where('data.debt_accounts.1.asset', false)
                 ->has('data.asset_accounts', 2, fn (Assert $page) => $page
                    ->where('name', $account1->name)
                    ->where('in_range_net_growth', ($transaction1->amount - $transaction2->amount) / 100)
                    ->where('pre_range_net_growth', $transaction0->amount) // this is a raw value, i don't think the front end uses it, but it's included so lets check it
                    ->where('start_balance', $transaction0->amount / 100)
                    ->where('end_balance', ($transaction0->amount + $transaction1->amount - $transaction2->amount) / 100)
                    ->where('overdrawn_or_overpaid', false)
                    ->has('daily_balance_line_graph_data', 2)
                    ->where('daily_balance_line_graph_data.Start', $transaction0->amount / 100)
                    ->where('daily_balance_line_graph_data.2024-06-14', ($transaction0->amount + $transaction1->amount - $transaction2->amount) / 100)
                    ->where('asset', 1)
                    ->etc()
                 )
                 ->has('data.asset_accounts.1', 6)
                 ->where('data.asset_accounts.1.name', 'Totals')
                 ->where('data.asset_accounts.1.start_balance', $transaction0->amount / 100)
                 ->where('data.asset_accounts.1.in_range_net_growth', ($transaction1->amount - $transaction2->amount) / 100)
                 ->where('data.asset_accounts.1.end_balance', ($transaction0->amount + $transaction1->amount - $transaction2->amount) / 100)
                 ->where('data.asset_accounts.1.asset', true)
                 ->has('data.category_type_breakdowns', 2, fn (Assert $page) => $page
                    ->where('name', $catType1->name)
                    ->where('color', $catType1->hex_color)
                    ->where(
                        'total',
                        round(
                            (($transaction1->amount * $actualCatPercentage1)
                            + ($transaction2->amount * $actualCatPercentage1)
                            + ($transaction3->amount * $actualCatPercentage1)) / 100,
                            2
                        )
                    )
                    ->has('data.' . $cat1->id, 4)
                    ->where('data.' . $cat1->id . '.name', $cat1->name)
                    ->where(
                        'data.' . $cat1->id . '.value',
                        round(
                            (($transaction1->amount * $actualCatPercentage1)
                            + ($transaction2->amount * $actualCatPercentage1)
                            + ($transaction3->amount * $actualCatPercentage1)) / 100,
                            2
                        )
                    )
                    ->where('data.' . $cat1->id . '.color', $cat1->hex_color)
                    ->has( 'data.' . $cat1->id . '.transactions', 3,
                        function (Assert $page) use ($transaction1, $transaction2, $transaction3, $cat1) {
                            $current_trans = $page->toArray();
                            $transaction = null;
                            switch($current_trans['props']['id']) {
                                case $transaction1->id:
                                    $transaction = $transaction1;
                                    break;
                                case $transaction2->id:
                                    $transaction = $transaction2;
                                    break;
                                case $transaction3->id:
                                    $transaction = $transaction3;
                                    break;
                            }
                            $cats = $transaction->categories;
                            $cat_val = null;
                            foreach ($cats as $cat) {
                                if ($cat->id !== $cat1->id) {
                                    continue;
                                }
                                $percent = $cat->pivot->percentage;
                                $cat_val = ($transaction->amount * ($percent / 10000)) / 100;
                            }

                            return $page
                                ->where('id', $transaction->id)
                                ->where('date', $transaction->transaction_date)
                                ->where('note', $transaction->note)
                                ->where('cat_value', $cat_val)
                                ->etc();
                        }
                    )
                    ->etc()
                 )
                 ->has('data.category_type_breakdowns.' . $catType2->id, 4)
                 ->where('data.category_type_breakdowns.' . $catType2->id . '.name', $catType2->name)
                 ->where('data.category_type_breakdowns.' . $catType2->id . '.color', $catType2->hex_color)
                 ->where('data.category_type_breakdowns.' . $catType2->id . '.total',
                     round(
                         (($transaction1->amount * $actualCatPercentage2)
                         + ($transaction2->amount * $actualCatPercentage2)
                         + ($transaction3->amount * $actualCatPercentage2)) / 100,
                         2
                     )
                 )
                 ->has('data.category_type_breakdowns.' . $catType2->id . '.data.' . $cat2->id, 4)
                 ->where('data.category_type_breakdowns.' . $catType2->id . '.data.' . $cat2->id . '.name', $cat2->name)
                 ->where(
                     'data.category_type_breakdowns.' . $catType2->id . '.data.' . $cat2->id . '.value',
                     round(
                         (($transaction1->amount * $actualCatPercentage2)
                         + ($transaction2->amount * $actualCatPercentage2)
                         + ($transaction3->amount * $actualCatPercentage2)) / 100,
                         2
                     )
                 )
                 ->where('data.category_type_breakdowns.' . $catType2->id . '.data.' . $cat2->id . '.color', $cat2->hex_color)
                 ->has('data.category_type_breakdowns.' . $catType2->id . '.data.' . $cat2->id . '.transactions', 3,
                     function (Assert $page) use ($transaction1, $transaction2, $transaction3, $cat2) {
                         $current_trans = $page->toArray();
                         $transaction = null;
                         switch($current_trans['props']['id']) {
                             case $transaction1->id:
                                 $transaction = $transaction1;
                                 break;
                             case $transaction2->id:
                                 $transaction = $transaction2;
                                 break;
                             case $transaction3->id:
                                 $transaction = $transaction3;
                                 break;
                         }
                         $cats = $transaction->categories;
                         $cat_val = null;
                         foreach ($cats as $cat) {
                             if ($cat->id !== $cat2->id) {
                                 continue;
                             }
                             $percent = $cat->pivot->percentage;
                             $cat_val = ($transaction->amount * ($percent / 10000)) / 100;
                         }

                         return $page
                             ->where('id', $transaction->id)
                             ->where('date', $transaction->transaction_date)
                             ->where('note', $transaction->note)
                             ->where('cat_value', $cat_val)
                             ->etc();
                     }
                 )
                 ->etc()
                 /* ->dd('data') */
        );

    }
}
