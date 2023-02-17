<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Transaction extends Model
{
    use HasFactory;

    /**
     * Get the account that owns the transaction.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public static function fetch_transaction_data_for_current_user($start = null, $end = null, $show_all = null)
    {
        $data = [
            'transactions_in_range' => [],
            'transactions_to_range' => [],
        ];
        $current_user = Auth::user();

        $transactions_to_range = Transaction::where(function($query) {
            $query->select('user_id')
                ->from('accounts')
                ->whereColumn('accounts.id', 'transactions.account_id');
        }, $current_user->id);

        $transactions_in_range = Transaction::where(function($query) {
            $query->select('user_id')
                ->from('accounts')
                ->whereColumn('accounts.id', 'transactions.account_id');
        }, $current_user->id);

        if (! $show_all && ! $start && ! $end ) {
            $start = date('Y-m-01');
            $end = date('Y-m-t');
            $transactions_in_range = $transactions_in_range->where('transaction_date', '>=', $start)
                ->where('transaction_date', '<=', $end)
                ->orderBy('transaction_date', 'desc');


        } else if ($show_all) {
            $start = null;
            $end = null;

        } else if ($start && $end) {
            $transactions_in_range = $transactions_in_range->where('transaction_date', '>=', $start)
                ->where('transaction_date', '<=', $end)
                ->orderBy('transaction_date', 'desc');

        } else if ($start) {
            $transactions_in_range = $transactions_in_range->where('transaction_date', '>=', $start)
                ->orderBy('transaction_date', 'desc');

        } else {
            $transactions_in_range = $transactions_in_range->where('transaction_date', '<=', $end)
                ->orderBy('transaction_date', 'desc');
        }

        $data['start'] = $start;
        $data['end'] = $end;

        if ($start) {
            $transactions_to_range = $transactions_to_range->where('transaction_date', '<', $start)
                ->orderBy('transaction_date', 'desc');
            foreach ($transactions_to_range->get() as $trans) {
                $acct = $trans->account;
                $type = AccountType::find($acct->type_id);
                $data['transactions_to_range'][] = [
                    'amount_raw' => $trans->amount,
                    'amount' => $trans->amount / 100,
                    'account_id' => $acct->id,
                    'account' => $acct->name,
                    'account_type' => $type->name,
                    'asset_txt' => ($trans->credit) ? 'Credit' : 'Debit',
                    'asset' => ($trans->credit) ? true : false,
                    'transaction_date' => $trans->transaction_date,
                    'note' => $trans->note,
                    'bank_identifier' => $trans->note,
                    'id' => $trans->id
                ];
            }
        }


        foreach ($transactions_in_range->get() as $trans) {
            $acct = $trans->account;
            $type = AccountType::find($acct->type_id);
            $data['transactions_in_range'][] = [
                'amount_raw' => $trans->amount,
                'amount' => $trans->amount / 100,
                'account_id' => $acct->id,
                'account' => $acct->name,
                'account_type' => $type->name,
                'asset_text' => ($trans->credit) ? 'Credit' : 'Debit',
                'asset' => ($trans->credit) ? true : false,
                'transaction_date' => $trans->transaction_date,
                'note' => $trans->note,
                'bank_identifier' => $trans->note,
                'id' => $trans->id
            ];
        }
        return $data;
    }
}
