<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\TransactionPostRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\CategoryType;
use App\Models\Transaction;
/* use thiagoalessio\TesseractOCR\TesseractOCR; */

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response
     */
    public function index(Request $request) : \Inertia\Response
    {
        $start = $request->start;
        $end = $request->end;
        $show_all = $request->show_all;
        $filter_accounts = $request->filter_accounts;
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

        if (! $start && ! $end && !$show_all && ! $use_session_dates) {
            $request->session()->forget([ 'filter_start_date', 'filter_end_date' ]);
            $start = date('Y-m-01');
            $end = date('Y-m-t');
            session(['filter_start_date' => $start]);
            session(['filter_end_date' => $end]);

        } else if ($use_session_dates) {
            $start = session('filter_start_date');
            $end = session('filter_end_date');
            $filter_accounts = session('filter_accounts');

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
        if ($filter_accounts) {
            session([ 'filter_accounts' => $filter_accounts ]);
        } else {
            $request->session()->forget([ 'filter_accounts' ]);
        }

        /**
         * @var \App\Models\User $current_user
         */
        $current_user = Auth::user();
        $data = $current_user->fetchTransactionData(
            $start,
            $end,
            'desc',
            false,
            $filter_accounts
        );
        $data['start'] = $start;
        $data['end'] = $end;
        $data['transactions_created_count'] = session('transactions_created_count');
        $data['transactions_updated_count'] = session('transactions_updated_count');
        $data['categories'] = $this->_fetch_categories();
        $data['category_types'] = $this->_fetch_category_types();
        $accounts = Account::where('user_id', '=', $current_user->id)->get();
        foreach ($accounts as $acct) {
            $data['accounts'][] = [
                'id' => strval($acct->id),
                'name' => $acct->name,
            ];
        }
        return Inertia::render('Transactions', [
            'data' => $data,
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
                'expand' => true,
                'hex_color' => $catt->hex_color,
            ];
        }
        return $catts;
    }

    private function _fetch_categories() : array
    {
        $cats = [];
        $current_user = Auth::user();
        $cat_itty = Category::where('user_id', '=', $current_user->id)
            ->where('active', 1)
            ->orderBy('name')
            ->get();
        foreach ($cat_itty as $cat) {
            $catt = CategoryType::find($cat->category_type_id);
            $cats[] = [
                'name' => $cat->name,
                'cat_id' => $cat->id,
                'cat_type_name' => ($catt) ? $catt->name : null,
                'cat_type_id' => ($catt) ? $catt->id : null,
                'hex_color' => $cat->hex_color
            ];
        }
        return $cats;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\TransactionPostRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(TransactionPostRequest $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validated();
        $trans = new Transaction();
        $newTransactions = $trans->create($data);
        return redirect()
            ->route(
                'transactions',
                [ 'use_session_filter_dates' => true ]
            )->with('transactions_created_count', $newTransactions->count());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\TransactionPostRequest $request
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(TransactionPostRequest $request, Transaction $transaction): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validated();
        $updatedTransCnt = $transaction->updateTrans($data);
        return redirect()
            ->route(
                'transactions',
                [ 'use_session_filter_dates' => true ]
            )->with('transactions_updated_count', $updatedTransCnt);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request) : \Illuminate\Http\RedirectResponse
    {
        $target = Transaction::where('id', '=', $request->id)->first();
        if (! $target) {
            return redirect()
                ->route(
                    'transactions',
                    [ 'use_session_filter_dates' => true ]
                );
        }
        $deleteChildren = ($request->delete_child_transactions) ? true : false;
        $target->deleteTrans($deleteChildren);

        return redirect()
            ->route(
                'transactions',
                [ 'use_session_filter_dates' => true ]
            );
    }
}
