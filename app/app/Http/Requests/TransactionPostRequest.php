<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class TransactionPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $trans = $this->route('transaction');
        Log::info([
          'app/Http/Requests/TransactionPostRequest.php:21 trans type' => gettype($trans)
        ]);
        if ($trans && ! $trans instanceof Transaction) {
            $trans = Transaction::findOrFail($trans);
        }
        $auth = true;
        if ($trans) {
            $acct = Account::find($trans->account_id);
            if ($acct->user_id !== $this->user()->id) {
                $auth = false;
            }
        }
        return $auth;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(Request $request)
    {
        $ret = [];
        $route_name = Route::currentRouteName();
        Log::info([
          'app/Http/Requests/TransactionPostRequest.php:48 request' => $request->all(),
          'app/Http/Requests/TransactionPostRequest.php:48 route' => $route_name
        ]);
        switch ($route_name) {
        case 'transactions.destroy':
            $ret = [
                'id' => [ 'required', 'gt:0', 'numeric' ],
            ];
            break;

        case 'transactions.store':
        case 'transactions.update':
            $ret = [
                'amount' => [ 'required', 'gt:0' ],
                'account_id' => [ 'required', 'numeric' ],
                'transaction_date' => [ 'required', 'date' ],
                'credit' => [ 'required', 'boolean' ],
                'bank_identifier' => [ 'nullable', 'string' ],
                'note' => [ 'nullable', 'string'],
            ];

            if ($route_name === 'transactions.store') {
                $ret = array_merge($ret, [
                    'trans_buddy' => [ 'nullable', 'boolean'],
                    'recurring' => [ 'nullable', 'boolean'],
                ]);

                $to_merge_recurring = [
                    'recurring_end_date' => [ 'date' ],
                    'frequency' => [ 'string', 'in:monthly,biweekly' ],
                ];
                if (! $request->boolean('recurring')) {
                    $to_merge_recurring['recurring_end_date'][] = 'nullable';
                    $to_merge_recurring['frequency'][] = 'nullable';
                }

                $to_merge_buddy = [
                    'trans_buddy_account' => [ 'numeric' ],
                    'trans_buddy_note' => [ 'nullable', 'string' ],
                ];
                if (! $request->boolean('trans_buddy')) {
                    $to_merge_buddy['trans_buddy_account'][] = 'nullable';
                    $to_merge_buddy['trans_buddy_note'][] = 'nullable';
                }


                $ret = array_merge($ret, $to_merge_recurring, $to_merge_buddy, [
                    'categories' => [ 'nullable', 'json'],
                ]);
            }
            break;
        }
        Log::info([
          'app/Http/Requests/TransactionPostRequest.php:101 rules' => $ret,
        ]);
        return $ret;
    }
}
