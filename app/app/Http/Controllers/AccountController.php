<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\User;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = [];
        $current_user = Auth::user();
        $acct_types = AccountType::all();
        foreach ($acct_types as $type) {
            $data['types'][] = [
                'id' => $type->id,
                'name' => $type->name,
                'asset' => $type->asset,
            ];
        }
        $accts = Account::where('user_id', '=', $current_user->id)->get();
        foreach ($accts as $acct) {
            $type = AccountType::find($acct->type_id);
            $user = User::find($acct->user_id);
            $acct_data = [
                'name' => $acct->name,
                'type' => $type->name,
                'asset' => $type->asset,
                'interest_rate' => $acct->interest_rate,
                'initial_balance' => $acct->initial_balance / 100,
                'owner' => $user->name
            ];
            $data['accounts'][] = $acct_data;
        }

        return Inertia::render('Accounts', [
            'data' => $data
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
        $current_user = Auth::user();
        $request->validate([
            'name' => ['required', 'max:50'],
            'type' => ['required'],
        ]);
        $acct = new Account();
        $acct->name = $request->name;
        $acct->type_id = $request->type;
        $acct->user_id = $current_user->id;
        $acct->interest_rate = $request->interest_rate;
        $acct->initial_balance = $request->initial_balance * 100;
        $acct->save();
        return redirect()->route('accounts')->with('message', 'Successfully Created Account: #' . $acct->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function show(Account $account)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function edit(Account $account)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Account $account)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\Response
     */
    public function destroy(Account $account)
    {
        //
    }
}
