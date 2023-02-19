<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\AccountType;
use App\Models\User;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $start = $request->start;
        $end = $request->end;
        $show_all = $request->show_all;
        $use_session_dates = $request->use_session_filter_dates;

        /*
            start   end     show_all        use_ses ||  default current month       show everything         use start/end       use session start/end         clear session
                0     0         0               0   ||      1                           0                      0                      0                            0
                0     0         0               1   ||      0                           0                      0                      1                            0
                0     0         1               0   ||      0                           1                      0                      0                            1
                0     0         1               1   ||
                0     1         0               0   ||      0                           0                      1                      0                            0
                0     1         0               1   ||      0                           0                      1                      0                            0
                0     1         1               0   ||
                0     1         1               1   ||
                1     0         0               0   ||      0                           0                      1                      0                            0
                1     0         0               1   ||      0                           0                      1                      0                            0
                1     1         0               0   ||      0                           0                      1                      0                            0
                1     1         0               1   ||      0                           0                      1                      0                            0
         */

        Log::info([
            'start' => $start,
            'end' => $end,
            'show_all' => $show_all,
            'use_session_dates' => $use_session_dates,
        ]);
        Log::info($request->session()->all());

        if (! $start && ! $end && !$show_all && ! $use_session_dates) {
            $request->session()->forget([ 'filter_start_date', 'filter_end_date' ]);
            $start = date('Y-m-01');
            $end = date('Y-m-t');
            session(['filter_start_date' => $start]);
            session(['filter_end_date' => $end]);

        } else if ($use_session_dates) {
            $start = session('filter_start_date');
            $end = session('filter_end_date');

        } else if ($show_all) {
            $request->session()->forget([ 'filter_start_date', 'filter_end_date' ]);
            $start = $end = null;

        } else if ($start || $end) {

            if ($start && $end) {
                session([ 'filter_start_date' => $start ]);
                session([ 'filter_end_date' => $end ]);
            } else if ($start) {
                session([ 'filter_start_date' => $start ]);
                session([ 'filter_end_date' => null ]);
            } else {
                session([ 'filter_start_date' => null ]);
                session([ 'filter_end_date' => $end ]);
            }
        }

        /*
        Log::info([
            'start' => $start,
            'end' => $end,
        ]);

        Log::info($request->session()->all());
         */

        $data = Transaction::fetch_transaction_data_for_current_user($start, $end);
        $data['start'] = $start;
        $data['end'] = $end;
        $current_user = Auth::user();
        $accounts = Account::where('user_id', '=', $current_user->id)->get();
        foreach ($accounts as $acct) {
            $data['accounts'][] = [
                'id' => $acct->id,
                'name' => $acct->name,
            ];
        }
        return Inertia::render('Transactions', [
            'data' => $data,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /*
        Log::info([
            'freq' => $request->frequency,
            'recurr' => $request->recurring,
            'end' => $request->end_date,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
            'account' => $request->account,
            'transBuddy' => $request->transBuddy,
            'transBuddyAcct' => $request->transBuddyAccount,
            'transBuddyNote' => $request->transBuddyNote,
        ]);
         */

        $request->validate([
            'amount' => ['required', 'gt:0',],
            'account' => ['required', 'numeric'],
            'transaction_date' => ['required', 'date'],
            'credit' => ['required'],
        ]);

        $trans = new Transaction();
        $trans->amount = $request->amount * 100;
        $trans->credit = ($request->credit === 'true') ? 1 : 0;
        $trans->account_id = $request->account;
        $trans->transaction_date = $request->transaction_date;
        $trans->note = $request->note;
        $trans->save();
        if ($request->transBuddy) {
            $trans_buddy = $trans->replicate();
            $trans_buddy->credit = ! $trans->credit;
            $trans_buddy->note = $request->transBuddyNote;
            $trans_buddy->account_id = $request->transBuddyAccount;
            $trans_buddy->save();
        }

        if ($request->recurring) {
            $duration = ($request->frequency === 'monthly') ? 'P1M' : 'P14D';
            $d = new \DateTime($request->transaction_date);
            $next_date = $d->add(new \DateInterval($duration));
            while ($next_date->format(\DateTime::ATOM) <= $request->end_date) {
                $recurring_trans = $trans->replicate();
                $recurring_trans->transaction_date = $next_date->format(\DateTime::ATOM);
                $recurring_trans->save();
                if ($request->transBuddy) {
                    $recurring_buddy = $trans_buddy->replicate();
                    $recurring_buddy->transaction_date = $next_date->format(\DateTime::ATOM);
                    $recurring_buddy->save();
                }
                $next_date = $next_date->add(new \DateInterval($duration));
            }
        }

        /*
        Log::info([
            'in store start'=> $request->transStart,
            'end'=> $request->transEnd,
        ]);
         */

        return redirect()
            ->route(
                'transactions',
                [ 'use_session_filter_dates' => true ]
            );
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function show(Transaction $transaction)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function edit(Transaction $transaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Transaction $transaction)
    {
        $transaction->amount = $request->amount * 100;
        $transaction->note = $request->note;
        $transaction->account_id = $request->account_id;
        $transaction->credit = $request->boolean('credit');
        $transaction->bank_identifier = $request->bank_identifier;
        $transaction->transaction_date = $request->date('transaction_date');
        $transaction->save();
        return redirect()
            ->route(
                'transactions',
                [ 'use_session_filter_dates' => true ]
            );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        Transaction::destroy($request->id);
        return redirect()
            ->route(
                'transactions',
                [ 'use_session_filter_dates' => true ]
            );
    }
}
