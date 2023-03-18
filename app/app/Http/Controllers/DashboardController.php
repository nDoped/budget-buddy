<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Transaction;

class DashboardController extends Controller
{
    public function dashboard(Request $request) : \Inertia\Response
    {
        $show_all = $request->show_all;
        $start = $request->start;
        $end = $request->end;

        if (! $start && ! $end && ! $show_all) {
            $start = date('Y-m-01');
            $end = date('Y-m-t');

        } else if ($show_all) {
            $start = $end = null;
        }

        $args = [
            'start' => $start,
            'end' => $end,
            'jsonify_categories' => false,
            'include_to_range' => true,
            'order_by' => 'asc',
        ];
        $trans_data = Transaction::fetch_transaction_data_for_current_user($args);
        $account_data = $this->_fetch_account_data();

        foreach ($trans_data['transactions_to_range'] as $trans) {
            $acct = $account_data[$trans['account_id']];
            if ($trans['asset']) {
                if ($acct['asset']) {
                    $account_data[$trans['account_id']]['pre_range_net_growth'] += $trans['amount_raw'];
                } else {
                    $account_data[$trans['account_id']]['pre_range_net_growth'] -= $trans['amount_raw'];
                }

            } else {
                if ($acct['asset']) {
                    $account_data[$trans['account_id']]['pre_range_net_growth'] -= $trans['amount_raw'];
                } else {
                    $account_data[$trans['account_id']]['pre_range_net_growth'] += $trans['amount_raw'];
                }
            }
        }


        $account_growth_line_data = [
            'daily_asset_growth' => [],
            'daily_debt_growth' => [],
        ];
        foreach ($trans_data['transactions_in_range'] as $trans) {
            $acct = $account_data[$trans['account_id']];
            // if this transaction is a credit
            if ($trans['asset']) {

                // to an asset account
                if ($acct['asset']) {
                    // counts as asset growth
                    $account_data[$trans['account_id']]['in_range_net_growth'] += $trans['amount_raw'];

                    // data for account daily balance graph
                    if (isset($account_data[$trans['account_id']]['daily_net_growths'][$trans['transaction_date']])) {
                        $account_data[$trans['account_id']]['daily_net_growths'][$trans['transaction_date']] += $trans['amount_raw'];

                    } else {
                        $account_data[$trans['account_id']]['daily_net_growths'][$trans['transaction_date']] = $trans['amount_raw'];
                    }

                    // daily asset accounts growth
                    if (isset($account_growth_line_data['daily_asset_growth'][$trans['transaction_date']])) {
                        $account_growth_line_data['daily_asset_growth'][$trans['transaction_date']] += $trans['amount_raw'];

                    } else {
                        $account_growth_line_data['daily_asset_growth'][$trans['transaction_date']] = $trans['amount_raw'];
                    }

                    // need to check if the daily_debt_growth array has a value for this transaction date
                    // and add one if not (so the labels line up in the line chart)
                    if (! isset($account_growth_line_data['daily_debt_growth'][$trans['transaction_date']])) {
                        $account_growth_line_data['daily_debt_growth'][$trans['transaction_date']] = 0;
                    }

                // to a debt account
                } else {
                    $account_data[$trans['account_id']]['in_range_net_growth'] -= $trans['amount_raw'];

                    // data for account daily balance graph
                    if (isset($account_data[$trans['account_id']]['daily_net_growths'][$trans['transaction_date']])) {
                        $account_data[$trans['account_id']]['daily_net_growths'][$trans['transaction_date']] -= $trans['amount_raw'];

                    } else {
                        $account_data[$trans['account_id']]['daily_net_growths'][$trans['transaction_date']] = -$trans['amount_raw'];
                    }

                    // counts as negative debt growth
                    if (isset($account_growth_line_data['daily_debt_growth'][$trans['transaction_date']])) {
                        $account_growth_line_data['daily_debt_growth'][$trans['transaction_date']] -= $trans['amount_raw'];

                    } else {
                        $account_growth_line_data['daily_debt_growth'][$trans['transaction_date']] = -$trans['amount_raw'];
                    }

                    // need to check if the daily_asset_growth array has a value for this transaction date
                    // and add one if not (so the labels line up in the line chart)
                    if (! isset($account_growth_line_data['daily_asset_growth'][$trans['transaction_date']])) {
                        $account_growth_line_data['daily_asset_growth'][$trans['transaction_date']] = 0;
                    }
                }

            // else this is a debit
            } else {


                // to an asset account
                if ($acct['asset']) {
                    $account_data[$trans['account_id']]['in_range_net_growth'] -= $trans['amount_raw'];

                    // data for account daily balance graph
                    if (isset($account_data[$trans['account_id']]['daily_net_growths'][$trans['transaction_date']])) {
                        $account_data[$trans['account_id']]['daily_net_growths'][$trans['transaction_date']] -= $trans['amount_raw'];

                    } else {
                        $account_data[$trans['account_id']]['daily_net_growths'][$trans['transaction_date']] = -$trans['amount_raw'];
                    }

                    // counts as negative asset growth
                    if (isset($account_growth_line_data['daily_asset_growth'][$trans['transaction_date']])) {
                        $account_growth_line_data['daily_asset_growth'][$trans['transaction_date']] -= $trans['amount_raw'];
                    } else {
                        $account_growth_line_data['daily_asset_growth'][$trans['transaction_date']] = -$trans['amount_raw'];
                    }

                    // need to check if the daily_debt_growth array has a value for this transaction date
                    // and add one if not (so the labels line up in the line chart)
                    if (! isset($account_growth_line_data['daily_debt_growth'][$trans['transaction_date']])) {
                        $account_growth_line_data['daily_debt_growth'][$trans['transaction_date']] = 0;
                    }

                // else it's a debit to a debt account
                } else {
                    $account_data[$trans['account_id']]['in_range_net_growth'] += $trans['amount_raw'];

                    // data for account daily balance graph
                    if (isset($account_data[$trans['account_id']]['daily_net_growths'][$trans['transaction_date']])) {
                        $account_data[$trans['account_id']]['daily_net_growths'][$trans['transaction_date']] += $trans['amount_raw'];

                    } else {
                        $account_data[$trans['account_id']]['daily_net_growths'][$trans['transaction_date']] = $trans['amount_raw'];
                    }
                    // counts as debt growth
                    if (isset($account_growth_line_data['daily_debt_growth'][$trans['transaction_date']])) {
                        $account_growth_line_data['daily_debt_growth'][$trans['transaction_date']] += $trans['amount_raw'];

                    } else {
                        $account_growth_line_data['daily_debt_growth'][$trans['transaction_date']] = $trans['amount_raw'];
                    }

                    // need to check if the daily_asset_growth array has a value for this transaction date
                    // and add one if not (so the labels line up in the line chart)
                    if (! isset($account_growth_line_data['daily_asset_growth'][$trans['transaction_date']])) {
                        $account_growth_line_data['daily_asset_growth'][$trans['transaction_date']] = 0;
                    }
                }
            }
        }

        $daily_economic_growth = [];
        foreach ($account_growth_line_data as $group => $data) {
            foreach ($data as $date => $raw_val) {
                if (! isset($daily_economic_growth[$date])) {
                    if ($group === 'daily_asset_growth') {
                        $daily_economic_growth[$date] = $raw_val;
                    } else {
                        $daily_economic_growth[$date] = -$raw_val;
                    }

                } else {
                    if ($group === 'daily_asset_growth') {
                        $daily_economic_growth[$date] += $raw_val;
                    } else {
                        $daily_economic_growth[$date] -= $raw_val;
                    }
                }

                $account_growth_line_data[$group][$date] = $raw_val / 100;
            }
        }

        $total_economic_growth = [];
        $running_total = 0;
        foreach ($daily_economic_growth as $date => $value) {
            $daily_economic_growth[$date] = $value / 100;
            $running_total += $value;
            $total_economic_growth[$date] = $running_total;
        }
        $account_growth_line_data['daily_economic_growth'] = $daily_economic_growth;
        $account_growth_line_data['total_economic_growth'] = array_map(fn($val): float => $val / 100, $total_economic_growth);

        $asset_accts = [];
        $debt_accts = [];
        $total_net_growth = [
            'assets' => [
                'name' => 'Totals',
                'start_balance' => 0,
                'in_range_net_growth' => 0,
                'end_balance' => 0,
                'asset' => true,
            ],
            'debts' => [
                'name' => 'Totals',
                'start_balance' => 0,
                'in_range_net_growth' => 0,
                'end_balance' => 0,
                'asset' => false,

            ],
        ];
        $total_eco_growth = 0;
        foreach ($account_data as $acct) {
            $start_balance_raw = $acct['init_balance_raw'] + $acct['pre_range_net_growth'];
            $acct['start_balance'] = $start_balance_raw / 100;

            $in_range_net_growth_raw = $acct['in_range_net_growth'];
            $acct['in_range_net_growth'] = $in_range_net_growth_raw / 100;

            $end_balance_raw = $start_balance_raw + $in_range_net_growth_raw;
            $acct['end_balance'] = $end_balance_raw / 100;

            $acct['overdrawn_or_overpaid'] = false;

            $daily_balance_line_graph_data = [];
            $running_balance = $start_balance_raw;
            foreach ($acct['daily_net_growths'] as $trans_date => $growth) {
                $running_balance += $growth;
                if(isset($daily_balance_line_graph_data[$trans_date])) {
                    $daily_balance_line_graph_data[$trans_date] += $running_balance;
                } else {
                    $daily_balance_line_graph_data[$trans_date] = $running_balance;
                }
                if ($running_balance <= 0) {
                    $acct['overdrawn_or_overpaid'] = true;
                }
            }

            $acct['daily_balance_line_graph_data'] = array_merge(
                [ 'Start' => $acct['start_balance'] ],
                array_map(fn($value): float => $value / 100, $daily_balance_line_graph_data)
            );
            if ($acct['asset']) {
                $asset_accts[] = $acct;
            } else {
                $debt_accts[] = $acct;
            }
            $group = ($acct['asset']) ? 'assets' : 'debts';
            $total_net_growth[$group]['start_balance'] += $start_balance_raw;
            $total_net_growth[$group]['in_range_net_growth'] += $in_range_net_growth_raw;
            $total_net_growth[$group]['end_balance'] += $end_balance_raw;
        }
        $total_eco_growth = ($total_net_growth['assets']['in_range_net_growth'] - $total_net_growth['debts']['in_range_net_growth']) / 100;
        foreach ($total_net_growth as $group => $data) {
            foreach ($data as $key => $val) {
                if ($key === 'name') {
                    continue;
                }
                $total_net_growth[$group][$key] = $val / 100;
            }
        }

        $asset_accts[] = $total_net_growth['assets'];
        $debt_accts[] = $total_net_growth['debts'];
        $data['start'] = $start;
        $data['end'] = $end;
        $data['extra_expense_breakdown'] = $trans_data['extra_expense_breakdown'];
        $data['recurring_expense_breakdown'] = $trans_data['recurring_expense_breakdown'];
        $data['total_extra_expenses'] = $trans_data['total_extra_expenses'];
        $data['total_recurring_expenses'] = $trans_data['total_recurring_expenses'];
        $data['total_economic_growth'] = $total_eco_growth;
        $data['asset_accounts'] = $asset_accts;
        $data['debt_accounts'] = $debt_accts;
        $data['account_growth_line_data']['daily_economic_growth'] = $account_growth_line_data['daily_economic_growth'];
        $data['account_growth_line_data']['total_economic_growth'] = $account_growth_line_data['total_economic_growth'];
        return Inertia::render('Dashboard', [
            'data' => $data
        ]);
    }

    /*
     * @return array
     */
    private function _fetch_account_data() : array
    {
        $ret = [];
        $current_user = Auth::user();
        $accounts = DB::table('accounts')
            ->join('account_types', 'accounts.type_id', '=', 'account_types.id')
            ->where('accounts.user_id', '=', $current_user->id)
            ->select('accounts.*', 'account_types.asset')
            ->get();

        foreach ($accounts as $acct) {
            $ret[$acct->id] = [
                'name' => $acct->name,
                'init_balance' => $acct->initial_balance / 100,
                'init_balance_raw' => $acct->initial_balance,
                'asset' => $acct->asset,
                'url' => $acct->url,
                'in_range_net_growth' => 0,
                'pre_range_net_growth' => 0,
                'daily_net_growths' => [],
                'end_balance' => 0
            ];
        }
        return $ret;
    }
}
