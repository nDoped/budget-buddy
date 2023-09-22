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
            ->where('active', 1)
            ->orderBy('name')
            ->get();
        foreach ($cat_itty as $cat) {
            $cats[] = [
                'name' => $cat->name,
                'cat_id' => $cat->id,
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
            $trans_buddy->buddy_id = $trans->id;
            $trans_buddy->save();
            $trans->buddy_id = $trans_buddy->id;
            $trans->save();
        }

        $recurring_transactions = [];
        $recurring_buddy_transactions = [];
        $duration = '';
        if ($data['recurring']) {
            switch ($data['frequency']) {
            case 'yearly':
                $duration = 'P1Y';
                break;

            case 'quarterly':
                $duration = 'P3M';
                break;

            case 'monthly':
                $duration = 'P1M';
                break;

            case 'biweekly':
                $duration = 'P14D';
                break;
            }

            $d = new \DateTime($data['transaction_date']);
            $trans->parent_id = $trans->id;
            $trans->save();
            $next_date = $d->add(new \DateInterval($duration));
            while ($next_date->format('Y-m-d') <= $data['recurring_end_date']) {
                $recurring_trans = $trans->replicate();
                $recurring_trans->transaction_date = $next_date->format(\DateTime::ATOM);
                $recurring_trans->parent_id = $trans->id;
                $recurring_trans->save();
                $recurring_transactions[] = $recurring_trans;
                if ($trans_buddy) {
                    $recurring_buddy = $trans_buddy->replicate();
                    $recurring_buddy->transaction_date = $next_date->format(\DateTime::ATOM);
                    $recurring_buddy->buddy_id = $recurring_trans->id;
                    $recurring_buddy->save();
                    $recurring_buddy_transactions[] = $recurring_buddy;
                    $recurring_trans->buddy_id = $recurring_buddy->id;
                    $recurring_trans->save();
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
        if ($transaction->buddy_id) {
            $this->_updateBuddyTransaction($transaction);
        }

        if ($data['edit_child_transactions'] && $transaction->parent_id) {
            $future_transactions = Transaction::where('parent_id', '=', $transaction->parent_id)
                ->where('transaction_date', '>', $transaction->transaction_date)->get();
            foreach ($future_transactions as $future_trans) {
                $future_trans->amount = $data['amount'] * 100;
                $future_trans->credit = $data['credit'];
                $future_trans->account_id = $data['account_id'];
                if ($data['note']) {
                    $future_trans->note = $data['note'];
                }

                $future_trans->categories()->detach();
                foreach ($data['categories'] as $cat) {
                    $cat_model = Category::find($cat['cat_id']);
                    $percent = $cat['percent'];
                    $future_trans->categories()->save($cat_model, [ 'percentage' => $percent * 100 ]);
                }
                $future_trans->save();
                if ($future_trans->buddy_id) {
                    $this->_updateBuddyTransaction($future_trans);
                }
            }
        }

        return redirect()
            ->route(
                'transactions',
                [ 'use_session_filter_dates' => true ]
            );
    }

    private function _updateBuddyTransaction(Transaction $transaction)
    {
        if (! $transaction->buddy_id) {
            return;
        }
        $trans_buddy = Transaction::where('id', '=', $transaction->buddy_id)->first();
        $trans_buddy->amount = $transaction->amount;
        $trans_buddy->credit = ! $transaction->credit;
        $trans_buddy->transaction_date = $transaction->transaction_date;
        $trans_buddy->note = $transaction->note;
        /*
        $trans_buddy->categories()->detach();
        foreach ($transaction->categories as $cat) {
            $percent = $cat->pivot->percentage / 100;
            $trans_buddy->categories()->save($cat, [ 'percentage' => $percent * 100 ]);
        }
         */
        $trans_buddy->save();
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
        $target = Transaction::where('id', '=', $data['id'])->first();
        if (! $target) {
            return redirect()
                ->route(
                    'transactions',
                    [ 'use_session_filter_dates' => true ]
                );
        }

        // we're deleting a parent and not wanting to delete all of the children
        // so we need to move the parent id for all children to the next in line
        if (! $data['delete_child_transactions'] && $target->parent_id === $target->id) {
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
        if ($data['delete_child_transactions']) {
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
