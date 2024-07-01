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

        $args = [
            'start_date' => $start,
            'end_date' => $end,
            'include_to_range' => false,
            'order_by' => 'desc',
            'filter_account_ids' => $filter_accounts
        ];
        $data = Transaction::fetch_transaction_data_for_current_user($args);
        $data['start'] = $start;
        $data['end'] = $end;
        $data['categories'] = $this->_fetch_categories();
        $data['category_types'] = $this->_fetch_category_types();
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
        $trans->create($data);
        return redirect()
            ->route(
                'transactions',
                [ 'use_session_filter_dates' => true ]
            );
        //return to_route('transactions')->with('use_session_filter_dates', true);
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
        $transaction->updateTrans($data);
        return redirect()
            ->route(
                'transactions',
                [ 'use_session_filter_dates' => true ]
            );
        //return to_route('transactions')->with('use_session_filter_dates', true);
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

        // we're deleting a parent and not wanting to delete all of the children
        // so we need to move the parent id for all children to the next in line
        if (! $request->delete_child_transactions && $target->parent_id === $target->id) {
            $next_child_trans = Transaction::where('parent_id', '=', $target->id)
                ->where('id', '<>', $target->id)
                ->first();
            $child_transactions = Transaction::where('parent_id', '=', $target->id)
                ->where('id', '<>', $target->id)
                ->get();
            foreach ($child_transactions as $child_trans) {
                $child_trans->parent_id = $next_child_trans->id;
                $child_trans->save();
            }
        }
        if ($request->delete_child_transactions) {
            $child_transactions = Transaction::where('parent_id', '=', $target->parent_id)
                ->where('transaction_date', '>', $target->transaction_date)
                ->get();
            foreach ($child_transactions as $child_trans) {
                if ($child_trans->buddy_id) {
                    Transaction::destroy($child_trans->buddy_id);
                }
                Transaction::destroy($child_trans->id);
            }
        }

        // if the target has a buddy, delete that too
        if ($target->buddy_id) {
            // if that buddy is the parent to a recurring trans sequence,
            // then we must cycle the parent ids down to the next in line
            $buddy = Transaction::where('id', '=', $target->buddy_id)->first();
            if ($buddy->parent_id === $buddy->id) {
                $next_child_trans = Transaction::where('parent_id', '=', $buddy->id)
                    ->where('id', '<>', $buddy->id)
                    ->first();
                $child_transactions = Transaction::where('parent_id', '=', $buddy->id)
                    ->where('id', '<>', $buddy->id)
                    ->get();
                foreach ($child_transactions as $child_trans) {
                    $child_trans->parent_id = $next_child_trans->id;
                    $child_trans->save();
                }
            }
            Transaction::destroy($buddy->id);
        }
        Transaction::destroy($target->id);
        return redirect()
            ->route(
                'transactions',
                [ 'use_session_filter_dates' => true ]
            );
    }
}
