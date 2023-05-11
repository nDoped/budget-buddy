<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Requests\TransactionPostRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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

        $args = [
            'start' => $start,
            'end' => $end,
            'include_to_range' => false,
            'order_by' => 'desc',
            'filter_accounts' => $filter_accounts
        ];
        $data = Transaction::fetch_transaction_data_for_current_user($args);
        $data['start'] = $start;
        $data['end'] = $end;
        $data['categories'] = $this->_fetch_categories();
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

    private function _fetch_categories() : array
    {
        $cats = [];
        $current_user = Auth::user();
        $cat_itty = Category::where('user_id', '=', $current_user->id)
            ->orderBy('name')
            ->get();
        foreach ($cat_itty as $cat) {
            $cats[] = [
                'name' => $cat->name,
                'cat_id' => $cat->id,
                'extra_expense' => ($cat->extra_expense) ? "Yes" : "No",
                'color' => $cat->hex_color,
            ];
        }
        return $cats;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\TransactionPostRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(TransactionPostRequest $request) : \Illuminate\Http\RedirectResponse
    {
        $data = $request->validated();
        $current_user = Auth::user();
        $trans = new Transaction();
        $trans->amount = $data['amount'] * 100;
        $trans->credit = $data['credit'];
        $trans->account_id = $data['account_id'];
        $trans->transaction_date = $data['transaction_date'];
        $trans->note = $data['note'];
        $trans->save();
        $trans_buddy = null;
        if ($data['trans_buddy']) {
            $trans_buddy = $trans->replicate();
            $trans_buddy->credit = ! $trans->credit;
            $trans_buddy->account_id = $data['trans_buddy_account'];
            $trans_buddy->note = $data['trans_buddy_note'];
            $trans_buddy->save();
        }

        $recurring_transactions = [];
        $recurring_buddy_transactions = [];
        if ($data['recurring']) {
            $duration = ($data['frequency'] === 'monthly') ? 'P1M' : 'P14D';
            $d = new \DateTime($data['transaction_date']);
            $next_date = $d->add(new \DateInterval($duration));
            while ($next_date->format('Y-m-d') <= $data['recurring_end_date']) {
                $recurring_trans = $trans->replicate();
                $recurring_trans->transaction_date = $next_date->format(\DateTime::ATOM);
                $recurring_trans->save();
                $recurring_transactions[] = $recurring_trans;
                if ($trans_buddy) {
                    $recurring_buddy = $trans_buddy->replicate();
                    $recurring_buddy->transaction_date = $next_date->format(\DateTime::ATOM);
                    $recurring_buddy->save();
                    $recurring_buddy_transactions[] = $recurring_buddy;
                }
                $next_date = $next_date->add(new \DateInterval($duration));
            }
        }

        foreach ($data['categories'] as $cat) {
            $cat_model = Category::find($cat['cat_id']);
            $percent = $cat['percent'];
            $trans->categories()->save($cat_model, [ 'percentage' => $percent * 100 ]);

            if ($trans_buddy) {
                $trans_buddy->categories()->save($cat_model, [ 'percentage' => $percent * 100 ]);
            }
            foreach ($recurring_transactions as $rec_trans) {
                $rec_trans->categories()->save($cat_model, [ 'percentage' => $percent * 100 ]);
            }
            foreach ($recurring_buddy_transactions as $rec_bud_trans) {
                $rec_bud_trans->categories()->save($cat_model, [ 'percentage' => $percent * 100 ]);
            }
        }

        return redirect()
            ->route(
                'transactions',
                [ 'use_session_filter_dates' => true ]
            );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\TransactionPostRequest $request
     * @param  \App\Models\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function update(TransactionPostRequest $request, Transaction $transaction) : \Illuminate\Http\RedirectResponse
    {
        $data = $request->validated();
        $current_user = Auth::user();
        $transaction->amount = $data['amount'] * 100;
        $transaction->account_id = $data['account_id'];
        $transaction->credit = $data['credit'];
        if ($data['bank_identifier']) {
            $transaction->bank_identifier = $data['bank_identifier'];
        }
        if ($data['note']) {
            $transaction->note = $data['note'];
        }
        $transaction->transaction_date = $data['transaction_date'];
        $transaction->categories()->detach();
        foreach ($data['categories'] as $cat) {
            $cat_model = Category::find($cat['cat_id']);
            $percent = $cat['percent'];
            $transaction->categories()->save($cat_model, [ 'percentage' => $percent * 100 ]);
        }

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
     * @param  \App\Http\Requests\TransactionPostRequest $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(TransactionPostRequest $request) : \Illuminate\Http\RedirectResponse
    {
        $data = $request->validated();
        Transaction::destroy($data['id']);
        return redirect()
            ->route(
                'transactions',
                [ 'use_session_filter_dates' => true ]
            );
    }
}
