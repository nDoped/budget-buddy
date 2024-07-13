<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Database\Seeders\FeatureTestSeeder;

class DashboardTest extends TestCase
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

    #[Group('dashboard')]
    public function test_dashboard(): void
    {
        $parms = [
            'start' => '2024-06-01',
            'end' => '2024-06-30',
        ];
        $query = http_build_query($parms);
        $this->get(
            '/dashboard?' . $query,
        )->assertInertia(
            function (Assert $page) {
                $data = $page->toArray()['props']['data'];
                $this->assertEquals(7, count($data));
                $this->assertEquals(
                    ($this->savingsTransaction1->amount - $this->savingsTransaction2->amount - $this->creditTransaction1->amount) / 100,
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
                    -$this->creditTransaction1->amount / 100,
                    $daily_economic_growth['2024-06-01']
                );
                $this->assertEquals(
                    ($this->savingsTransaction1->amount - $this->savingsTransaction2->amount) / 100,
                    $daily_economic_growth['2024-06-14']
                );
                $total_economic_growth = $account_growth_line_data['total_economic_growth'];
                $this->assertEquals(
                    -$this->creditTransaction1->amount / 100,
                    $total_economic_growth['2024-06-01']
                );
                $this->assertEquals(
                    ($this->savingsTransaction1->amount  - $this->savingsTransaction2->amount - $this->creditTransaction1->amount) / 100,
                    $total_economic_growth['2024-06-14']
                );

                /*
                 * debt_accounts
                 */
                $this->assertEquals(2, count($data['debt_accounts']));
                $credit_card = $data['debt_accounts'][0];
                $this->isFalse($credit_card['asset']);
                $this->assertEquals($this->creditCardAccount->name, $credit_card['name']);
                $this->assertEquals($this->creditTransaction1->amount / 100, $credit_card['in_range_net_growth']);
                // this is a raw value, i don't think the front end uses it, but it's included so lets check it */
                $this->assertEquals($this->creditTransaction2->amount, $credit_card['pre_range_net_growth']);
                $this->assertFalse($credit_card['overdrawn_or_overpaid']);
                $this->assertEquals($this->creditTransaction2->amount / 100, $credit_card['start_balance']);
                $this->assertEquals(($this->creditTransaction2->amount + $this->creditTransaction1->amount) / 100, $credit_card['end_balance']);

                $cc_daily_balance_line_graph_data = $credit_card['daily_balance_line_graph_data'];
                $this->assertEquals($this->creditTransaction2->amount / 100, $cc_daily_balance_line_graph_data['Start']);
                $this->assertEquals(($this->creditTransaction2->amount + $this->creditTransaction1->amount) / 100, $cc_daily_balance_line_graph_data['2024-06-01']);
                $cc_daily_net_growths = $credit_card['daily_net_growths'];
                $this->assertEquals(1, count($cc_daily_net_growths));
                // this is raw..idk if it's actually used on the front end
                $this->assertEquals($this->creditTransaction1->amount, $cc_daily_net_growths['2024-06-01']);
                $debt_accounts_totals = $data['debt_accounts'][1];
                $this->assertEquals(6, count($debt_accounts_totals));
                $this->assertEquals('Totals', $debt_accounts_totals['name']);
                $this->assertEquals($this->creditTransaction2->amount / 100, $debt_accounts_totals['start_balance']);
                $this->assertEquals($this->creditTransaction1->amount / 100, $debt_accounts_totals['in_range_net_growth']);
                $this->assertEquals(($this->creditTransaction2->amount + $this->creditTransaction1->amount) / 100, $debt_accounts_totals['end_balance']);
                $this->isFalse($debt_accounts_totals['asset']);

                /*
                 * asset_accounts
                 */
                $asset_accounts = $data['asset_accounts'];
                $this->assertEquals(2, count($asset_accounts));
                $savings = $asset_accounts[0];
                $this->isTrue($savings['asset']);
                $this->assertEquals($this->savingsAccount->name, $savings['name']);
                $this->assertEquals(($this->savingsTransaction1->amount - $this->savingsTransaction2->amount) / 100, $savings['in_range_net_growth']);
                // this is a raw value, i don't think the front end uses it, but it's included so lets check it
                $this->assertEquals($this->savingsTransaction0->amount, $savings['pre_range_net_growth']);
                $this->assertFalse($savings['overdrawn_or_overpaid']);
                $this->assertEquals($this->savingsTransaction0->amount / 100, $savings['start_balance']);
                $this->assertEquals(($this->savingsTransaction0->amount + $this->savingsTransaction1->amount - $this->savingsTransaction2->amount) / 100, $savings['end_balance']);
                $savings_daily_balance_line_graph_data = $savings['daily_balance_line_graph_data'];
                $this->assertEquals($this->savingsTransaction0->amount / 100, $savings_daily_balance_line_graph_data['Start']);
                $this->assertEquals(($this->savingsTransaction0->amount + $this->savingsTransaction1->amount - $this->savingsTransaction2->amount) / 100, $savings_daily_balance_line_graph_data['2024-06-14']);
                $savings_daily_net_growths = $savings['daily_net_growths'];
                $this->assertEquals(1, count($savings_daily_net_growths));
                // this is raw..idk if it's actually used on the front end
                $this->assertEquals($this->savingsTransaction1->amount - $this->savingsTransaction2->amount, $savings_daily_net_growths['2024-06-14']);

                /*
                 * category_type_breakdowns
                 */
                $category_type_breakdowns = $data['category_type_breakdowns'];
                $this->assertEquals(2, count($category_type_breakdowns));

                // CatType1
                $cat_type1 = $category_type_breakdowns[$this->catType1->id];
                $this->assertEquals($this->catType1->name, $cat_type1['name']);
                $this->assertEquals($this->catType1->hex_color, $cat_type1['hex_color']);
                $this->assertEquals(
                    round(
                        (($this->savingsTransaction1->amount * $this->actualCatPercentage1)
                        + ($this->savingsTransaction2->amount * $this->actualCatPercentage1)
                        + ($this->creditTransaction1->amount * $this->actualCatPercentage1)) / 100,
                        2
                    ),
                    $cat_type1['total']
                );

                $cat_type_1_categories = $cat_type1['data'];
                $this->assertEquals(1, count($cat_type_1_categories));

                $cat1_data = $cat_type_1_categories[$this->cat1->id];
                $this->assertEquals($this->cat1->name, $cat1_data['name']);
                $this->assertEquals(
                    round(
                        (($this->savingsTransaction1->amount * $this->actualCatPercentage1)
                        + ($this->savingsTransaction2->amount * $this->actualCatPercentage1)
                        + ($this->creditTransaction1->amount * $this->actualCatPercentage1)) / 100,
                        2
                    ),
                    $cat1_data['value']
                );
                $this->assertEquals($this->cat1->hex_color, $cat1_data['hex_color']);

                $cat1_transactions = $cat1_data['transactions'];
                $this->assertEquals(3, count($cat1_transactions));
                $expected_transactions = [
                    $this->savingsTransaction1,
                    $this->savingsTransaction2,
                    $this->creditTransaction1
                ];
                foreach ($cat1_transactions as $transaction) {
                    $this->_checkTransaction($transaction, $expected_transactions, $this->cat1->id);
                }

                // CatType2
                $cat_type2 = $category_type_breakdowns[$this->catType2->id];
                $this->assertEquals($this->catType2->name, $cat_type2['name']);
                $this->assertEquals($this->catType2->hex_color, $cat_type2['hex_color']);
                $this->assertEquals(
                    round(
                        (($this->savingsTransaction1->amount * $this->actualCatPercentage2)
                        + ($this->savingsTransaction2->amount * $this->actualCatPercentage2)
                        + ($this->creditTransaction1->amount * $this->actualCatPercentage2)) / 100,
                        2
                    ),
                    $cat_type2['total']
                );

                $cat_type_2_categories = $cat_type2['data'];
                $this->assertEquals(2, count($cat_type_2_categories));
                $this->assertContains($this->cat2->id, array_keys($cat_type_2_categories));
                $this->assertContains($this->cat3->id, array_keys($cat_type_2_categories));

                $cat2_data = $cat_type_2_categories[$this->cat2->id];
                $this->assertEquals($this->cat2->name, $cat2_data['name']);
                $this->assertEquals($this->cat2->hex_color, $cat2_data['hex_color']);
                $this->assertEquals(
                    round(
                        (($this->savingsTransaction2->amount * $this->actualCatPercentage2)
                        + ($this->creditTransaction1->amount * $this->actualCatPercentage2)) / 100,
                        2
                    ),
                    $cat2_data['value']
                );

                $cat2_transactions = $cat2_data['transactions'];
                $this->assertEquals(2, count($cat2_transactions));
                $expected_transactions = [
                    $this->savingsTransaction2,
                    $this->creditTransaction1
                ];
                foreach ($cat2_transactions as $transaction) {
                    $this->_checkTransaction($transaction, $expected_transactions, $this->cat2->id);
                }

                $cat3_data = $cat_type_2_categories[$this->cat3->id];
                $this->assertEquals($this->cat3->name, $cat3_data['name']);
                $this->assertEquals($this->cat3->hex_color, $cat3_data['hex_color']);
                $this->assertEquals(
                    round(($this->savingsTransaction1->amount * $this->actualCatPercentage2) / 100, 2),
                    $cat3_data['value']
                );

                $cat3_transactions = $cat3_data['transactions'];
                $this->assertEquals(1, count($cat3_transactions));
                $expected_transactions = [ $this->savingsTransaction1 ];
                foreach ($cat3_transactions as $transaction) {
                    $this->_checkTransaction($transaction, $expected_transactions, $this->cat3->id);
                }

                return $page->component('Dashboard');
            }
        );
    }

    public function test_dashboard_all_transactions(): void
    {
        $this->creditTransaction2->credit = true;
        $this->creditTransaction2->save();
        $params = [
            'show_all' => true
        ];
        $query = http_build_query($params);
        $this->get(
            '/dashboard?' . $query
        )->assertInertia(
            function (Assert $page) {
                $data = $page->toArray()['props']['data'];
                $this->assertEquals(7, count($data));
                $expected_eco_growth = $this->savingsTransaction0->amount
                    + $this->savingsTransaction1->amount
                    - $this->savingsTransaction2->amount
                    - $this->creditTransaction1->amount
                    + $this->creditTransaction2->amount;
                $this->assertEquals(
                    $expected_eco_growth / 100,
                    $data['total_economic_growth']
                );

                /*
                 * account_growth_line_data
                 */
                $this->assertEquals(2, count($data['account_growth_line_data']));
                $account_growth_line_data = $data['account_growth_line_data'];
                $daily_economic_growth = $account_growth_line_data['daily_economic_growth'];
                $this->assertEquals(4, count($daily_economic_growth));
                $this->assertEquals(
                    $this->creditTransaction2->amount / 100,
                    $daily_economic_growth['2024-05-01']
                );
                $this->assertEquals(
                    $this->savingsTransaction0->amount / 100,
                    $daily_economic_growth['2024-05-14']
                );
                $this->assertEquals(
                    -$this->creditTransaction1->amount / 100,
                    $daily_economic_growth['2024-06-01']
                );
                $this->assertEquals(
                    ($this->savingsTransaction1->amount - $this->savingsTransaction2->amount) / 100,
                    $daily_economic_growth['2024-06-14']
                );
                $total_economic_growth = $account_growth_line_data['total_economic_growth'];
                $total_eco_growth_sum = $this->creditTransaction2->amount;
                $this->assertEquals(
                    $total_eco_growth_sum / 100,
                    $total_economic_growth['2024-05-01']
                );
                $total_eco_growth_sum += $this->savingsTransaction0->amount;
                $this->assertEquals(
                    $total_eco_growth_sum / 100,
                    $total_economic_growth['2024-05-14']
                );
                $total_eco_growth_sum -= $this->creditTransaction1->amount;
                $this->assertEquals(
                    $total_eco_growth_sum / 100,
                    $total_economic_growth['2024-06-01']
                );
                $total_eco_growth_sum += $this->savingsTransaction1->amount - $this->savingsTransaction2->amount;
                $this->assertEquals(
                    $total_eco_growth_sum / 100,
                    $total_economic_growth['2024-06-14']
                );

                /*
                 * debt_accounts
                 */
                $this->assertEquals(2, count($data['debt_accounts']));
                $credit_card = $data['debt_accounts'][0];
                $this->isFalse($credit_card['asset']);
                $this->assertEquals($this->creditCardAccount->name, $credit_card['name']);
                $this->assertEquals((-$this->creditTransaction2->amount + $this->creditTransaction1->amount) / 100, $credit_card['in_range_net_growth']);
                // this is a raw value, i don't think the front end uses it, but it's included so lets check it */
                $this->assertEquals(0, $credit_card['pre_range_net_growth']);
                $this->assertTrue($credit_card['overdrawn_or_overpaid']);
                $this->assertEquals(0, $credit_card['start_balance']);
                $end_bal = $this->creditTransaction1->amount
                    - $this->creditTransaction2->amount;
                $this->assertEquals($end_bal / 100, $credit_card['end_balance']);

                $cc_daily_balance_line_graph_data = $credit_card['daily_balance_line_graph_data'];
                $this->assertEquals(0, $cc_daily_balance_line_graph_data['Start']);
                $this->assertEquals(
                    ($this->creditTransaction1->amount - $this->creditTransaction2->amount) / 100,
                    $cc_daily_balance_line_graph_data['2024-06-01']
                );
                $cc_daily_net_growths = $credit_card['daily_net_growths'];
                $this->assertEquals(2, count($cc_daily_net_growths));
                // this is raw..idk if it's actually used on the front end
                $this->assertEquals(
                    $this->creditTransaction1->amount,
                    $cc_daily_net_growths['2024-06-01']
                );
                $debt_accounts_totals = $data['debt_accounts'][1];
                $this->assertEquals(6, count($debt_accounts_totals));
                $this->assertEquals('Totals', $debt_accounts_totals['name']);
                $this->assertEquals(0, $debt_accounts_totals['start_balance']);
                $this->assertEquals(
                    ($this->creditTransaction1->amount - $this->creditTransaction2->amount) / 100,
                    $debt_accounts_totals['in_range_net_growth']
                );
                $this->assertEquals(
                    ($this->creditTransaction1->amount - $this->creditTransaction2->amount) / 100,
                    $debt_accounts_totals['end_balance']
                );
                $this->isFalse($debt_accounts_totals['asset']);

                /*
                 * asset_accounts
                 */
                $asset_accounts = $data['asset_accounts'];
                $this->assertEquals(2, count($asset_accounts));
                $savings = $asset_accounts[0];
                $this->isTrue($savings['asset']);
                $this->assertEquals($this->savingsAccount->name, $savings['name']);
                $this->assertEquals(
                    ($this->savingsTransaction0->amount
                    + $this->savingsTransaction1->amount
                    - $this->savingsTransaction2->amount) / 100,
                    $savings['in_range_net_growth']
                );
                // this is a raw value, i don't think the front end uses it, but it's included so lets check it
                $this->assertEquals(0, $savings['pre_range_net_growth']);
                $this->assertFalse($savings['overdrawn_or_overpaid']);
                $this->assertEquals(0, $savings['start_balance']);
                $this->assertEquals(($this->savingsTransaction0->amount + $this->savingsTransaction1->amount - $this->savingsTransaction2->amount) / 100, $savings['end_balance']);
                $savings_daily_balance_line_graph_data = $savings['daily_balance_line_graph_data'];
                $this->assertEquals(3, count($savings_daily_balance_line_graph_data));
                $this->assertEquals(0, $savings_daily_balance_line_graph_data['Start']);
                $this->assertEquals(
                    $this->savingsTransaction0->amount / 100,
                    $savings_daily_balance_line_graph_data['2024-05-14']
                );
                $this->assertEquals(
                    ($this->savingsTransaction0->amount
                    + $this->savingsTransaction1->amount
                    - $this->savingsTransaction2->amount) / 100,
                    $savings_daily_balance_line_graph_data['2024-06-14']
                );
                $savings_daily_net_growths = $savings['daily_net_growths'];
                $this->assertEquals(2, count($savings_daily_net_growths));
                // this is raw..idk if it's actually used on the front end
                $this->assertEquals(
                    $this->savingsTransaction0->amount,
                    $savings_daily_net_growths['2024-05-14']
                );
                $this->assertEquals(
                    $this->savingsTransaction1->amount - $this->savingsTransaction2->amount,
                    $savings_daily_net_growths['2024-06-14']
                );

                /*
                 * category_type_breakdowns
                 */
                $category_type_breakdowns = $data['category_type_breakdowns'];
                $this->assertEquals(2, count($category_type_breakdowns));

                // CatType1
                $cat_type1 = $category_type_breakdowns[$this->catType1->id];
                $this->assertEquals($this->catType1->name, $cat_type1['name']);
                $this->assertEquals($this->catType1->hex_color, $cat_type1['hex_color']);
                $this->assertEquals(
                    round(
                        ($this->savingsTransaction0->amount
                        + ($this->savingsTransaction1->amount * $this->actualCatPercentage1)
                        + ($this->savingsTransaction2->amount * $this->actualCatPercentage1)
                        + ($this->creditTransaction2->amount * $this->actualCatPercentage1)
                        + ($this->creditTransaction1->amount * $this->actualCatPercentage1)) / 100,
                        2
                    ),
                    $cat_type1['total']
                );

                $cat_type_1_categories = $cat_type1['data'];
                $this->assertEquals(1, count($cat_type_1_categories));

                $cat1_data = $cat_type_1_categories[$this->cat1->id];
                $this->assertEquals($this->cat1->name, $cat1_data['name']);
                $this->assertEquals(
                    round(
                        ($this->savingsTransaction0->amount
                        + ($this->savingsTransaction1->amount * $this->actualCatPercentage1)
                        + ($this->savingsTransaction2->amount * $this->actualCatPercentage1)
                        + ($this->creditTransaction1->amount * $this->actualCatPercentage1)
                        + ($this->creditTransaction2->amount * $this->actualCatPercentage1)) / 100,
                        2
                    ),
                    $cat1_data['value']
                );
                $this->assertEquals($this->cat1->hex_color, $cat1_data['hex_color']);

                $cat1_transactions = $cat1_data['transactions'];
                $this->assertEquals(5, count($cat1_transactions));
                $expected_transactions = [
                    $this->savingsTransaction0,
                    $this->savingsTransaction1,
                    $this->savingsTransaction2,
                    $this->creditTransaction1,
                    $this->creditTransaction2
                ];
                foreach ($cat1_transactions as $transaction) {
                    $this->_checkTransaction($transaction, $expected_transactions, $this->cat1->id);
                }

                // CatType2
                $cat_type2 = $category_type_breakdowns[$this->catType2->id];
                $this->assertEquals($this->catType2->name, $cat_type2['name']);
                $this->assertEquals($this->catType2->hex_color, $cat_type2['hex_color']);
                $this->assertEquals(
                    round(
                        (($this->savingsTransaction1->amount * $this->actualCatPercentage2)
                        + ($this->savingsTransaction2->amount * $this->actualCatPercentage2)
                        + ($this->creditTransaction1->amount * $this->actualCatPercentage2)
                        + ($this->creditTransaction2->amount * $this->actualCatPercentage2)) / 100,
                        2
                    ),
                    $cat_type2['total']
                );

                $cat_type_2_categories = $cat_type2['data'];
                $this->assertEquals(2, count($cat_type_2_categories));
                $this->assertContains($this->cat2->id, array_keys($cat_type_2_categories));
                $this->assertContains($this->cat3->id, array_keys($cat_type_2_categories));

                $cat2_data = $cat_type_2_categories[$this->cat2->id];
                $this->assertEquals($this->cat2->name, $cat2_data['name']);
                $this->assertEquals($this->cat2->hex_color, $cat2_data['hex_color']);
                $this->assertEquals(
                    round(
                        (($this->savingsTransaction2->amount * $this->actualCatPercentage2)
                        + ($this->creditTransaction1->amount * $this->actualCatPercentage2)
                        + ($this->creditTransaction2->amount * $this->actualCatPercentage2)) / 100,
                        2
                    ),
                    $cat2_data['value']
                );

                $cat2_transactions = $cat2_data['transactions'];
                $this->assertEquals(3, count($cat2_transactions));
                $expected_transactions = [
                    $this->savingsTransaction2,
                    $this->creditTransaction1,
                    $this->creditTransaction2
                ];
                foreach ($cat2_transactions as $transaction) {
                    $this->_checkTransaction($transaction, $expected_transactions, $this->cat2->id);
                }

                $cat3_data = $cat_type_2_categories[$this->cat3->id];
                $this->assertEquals($this->cat3->name, $cat3_data['name']);
                $this->assertEquals($this->cat3->hex_color, $cat3_data['hex_color']);
                $this->assertEquals(
                    round(($this->savingsTransaction1->amount * $this->actualCatPercentage2) / 100, 2),
                    $cat3_data['value']
                );

                $cat3_transactions = $cat3_data['transactions'];
                $this->assertEquals(1, count($cat3_transactions));
                $expected_transactions = [ $this->savingsTransaction1 ];
                foreach ($cat3_transactions as $transaction) {
                    $this->_checkTransaction($transaction, $expected_transactions, $this->cat3->id);
                }

                return $page->component('Dashboard');
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
