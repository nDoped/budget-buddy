<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    const BALANCE_LINE_GRAPH_START_LABEL = 'Initial Balance';
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

        /**
         * @var \App\Models\User $user
         */
        $user = Auth::user();
        $trans_data = $user->fetchTransactionData(
            start: $start,
            end: $end,
            orderBy: 'asc',
            includeToRange: true,
            includeCategoryTypeBreakdowns: true
        );
        $account_data = $user->fetchAccountData();

        foreach ($trans_data['transactions_to_range'] as $trans) {
            $acct =& $account_data[$trans['account_id']];
            $acct['pre_range_net_growth'] ??= 0;
            $acct['in_range_net_growth'] ??= 0;
            $acct['expand'] ??= true;
            $acct['daily_net_growths'] ??= [];
            $acct['end_balance'] ??= 0;
            if ($trans['asset']) {
                if ($acct['asset']) {
                    $acct['pre_range_net_growth'] += $trans['amount_raw'];
                } else {
                    $acct['pre_range_net_growth'] -= $trans['amount_raw'];
                }

            } else {
                if ($acct['asset']) {
                    $acct['pre_range_net_growth'] -= $trans['amount_raw'];
                } else {
                    $acct['pre_range_net_growth'] += $trans['amount_raw'];
                }
            }
        }

        $account_growth_line_data = [
            'daily_asset_growth' => [],
            'daily_debt_growth' => [],
        ];
        $recurringTransactions = [];
        foreach ($trans_data['transactions_in_range'] as $trans) {
            $acct =& $account_data[$trans['account_id']];
            $acct['pre_range_net_growth'] ??= 0;
            $acct['in_range_net_growth'] ??= 0;
            $acct['expand'] ??= true;
            $acct['daily_net_growths'] ??= [];
            $acct['end_balance'] ??= 0;
            if ($trans['parent_id']) {
                $recurringTransactions[] = [
                    'id' => $trans['id'],
                    'parent_id' => $trans['parent_id'],
                    'buddy_id' => $trans['buddy_id'],
                    'transaction_date' => $trans['transaction_date'],
                    'amount_raw' => $trans['amount_raw'],
                    'asset' => $trans['asset'],
                    'account_id' => $trans['account_id'],
                ];
            }
            // if this transaction is a credit
            if ($trans['asset']) {
                // to an asset account
                if ($acct['asset']) {
                    // counts as asset growth
                    $acct['in_range_net_growth'] += $trans['amount_raw'];

                    // data for account daily balance graph
                    if (isset($acct['daily_net_growths'][$trans['transaction_date']])) {
                        $acct['daily_net_growths'][$trans['transaction_date']] += $trans['amount_raw'];

                    } else {
                        $acct['daily_net_growths'][$trans['transaction_date']] = $trans['amount_raw'];
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
                    $acct['in_range_net_growth'] -= $trans['amount_raw'];

                    // data for account daily balance graph
                    if (isset($acct['daily_net_growths'][$trans['transaction_date']])) {
                        $acct['daily_net_growths'][$trans['transaction_date']] -= $trans['amount_raw'];

                    } else {
                        $acct['daily_net_growths'][$trans['transaction_date']] = -$trans['amount_raw'];
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
                    $acct['in_range_net_growth'] -= $trans['amount_raw'];

                    // data for account daily balance graph
                    if (isset($acct['daily_net_growths'][$trans['transaction_date']])) {
                        $acct['daily_net_growths'][$trans['transaction_date']] -= $trans['amount_raw'];

                    } else {
                        $acct['daily_net_growths'][$trans['transaction_date']] = -$trans['amount_raw'];
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
                    $acct['in_range_net_growth'] += $trans['amount_raw'];

                    // data for account daily balance graph
                    if (isset($acct['daily_net_growths'][$trans['transaction_date']])) {
                        $acct['daily_net_growths'][$trans['transaction_date']] += $trans['amount_raw'];

                    } else {
                        $acct['daily_net_growths'][$trans['transaction_date']] = $trans['amount_raw'];
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

        $totalRecurringDebits = 0;
        $totalRecurringCredits = 0;
        foreach ($recurringTransactions as &$trans) {
            if ($trans['buddy_id']) {
                continue;
            }
            if ($trans['asset']) {
                $totalRecurringCredits += $trans['amount_raw'];
            } else {
                $totalRecurringDebits += $trans['amount_raw'];
            }
        }

        $recurringData = [
            'transactions' => $recurringTransactions,
            'totalRecurringDebits' => $totalRecurringDebits / 100,
            'totalRecurringCredits' => $totalRecurringCredits /100,
        ];

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
                'expand' => false,
                'end_balance' => 0,
                'asset' => true,
            ],
            'debts' => [
                'name' => 'Totals',
                'start_balance' => 0,
                'in_range_net_growth' => 0,
                'end_balance' => 0,
                'expand' => false,
                'asset' => false,

            ],
        ];
        $total_eco_growth = 0;
        foreach ($account_data as &$acct) {
            $acct['pre_range_net_growth'] ??= 0;
            $acct['in_range_net_growth'] ??= 0;
            $acct['expand'] ??= true;
            $acct['daily_net_growths'] ??= [];
            $acct['end_balance'] ??= 0;

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

            $inital_balance_key = self::BALANCE_LINE_GRAPH_START_LABEL;
            if ($start) {
                $inital_balance_key = $start;
            }
            $acct['daily_balance_line_graph_data'] = array_merge(
                [ $inital_balance_key => $acct['start_balance'] ],
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
                if (in_array($key, [ 'name', 'asset', 'expand' ])) {
                    continue;
                }
                $total_net_growth[$group][$key] = $val / 100;
            }
        }

        array_push($asset_accts, $total_net_growth['assets']);
        array_push($debt_accts, $total_net_growth['debts']);
        $data = [
            'start' => $start,
            'end' => $end,
            'category_type_breakdowns' => $trans_data['category_type_breakdowns'],
            'asset_accounts' => $asset_accts,
            'debt_accounts' => $debt_accts,
            'account_growth_line_data' => [
                'daily_economic_growth' => $account_growth_line_data['daily_economic_growth'],
                'total_economic_growth' => $account_growth_line_data['total_economic_growth']
            ],
            'total_economic_growth' => $total_eco_growth,
            'recurring_transactions' => $recurringData
        ];
        return Inertia::render('Dashboard', [
            'data' => $data
        ]);
    }

}
