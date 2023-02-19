<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\User;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['accounts'] = $this->_fetch_accounts();
        $data['account_types'] = $this->_fetch_account_types();
        return Inertia::render('Settings/Show', [
            'data' => $data
        ]);
    }

    /**
     * @return array
     */
    private function _fetch_account_types() : array
    {
        $current_user = Auth::user();
        $acct_types = AccountType::where('user_id', '=', $current_user->id)->get();
        $ret = [];
        foreach ($acct_types as $type) {
            $ret[] = [
                'id' => $type->id,
                'name' => $type->name,
                'asset' => $type->asset,
            ];
        }
        return $ret;
    }

    /**
     * @return array
     */
    private function _fetch_accounts() : array
    {
        $current_user = Auth::user();
        $accts = Account::where('user_id', '=', $current_user->id)->get();
        foreach ($accts as $acct) {
            $type = AccountType::find($acct->type_id);
            $user = User::find($acct->user_id);
            $acct_data = [
                'name' => $acct->name,
                'type' => $type->name,
                'asset' => $type->asset,
                'interest_rate' => $acct->interest_rate,
                'url' => $acct->url,
                'initial_balance' => $acct->initial_balance / 100,
                'owner' => $user->name
            ];
            $ret[] = $acct_data;
        }
        return $ret;
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
    public function store_account(Request $request)
    {
        $current_user = Auth::user();
        $request->validate([
            'name' => [ 'required', 'max:50' ],
            'type' => [ 'required' ],
            'initial_balance' => [ 'nullable', 'numeric' ],
            'interest_rate' => [ 'nullable', 'numeric' ],
            'url' => [ 'nullable', 'url' ]
        ]);
        $acct = new Account();
        $acct->name = $request->name;
        $acct->type_id = $request->type;
        $acct->user_id = $current_user->id;
        $acct->interest_rate = $request->interest_rate;
        $acct->initial_balance = $request->initial_balance * 100;
        $acct->url = $request->url;
        $acct->save();
        return redirect()->route('settings.show')->with('message', 'Successfully Created Account: #' . $acct->id);
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
      return [
        'url.url' => 'A valid url is required. eg. https://example.org',
      ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_account_type(Request $request)
    {
        $current_user = Auth::user();
        $request->validate([
            'name' => ['required', 'max:50'],
            'asset' => ['required'],
        ]);
        $acct = new AccountType();
        $acct->name = $request->name;
        $acct->user_id = $current_user->id;
        $acct->asset = $request->boolean('asset');
        $acct->save();
        return redirect()->route('settings.show')->with('message', 'Successfully Created Account: #' . $acct->id);
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
