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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function categories(Request $request)
    {
        /*
        Log::info([
            'app/Http/Controllers/SettingsController.php:38 request' => $request->all(),
        ]);
         */
        $cats = [];
        $current_user = Auth::user();
        $cat_itty = Category::where('user_id', '=', $current_user->id)
            ->orderBy('name')
            ->get();
        foreach ($cat_itty as $cat) {
            $cats[] = [
                'name' => $cat->name,
                'id' => $cat->id,
                'include_in_expense_breakdown' => $cat->include_in_expense_breakdown,
                'include_in_expense_breakdown_text' => ($cat->include_in_expense_breakdown) ? "Yes" : "No",
                'color' => $cat->hex_color,
            ];

        }
        return Inertia::render('Settings/Categories', [
            'categories' => $cats
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
        return redirect()->route('settings.show')->with('message', 'Successfully Created Account: #' . $acct->id);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_category(Request $request)
    {
        $current_user = Auth::user();
        $request->validate([
            'name' => [ 'required', 'max:50' ],
        ]);
        $cat = new Category();
        $cat->name = $request->name;
        $cat->hex_color = $request->color;
        $cat->user_id = $current_user->id;
        $cat->include_in_expense_breakdown = $request->include_in_expense_breakdown;
        $cat->save();
        return redirect()->route('settings.categories')->with('message', 'Successfully Created Category');
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
}
