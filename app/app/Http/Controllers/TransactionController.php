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
            'jsonify_categories' => true,
            'include_to_range' => false,
            'order_by' => 'desc'
        ];
        $data = Transaction::fetch_transaction_data_for_current_user($args);
        $data['start'] = $start;
        $data['end'] = $end;
        $current_user = Auth::user();
        $accounts = Account::where('user_id', '=', $current_user->id)->get();
        foreach ($accounts as $acct) {
            $data['accounts'][] = [
                'id' => $acct->id,
                'name' => $acct->name,
            ];
        }
        /*
        Log::info([
            'app/Http/Controllers/TransactionController.php:86 data' => $data,
        ]);
         */
        return Inertia::render('Transactions', [
            'data' => $data,
        ]);
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
        if ($data['trans_buddy']) {
            $trans_buddy = $trans->replicate();
            $trans_buddy->credit = ! $trans->credit;
            $trans_buddy->account_id = $data['trans_buddy_account'];
            $trans_buddy->note = $data['trans_buddy_note'];
            $trans_buddy->save();
        }

        if ($data['recurring']) {
            $duration = ($data['frequency'] === 'monthly') ? 'P1M' : 'P14D';
            $d = new \DateTime($data['transaction_date']);
            $next_date = $d->add(new \DateInterval($duration));
            Log::info([
                'app/Http/Controllers/TransactionController.php:135 next' => $next_date->format('Y-m-d'),
                'app/Http/Controllers/TransactionController.php:135 end' => $data['recurring_end_date']
            ]);
            while ($next_date->format('Y-m-d') <= $data['recurring_end_date']) {
                $recurring_trans = $trans->replicate();
                $recurring_trans->transaction_date = $next_date->format(\DateTime::ATOM);
                $recurring_trans->save();
                if ($data['trans_buddy']) {
                    $recurring_buddy = $trans_buddy->replicate();
                    $recurring_buddy->transaction_date = $next_date->format(\DateTime::ATOM);
                    $recurring_buddy->save();
                }
                $next_date = $next_date->add(new \DateInterval($duration));
                Log::info([
                    'app/Http/Controllers/TransactionController.php:135 next' => $next_date->format(\DateTime::ATOM),
                ]);
            }
        }

        if ($data['categories']) {
            $cats = json_decode($data['categories'], true);
            foreach ($cats as $cat => $percent) {
                $cat_model = Category::where('name', '=', $cat)->first();
                if (! $cat_model) {
                    $cat_model = new Category();
                    $cat_model->name = $cat;
                    $cat_model->user_id = $current_user->id;
                    $cat_model->save();
                }
                $trans->categories()->save($cat_model, [ 'percentage' => $percent * 100 ]);
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
        /*
        Log::info([
            'app/Http/Controllers/TransactionController.php:194 trans update request' => $request->all(),
        ]);
         */
        $transaction->amount = $data['amount'] * 100;
        $transaction->note = $data['note'];
        $transaction->account_id = $data['account_id'];
        $transaction->credit = $data['credit'];
        $transaction->bank_identifier = $data['bank_identifier'];
        $transaction->transaction_date = $data['transaction_date'];
        $transaction->categories()->detach();
        foreach (json_decode($data['categories'], true) as $cat_name => $percent) {
            $cat_model = Category::where('name', '=', $cat_name)->first();
            if (! $cat_model) {
                $cat_model = new Category();
                $cat_model->name = $cat_name;
                $cat_model->user_id = $current_user->id;
                $cat_model->save();
            }
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
        Log::info([
            'app/Http/Controllers/TransactionController.php:219 destroy data' => $data,
        ]);
        Transaction::destroy($data['id']);
        return redirect()
            ->route(
                'transactions',
                [ 'use_session_filter_dates' => true ]
            );
    }
}
