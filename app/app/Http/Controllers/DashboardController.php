<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Account;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
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
            'filter_for_expense_breakdown' => true
        ];
        $trans_data = Transaction::fetch_transaction_data_for_current_user($args);
        $account_data = $this->_fetch_account_data($start, $end);

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


        // todo, fix this
        $account_growth_line_data = [
            'daily_asset_growth' => [], // net sum j
            'total_asset_growth' => [],
            'daily_debt_growth' => [],
            'total_debt_growth' => [],
            'total_economic_growth' => [],
        ];
        foreach ($trans_data['transactions_in_range'] as $trans) {
            /*
            Log::info([
                'app/Http/Controllers/DashboardController.php:51 key' => $trans,
            ]);
             */

            $acct = $account_data[$trans['account_id']];
            // if this is a credit
            if ($trans['asset']) {
                // to an asset account
                if ($acct['asset']) {
                    // accounts for asset growth
                    $account_data[$trans['account_id']]['in_range_net_growth'] += $trans['amount_raw'];

                    // daily asset growth
                    if (isset($account_growth_line_data['daily_asset_growth'][$trans['transaction_date']])) {
                        $account_growth_line_data['daily_asset_growth'][$trans['transaction_date']] += $trans['amount_raw'];

                    } else {
                        $account_growth_line_data['daily_asset_growth'][$trans['transaction_date']] = $trans['amount_raw'];
                    }

                // to a debt account
                } else {
                    $account_data[$trans['account_id']]['in_range_net_growth'] -= $trans['amount_raw'];
                    // accounts for negative debt growth
                    if (isset($account_growth_line_data['daily_debt_growth'][$trans['transaction_date']])) {
                        $account_growth_line_data['daily_debt_growth'][$trans['transaction_date']] -= $trans['amount_raw'];

                    } else {
                        $account_growth_line_data['daily_debt_growth'][$trans['transaction_date']] = -$trans['amount_raw'];
                    }
                }

            // else this is a debit
            } else {
                // to an asset account
                if ($acct['asset']) {
                    $account_data[$trans['account_id']]['in_range_net_growth'] -= $trans['amount_raw'];
                    // accounts for asset shrinkage
                    if (isset($account_growth_line_data['daily_asset_growth'][$trans['transaction_date']])) {
                        $account_growth_line_data['daily_asset_growth'][$trans['transaction_date']] -= $trans['amount_raw'];

                    } else {
                        $account_growth_line_data['daily_asset_growth'][$trans['transaction_date']] = -$trans['amount_raw'];
                    }

                // else it's a debit to a debt account
                } else {
                    $account_data[$trans['account_id']]['in_range_net_growth'] += $trans['amount_raw'];
                    // accounts for debt growth
                    if (isset($account_growth_line_data['daily_debt_growth'][$trans['transaction_date']])) {
                        $account_growth_line_data['daily_debt_growth'][$trans['transaction_date']] += $trans['amount_raw'];

                    } else {
                        $account_growth_line_data['daily_debt_growth'][$trans['transaction_date']] = $trans['amount_raw'];
                    }
                }
            }
        }

        $asset_accts = [];
        $debt_accts = [];
        $total_net_growth = [
            'assets' => [
                'name' => 'Totals',
                'start_balance' => 0,
                'in_range_net_growth' => 0,
                'end_balance' => 0,
            ],
            'debts' => [
                'name' => 'Totals',
                'start_balance' => 0,
                'in_range_net_growth' => 0,
                'end_balance' => 0,

            ],
        ];
        $total_eco_growth = 0;
        foreach ($account_data as $id => $acct) {
            $start_balance_raw = $acct['init_balance_raw'] + $acct['pre_range_net_growth'];
            $acct['start_balance'] = $start_balance_raw / 100;

            $in_range_net_growth_raw = $acct['in_range_net_growth'];
            $acct['in_range_net_growth'] = $in_range_net_growth_raw / 100;

            $end_balance_raw = $start_balance_raw + $in_range_net_growth_raw;
            $acct['end_balance'] = $end_balance_raw / 100;
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
        $data['category_totals'] = $trans_data['category_totals'];
        $data['total_economic_growth'] = $total_eco_growth;
        $data['asset_accounts'] = $asset_accts;
        $data['debt_accounts'] = $debt_accts;
        $data['account_growth_line_data'] = $account_growth_line_data;
        Log::info([
            'app/Http/Controllers/DashboardController.php:51 line data' => $account_growth_line_data
        ]);
        return Inertia::render('Dashboard', [
            'data' => $data
        ]);
    }

    private function _fetch_account_data($transactions)
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
                'end_balance' => 0
            ];
        }

        //Log::info($ret);
        return $ret;
    }
}
