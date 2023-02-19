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
        Log::info([
            'start' => $request->start,
            'end' => $request->end,
            'show_all' => $request->show_all,
        ]);

        $show_all = $request->show_all;
        $start = $request->start;
        $end = $request->end;

        if (! $start && ! $end && ! $show_all) {
            $start = date('Y-m-01');
            $end = date('Y-m-t');

        } else if ($show_all) {
            $start = $end = null;
        }

        $trans_data = Transaction::fetch_transaction_data_for_current_user($start, $end);
        $account_data = $this->_fetch_account_data($start, $end);

        if ($trans_data['transactions_to_range']) {
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

        }
        foreach ($trans_data['transactions_in_range'] as $trans) {
            $acct = $account_data[$trans['account_id']];
            if ($trans['asset']) {
                if ($acct['asset']) {
                    $account_data[$trans['account_id']]['in_range_net_growth'] += $trans['amount_raw'];
                } else {
                    $account_data[$trans['account_id']]['in_range_net_growth'] -= $trans['amount_raw'];
                }

            } else {
                if ($acct['asset']) {
                    $account_data[$trans['account_id']]['in_range_net_growth'] -= $trans['amount_raw'];
                } else {
                    $account_data[$trans['account_id']]['in_range_net_growth'] += $trans['amount_raw'];
                }
            }
        }

        $asset_accts = [];
        $debt_accts = [];
        foreach ($account_data as $id => $acct) {
            $acct['end_balance'] = ($acct['init_balance_raw'] + $acct['pre_range_net_growth'] + $acct['in_range_net_growth']) / 100;
            $acct['start_balance'] = ($acct['init_balance_raw'] + $acct['pre_range_net_growth']) / 100;
            $acct['in_range_net_growth'] = $acct['in_range_net_growth'] / 100;
            if ($acct['asset']) {
                $asset_accts[] = $acct;
            } else {
                $debt_accts[] = $acct;
            }
        }

        $data['start'] = $start;
        $data['end'] = $end;
        $data['asset_accounts'] = $asset_accts;
        $data['debt_accounts'] = $debt_accts;

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
