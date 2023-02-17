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
        $data = Transaction::fetch_transaction_data_for_current_user($request->start, $request->end, $request->show_all);
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

        Log::info([
            'in store start'=> $request->transStart,
            'end'=> $request->transEnd,

        ]);
        return redirect()
            ->route(
                'transactions',
                [ 'start' => $request->transStart, 'end' => $request->transEnd ]
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
        //
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
                [ 'start' => $request->transStart, 'end' => $request->transEnd ]
            );
    }
}
