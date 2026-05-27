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
use App\Models\CategorySubtype;
use App\Services\ActivityLogService;

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
        $search = $request->query('search');
        $categoryTypeId = $request->query('category_type_id');

        $cat_itty = $current_user->categories()
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', '%'.$search.'%');
            })
            ->when($categoryTypeId, function ($query, $categoryTypeId) {
                return $query->where('category_type_id', $categoryTypeId);
            })
            ->get();
        $all_cats = $current_user->categories()
            ->with('categoryType:id,name', 'categorySubtype:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'category_type_id', 'category_subtype_id'])
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'category_type_id' => $cat->category_type_id,
                'category_type_name' => $cat->categoryType?->name,
                'category_subtype_id' => $cat->category_subtype_id,
                'category_subtype_name' => $cat->categorySubtype?->name,
            ]);
        foreach ($cat_itty as $cat) {
            $catt = CategoryType::find($cat->category_type_id);
            $subtype = $cat->categorySubtype;
            $cats[] = [
                'name' => $cat->name,
                'id' => $cat->id,
                'active_text' => ($cat->active) ? "Yes": "No",
                'expand' => true,
                'active' => ($cat->active) ? true : false,
                'category_type_name' => ($catt) ? $catt->name : null,
                'category_type_id' => ($catt) ? $catt->id : null,
                'category_subtype_id' => $subtype?->id,
                'category_subtype_name' => $subtype?->name,
                'hex_color' => $cat->hex_color,
            ];

        }

        usort($cats, function($a, $b) {
            $result = strcmp($a['category_type_name'], $b['category_type_name']);
            if ($result === 0) {
                $result = strcmp($a['name'], $b['name']);
            }
            return $result;
        });

        return Inertia::render('Settings/Categories', [
            'categories' => $cats,
            'category-types' => $this->_fetch_category_types(),
            'category-subtypes' => $this->_fetch_category_subtypes(),
            'all-categories' => $all_cats
        ]);
    }

    public function category_types(Request $request)
    {
        return Inertia::render('Settings/CategoryTypes', [
            'category-types' => $this->_fetch_category_types()
        ]);
    }

    public function category_subtypes(Request $request)
    {
        return Inertia::render('Settings/Subtypes', [
            'category-subtypes' => $this->_fetch_category_subtypes(),
            'category-types' => $this->_fetch_category_types(),
        ]);
    }

    private function _fetch_category_subtypes() : array
    {
        $current_user = Auth::user();
        $subtypes = CategorySubtype::where('user_id', '=', $current_user->id)
            ->with('categoryType')
            ->orderBy('name')
            ->get();
        $ret = [];
        foreach ($subtypes as $subtype) {
            $ret[] = [
                'id' => $subtype->id,
                'name' => $subtype->name,
                'category_type_id' => $subtype->category_type_id,
                'category_type_name' => $subtype->categoryType?->name,
                'expand' => true,
            ];
        }
        return $ret;
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
        $acct_types = $current_user->accountTypes;
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
        $accts = $current_user->accounts;
        $ret = [];
        foreach ($accts as $acct) {
            $type = AccountType::find($acct->type_id);
            $user = User::find($acct->user_id);
            $ret[] = [
                'name' => $acct->name,
                'id' => $acct->id,
                'type' => $type->name,
                'type_id' => $acct->type_id,
                'asset' => $type->asset,
                'interest_rate' => $acct->interest_rate,
                'url' => $acct->url,
                'number' => $acct->number,
                'initial_balance' => $acct->initial_balance / 100,
                'active' => $acct->active,
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
            'url' => [ 'nullable', 'url' ],
            'number' => [ 'nullable', 'string', 'max:4' ]
        ]);
        $acct = new Account();
        $acct->name = $request->name;
        $acct->type_id = $request->type;
        $acct->user_id = $current_user->id;
        $acct->interest_rate = $request->interest_rate;
        $acct->initial_balance = $request->initial_balance * 100;
        $acct->url = $request->url;
        $acct->number = $request->number;
        $acct->active = $request->boolean('active');
        $acct->save();
        ActivityLogService::accountCreated($acct->id, $acct->name);
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
     * Update the specified account in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Account  $account
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update_account(Request $request, Account $account)
    {
        $current_user = Auth::user();
        if ($account->user_id !== $current_user->id) {
            abort(403);
        }
        $request->validate([
            'name' => [ 'required', 'max:50' ],
            'type' => [ 'required' ],
            'initial_balance' => [ 'nullable', 'numeric' ],
            'interest_rate' => [ 'nullable', 'numeric' ],
            'url' => [ 'nullable', 'url' ],
            'number' => [ 'nullable', 'string', 'max:4' ]
        ]);
        $old = [
            'name' => $account->name,
            'type_id' => $account->type_id,
            'interest_rate' => $account->interest_rate,
            'initial_balance' => $account->initial_balance,
            'url' => $account->url,
            'active' => $account->active,
            'number' => $account->number,
        ];
        $account->name = $request->name;
        $account->type_id = $request->type;
        $account->interest_rate = $request->interest_rate;
        $account->initial_balance = $request->initial_balance * 100;
        $account->url = $request->url;
        $account->number = $request->number;
        $account->active = $request->boolean('active');
        $account->save();
        $changes = array_filter([
            'name' => ['old' => $old['name'], 'new' => $account->name],
            'type_id' => ['old' => $old['type_id'], 'new' => $account->type_id],
            'interest_rate' => ['old' => $old['interest_rate'], 'new' => $account->interest_rate],
            'initial_balance' => ['old' => $old['initial_balance'], 'new' => $account->initial_balance],
            'url' => ['old' => $old['url'], 'new' => $account->url],
            'number' => ['old' => $old['number'], 'new' => $account->number],
            'active' => ['old' => $old['active'], 'new' => $account->active],
        ], fn($v) => $v['old'] != $v['new']);
        ActivityLogService::accountUpdated($account->id, $account->name, $changes);
        return redirect()->route('settings.accounts')->with('message', 'Successfully Updated Account: #' . $account->id);
    }

    /**
     * Remove the specified account from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy_account(Request $request) : \Illuminate\Http\RedirectResponse
    {
        $account = Account::where('id', '=', $request->id)->first();
        if ($account) {
            $linked_transactions = $account->transactions();
            if ($linked_transactions->count() > 0) {
                return redirect()->back()->withErrors([
                    'message' => 'This account has at least 1 transaction and cannot be deleted'
                ]);
            }
            ActivityLogService::accountDeleted($request->id, $account->name);
            Account::destroy($request->id);
        } else {
            return redirect()->back()->withErrors('Invalid account id');
        }
        return redirect()->route('settings.accounts');
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
        ActivityLogService::accountTypeCreated($acct->id, $acct->name);
        return redirect()->route('settings.account_types');
    }
}
