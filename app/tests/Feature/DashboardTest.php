<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
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
        $savings_account = Account::factory()->for($user)->create([
            'name' => "My Kick Ass Savings Account"
        ]);
        $savingsAccntType = $savings_account->accountType;
        $savingsAccntType->name = 'Savings';
        $savingsAccntType->asset = true;
        $savingsAccntType->save();
        $cc_account = Account::factory()->for($user)->create([
            'name' => "My Credit Card"
        ]);
        $creditAccountType = $cc_account->accountType;
        $creditAccountType->name = 'Credit';
        $creditAccountType->asset = false;
        $creditAccountType->save();
        $cat1 = Category::factory()->for($user)->create([
            'name' => 'cat1',
            'hex_color' => '#000000',
        ]);
        $cat2 = Category::factory()->for($user)->create([
            'name' => 'cat2',
            'hex_color' => '#ffffff',
        ]);
        $cat3 = Category::factory()->for($user)->create([
            'name' => 'cat3',
            'hex_color' => '#aaaaaa',
        ]);
        $catType1 = $cat1->categoryType;
        $catType1->name = 'CatType1';
        $catType1->save();
        $catType2 = $cat2->categoryType;
        $catType2->name = 'CatType2';
        $catType2->save();
        $cat3->category_type_id = $catType2->id;
        $cat3->save();

        $rawCatPercentage1 = 2513;
        $rawCatPercentage2 = 7487;
        $actualCatPercentage1 = $rawCatPercentage1 / 10000;
        $actualCatPercentage2 = $rawCatPercentage2 / 10000;

        $savingsTransaction0 = Transaction::factory()->for($savings_account)->create([
            'transaction_date' => '2024-05-14',
            'amount' => 42000, // $420.00
            'credit' => true,
            'note' => 'SavingsTransaction0'
        ]);
        // like amounts, we store percentages as integers
        $savingsTransaction0->categories()->save($cat1, ['percentage' => 100 * 100]);
        $savingsTransaction0->save();

        $savingsTransaction1 = Transaction::factory()->for($savings_account)->create([
            'transaction_date' => '2024-06-14',
            'amount' => 223444, // $2233.44
            'credit' => true,
            'note' => 'SavingsTransaction1'
        ]);
        // like amounts, we store percentages as integers
        $savingsTransaction1->categories()->save($cat1, [ 'percentage' => $rawCatPercentage1 ]);
        $savingsTransaction1->categories()->save($cat3, [ 'percentage' => $rawCatPercentage2 ]);
        $savingsTransaction1->save();

        $savingsTransaction2 = Transaction::factory()->for($savings_account)->create([
            'transaction_date' => '2024-06-14',
            'amount' => 25119, // $251.19
            'credit' => false,
            'note' => 'SavingsTransaction2'
        ]);
        $savingsTransaction2->categories()->save($cat1, [ 'percentage' => $rawCatPercentage1 ]);
        $savingsTransaction2->categories()->save($cat2, [ 'percentage' => $rawCatPercentage2 ]);
        $savingsTransaction2->save();

        $creditTransaction1 = Transaction::factory()->for($cc_account)->create([
            'transaction_date' => '2024-06-01',
            'amount' => 52523, // $525.23
            'credit' => false,
            'note' => 'CreditTransaction1'
        ]);
        $creditTransaction1->categories()->save($cat1, [ 'percentage' => $rawCatPercentage1 ]);
        $creditTransaction1->categories()->save($cat2, [ 'percentage' => $rawCatPercentage2 ]);
        $creditTransaction1->save();

        $creditTransaction2 = Transaction::factory()->for($cc_account)->create([
            'transaction_date' => '2024-05-01',
            'amount' => 145634, // $1456.34
            'credit' => false,
            'note' => 'CreditTransaction2'
        ]);
        $creditTransaction2->categories()->save($cat1, [ 'percentage' => $rawCatPercentage1 ]);
        $creditTransaction2->categories()->save($cat2, [ 'percentage' => $rawCatPercentage2 ]);
        $creditTransaction2->save();

        $this->get(
            '/dashboard',
            [
                'start_date' => '2024-06-01',
                'end_date' => '2024-06-30'
            ]
        )->assertInertia(
            function (Assert $page)
                use(
                    $savingsTransaction0,
                    $savingsTransaction1,
                    $savingsTransaction2,
                    $creditTransaction1,
                    $creditTransaction2,
                    $savings_account,
                    $cc_account,
                    $cat1,
                    $cat2,
                    $cat3,
                    $catType1,
                    $catType2,
                    $actualCatPercentage1,
                    $actualCatPercentage2
                ) {
                $data = $page->toArray()['props']['data'];
                $this->assertEquals(7, count($data));
                $this->assertEquals(
                    ($savingsTransaction1->amount - $savingsTransaction2->amount - $creditTransaction1->amount) / 100,
                    $data['total_economic_growth']
                );

                /*
                 * account_growth_line_data
                 */
                $this->assertEquals(2, count($data['account_growth_line_data']));
                $account_growth_line_data = $data['account_growth_line_data'];
                $this->assertEquals(2, count($account_growth_line_data['daily_economic_growth']));
                $daily_economic_growth = $account_growth_line_data['daily_economic_growth'];
                $this->assertEquals(
                    -$creditTransaction1->amount / 100,
                    $daily_economic_growth['2024-06-01']
                );
                $this->assertEquals(
                    ($savingsTransaction1->amount - $savingsTransaction2->amount) / 100,
                    $daily_economic_growth['2024-06-14']
                );
                $total_economic_growth = $account_growth_line_data['total_economic_growth'];
                $this->assertEquals(
                    -$creditTransaction1->amount / 100,
                    $total_economic_growth['2024-06-01']
                );
                $this->assertEquals(
                    ($savingsTransaction1->amount  - $savingsTransaction2->amount - $creditTransaction1->amount) / 100,
                    $total_economic_growth['2024-06-14']
                );

                /*
                 * debt_accounts
                 */
                $this->assertEquals(2, count($data['debt_accounts']));
                $credit_card = $data['debt_accounts'][0];
                $this->isFalse($credit_card['asset']);
                $this->assertEquals($cc_account->name, $credit_card['name']);
                $this->assertEquals($creditTransaction1->amount / 100, $credit_card['in_range_net_growth']);
                // this is a raw value, i don't think the front end uses it, but it's included so lets check it */
                $this->assertEquals($creditTransaction2->amount, $credit_card['pre_range_net_growth']);
                $this->assertFalse($credit_card['overdrawn_or_overpaid']);
                $this->assertEquals($creditTransaction2->amount / 100, $credit_card['start_balance']);
                $this->assertEquals(($creditTransaction2->amount + $creditTransaction1->amount) / 100, $credit_card['end_balance']);

                $cc_daily_balance_line_graph_data = $credit_card['daily_balance_line_graph_data'];
                $this->assertEquals($creditTransaction2->amount / 100, $cc_daily_balance_line_graph_data['Start']);
                $this->assertEquals(($creditTransaction2->amount + $creditTransaction1->amount) / 100, $cc_daily_balance_line_graph_data['2024-06-01']);
                $cc_daily_net_growths = $credit_card['daily_net_growths'];
                $this->assertEquals(1, count($cc_daily_net_growths));
                // this is raw..idk if it's actually used on the front end
                $this->assertEquals($creditTransaction1->amount, $cc_daily_net_growths['2024-06-01']);
                $debt_accounts_totals = $data['debt_accounts'][1];
                $this->assertEquals(6, count($debt_accounts_totals));
                $this->assertEquals('Totals', $debt_accounts_totals['name']);
                $this->assertEquals($creditTransaction2->amount / 100, $debt_accounts_totals['start_balance']);
                $this->assertEquals($creditTransaction1->amount / 100, $debt_accounts_totals['in_range_net_growth']);
                $this->assertEquals(($creditTransaction2->amount + $creditTransaction1->amount) / 100, $debt_accounts_totals['end_balance']);
                $this->isFalse($debt_accounts_totals['asset']);

                /*
                 * asset_accounts
                 */
                $asset_accounts = $data['asset_accounts'];
                $this->assertEquals(2, count($asset_accounts));
                $savings = $asset_accounts[0];
                $this->isTrue($savings['asset']);
                $this->assertEquals($savings_account->name, $savings['name']);
                $this->assertEquals(($savingsTransaction1->amount - $savingsTransaction2->amount) / 100, $savings['in_range_net_growth']);
                // this is a raw value, i don't think the front end uses it, but it's included so lets check it
                $this->assertEquals($savingsTransaction0->amount, $savings['pre_range_net_growth']);
                $this->assertFalse($savings['overdrawn_or_overpaid']);
                $this->assertEquals($savingsTransaction0->amount / 100, $savings['start_balance']);
                $this->assertEquals(($savingsTransaction0->amount + $savingsTransaction1->amount - $savingsTransaction2->amount) / 100, $savings['end_balance']);
                $savings_daily_balance_line_graph_data = $savings['daily_balance_line_graph_data'];
                $this->assertEquals($savingsTransaction0->amount / 100, $savings_daily_balance_line_graph_data['Start']);
                $this->assertEquals(($savingsTransaction0->amount + $savingsTransaction1->amount - $savingsTransaction2->amount) / 100, $savings_daily_balance_line_graph_data['2024-06-14']);
                $savings_daily_net_growths = $savings['daily_net_growths'];
                $this->assertEquals(1, count($savings_daily_net_growths));
                // this is raw..idk if it's actually used on the front end
                $this->assertEquals($savingsTransaction1->amount - $savingsTransaction2->amount, $savings_daily_net_growths['2024-06-14']);

                /*
                 * category_type_breakdowns
                 */
                $category_type_breakdowns = $data['category_type_breakdowns'];
                $this->assertEquals(2, count($category_type_breakdowns));

                // CatType1
                $cat_type1 = $category_type_breakdowns[$catType1->id];
                $this->assertEquals($catType1->name, $cat_type1['name']);
                $this->assertEquals($catType1->hex_color, $cat_type1['color']);
                $this->assertEquals(
                    round(
                        (($savingsTransaction1->amount * $actualCatPercentage1)
                        + ($savingsTransaction2->amount * $actualCatPercentage1)
                        + ($creditTransaction1->amount * $actualCatPercentage1)) / 100,
                        2
                    ),
                    $cat_type1['total']
                );

                $cat_type_1_categories = $cat_type1['data'];
                $this->assertEquals(1, count($cat_type_1_categories));

                $cat1_data = $cat_type_1_categories[$cat1->id];
                $this->assertEquals($cat1->name, $cat1_data['name']);
                $this->assertEquals(
                    round(
                        (($savingsTransaction1->amount * $actualCatPercentage1)
                        + ($savingsTransaction2->amount * $actualCatPercentage1)
                        + ($creditTransaction1->amount * $actualCatPercentage1)) / 100,
                        2
                    ),
                    $cat1_data['value']
                );
                $this->assertEquals($cat1->hex_color, $cat1_data['color']);

                $cat1_transactions = $cat1_data['transactions'];
                $this->assertEquals(3, count($cat1_transactions));
                $expected_transactions = [
                    $savingsTransaction1,
                    $savingsTransaction2,
                    $creditTransaction1
                ];
                foreach ($cat1_transactions as $transaction) {
                    $this->_checkTransaction($transaction, $expected_transactions, $cat1->id);
                }

                // CatType2
                $cat_type2 = $category_type_breakdowns[$catType2->id];
                $this->assertEquals($catType2->name, $cat_type2['name']);
                $this->assertEquals($catType2->hex_color, $cat_type2['color']);
                $this->assertEquals(
                    round(
                        (($savingsTransaction1->amount * $actualCatPercentage2)
                        + ($savingsTransaction2->amount * $actualCatPercentage2)
                        + ($creditTransaction1->amount * $actualCatPercentage2)) / 100,
                        2
                    ),
                    $cat_type2['total']
                );

                $cat_type_2_categories = $cat_type2['data'];
                $this->assertEquals(2, count($cat_type_2_categories));
                $this->assertContains($cat2->id, array_keys($cat_type_2_categories));
                $this->assertContains($cat3->id, array_keys($cat_type_2_categories));

                $cat2_data = $cat_type_2_categories[$cat2->id];
                $this->assertEquals($cat2->name, $cat2_data['name']);
                $this->assertEquals($cat2->hex_color, $cat2_data['color']);
                $this->assertEquals(
                    round(
                        (($savingsTransaction2->amount * $actualCatPercentage2)
                        + ($creditTransaction1->amount * $actualCatPercentage2)) / 100,
                        2
                    ),
                    $cat2_data['value']
                );

                $cat2_transactions = $cat2_data['transactions'];
                $this->assertEquals(2, count($cat2_transactions));
                $expected_transactions = [
                    $savingsTransaction2,
                    $creditTransaction1
                ];
                foreach ($cat2_transactions as $transaction) {
                    $this->_checkTransaction($transaction, $expected_transactions, $cat2->id);
                }

                $cat3_data = $cat_type_2_categories[$cat3->id];
                $this->assertEquals($cat3->name, $cat3_data['name']);
                $this->assertEquals($cat3->hex_color, $cat3_data['color']);
                $this->assertEquals(
                    round(($savingsTransaction1->amount * $actualCatPercentage2) / 100, 2),
                    $cat3_data['value']
                );

                $cat3_transactions = $cat3_data['transactions'];
                $this->assertEquals(1, count($cat3_transactions));
                $expected_transactions = [ $savingsTransaction1 ];
                foreach ($cat3_transactions as $transaction) {
                    $this->_checkTransaction($transaction, $expected_transactions, $cat3->id);
                }

                return $page
                    //->dd('data.category_type_breakdowns')
                    ->component('Dashboard');
            }
        );
    }

    private function _checkTransaction(array $trans_to_check, array $expected_transactions, int $cat_id)
    {
        $current_trans = array_filter(
            $expected_transactions,
            function ($trans) use ($trans_to_check) {
                return $trans->id === $trans_to_check['id'];
            }
        );
        $this->_checkValues($trans_to_check, array_pop($current_trans), $cat_id);
    }
    private function _checkValues(array $trans_array, Transaction $transaction, int $cat_id)
    {
        $this->assertEquals($transaction->transaction_date, $trans_array['date']);
        $this->assertEquals($transaction->note, $trans_array['note']);
        $this->assertEquals($transaction->amount / 100, $trans_array['trans_total']);
        $this->_checkCatVal($trans_array['cat_value'], $transaction, $cat_id);
    }
    private function _checkCatVal(float $value_to_check, Transaction $transaction, int $cat_id)
    {
        $cat_val = null;
        $categories = $transaction->categories;
        foreach ($categories as $cat) {
            if ($cat->id !== $cat_id) {
                continue;
            }
            $percent = $cat->pivot->percentage;
            $cat_val = ($transaction->amount * ($percent / 10000)) / 100;
            break;
        }
        $this->assertEquals($cat_val, $value_to_check);
    }
}
