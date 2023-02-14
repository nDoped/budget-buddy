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
    public function index()
    {
        $data = [
            'transactions' => [],
            'accounts' => [],
        ];
        $current_user = Auth::user();

        $accounts = Account::where('user_id', '=', $current_user->id)->get();
        foreach ($accounts as $acct) {
            $data['accounts'][] = [
                'id' => $acct->id,
                'name' => $acct->name,
            ];
        }
        $transactions = Transaction::where(function($query) {
            $query->select('user_id')
                ->from('accounts')
                ->whereColumn('accounts.id', 'transactions.account_id');
        }, $current_user->id)->get();
        foreach ($transactions as $trans) {
            $acct = $trans->account;
            $type = AccountType::find($acct->type_id);
            $data['transactions'][] = [
                'amount' => $trans->amount / 100,
                'account' => $acct->name,
                'account_type' => $type->name,
                'credit' => ($trans->credit) ? 'Credit' : 'Debit',
                'transaction_date' => $trans->transaction_date,
                'note' => $trans->note,
                'bank_identifier' => $trans->note,
                'id' => $trans->id
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
        $request->validate([
            'amount' => ['required', 'max:50'],
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
        return redirect()->route('transactions')->with('message', 'Successfully Created Transaction: #' . $trans->id);
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
    public function destroy(Transaction $transaction)
    {
        //
    }
}
