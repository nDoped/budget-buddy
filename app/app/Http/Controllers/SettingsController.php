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
use App\Models\Category;
use App\Models\CategoryType;

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
        return Inertia::render('Settings/Accounts', [
            'data' => $data
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function account_types(Request $request)
    {
        $data['account_types'] = $this->_fetch_account_types();
        return Inertia::render('Settings/AccountTypes', [
            'data' => $data
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function categories(Request $request)
    {
        $cats = [];
        $current_user = Auth::user();
        $cat_itty = Category::where('user_id', '=', $current_user->id)
            ->orderBy('name')
            ->get();
        foreach ($cat_itty as $cat) {
            $catt = CategoryType::find($cat->category_type_id);
            $cats[] = [
                'name' => $cat->name,
                'id' => $cat->id,
                'active_text' => ($cat->active) ? "Yes": "No",
                'expand' => true,
                'active' => ($cat->active) ? true : false,
                'category_type_name' => ($catt) ? $catt->name : null,
                'category_type_id' => ($catt) ? $catt->id : null,
                'color' => $cat->hex_color,
            ];

        }
        return Inertia::render('Settings/Categories', [
            'categories' => $cats,
            'category-types' => $this->_fetch_category_types()
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function category_types(Request $request)
    {
        return Inertia::render('Settings/CategoryTypes', [
            'category-types' => $this->_fetch_category_types()
        ]);
    }

    /**
     * @return array
     */
    private function _fetch_category_types() : array
    {
        $catts = [];
        $current_user = Auth::user();
        $catt_itty = CategoryType::where('user_id', '=', $current_user->id)
            ->orderBy('name')
            ->get();
        foreach ($catt_itty as $catt) {
            $catts[] = [
                'name' => $catt->name,
                'id' => $catt->id,
                'note' => $catt->note,
                'expand' => true,
                'color' => $catt->hex_color,
            ];

        }
        return $catts;
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
        $ret = [];
        foreach ($accts as $acct) {
            $type = AccountType::find($acct->type_id);
            $user = User::find($acct->user_id);
            $ret[] = [
                'name' => $acct->name,
                'type' => $type->name,
                'asset' => $type->asset,
                'interest_rate' => $acct->interest_rate,
                'url' => $acct->url,
                'initial_balance' => $acct->initial_balance / 100,
                'owner' => $user->name
            ];
        }
        return $ret;
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
        return redirect()->route('settings.accounts')->with('message', 'Successfully Created Account: #' . $acct->id);
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
        return redirect()->route('settings.account_types');
    }
}
