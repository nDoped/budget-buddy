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
        switch ($route_name) {
        case 'transactions.store':
        case 'transactions.update':
            $ret = [
                'amount' => [ 'required', 'gte:0' ],
                'account_id' => [ 'required', 'numeric' ],
                'transaction_date' => [ 'required', 'date' ],
                'credit' => [ 'required', 'boolean' ],
                'bank_identifier' => [ 'nullable', 'string' ],
                'note' => [ 'nullable', 'string'],
                'edit_child_transactions' => [ 'nullable', 'boolean'],
                'images_base64' => [ 'nullable', 'array'],
                'categories' => [ 'nullable' ],
                'categories.*.cat_data.name' => [ 'required' ],
            ];

            if ($route_name === 'transactions.store') {
                $ret = array_merge($ret, [
                    'trans_buddy' => [ 'nullable', 'boolean'],
                    'recurring' => [ 'nullable', 'boolean'],
                ]);

                $to_merge_recurring = [
                    'recurring_end_date' => [ 'date' ],
                    'frequency' => [ 'string', 'in:monthly,biweekly,yearly,quarterly' ],
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

                $ret = array_merge($ret, $to_merge_recurring, $to_merge_buddy);
            } else {
                $ret = array_merge($ret, [
                    'images' => [ 'nullable', 'array'],
                ]);
            }

            break;
        }
        return $ret;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'categories.*.cat_data.name.required' => 'A category name is required',
            'account_id.required' => 'An account is required',
            'amount.required' => 'An amount is required',
        ];
    }
}
