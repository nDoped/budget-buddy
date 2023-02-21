<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('percentage');
    }

    public static function fetch_transaction_data_for_current_user(string $start = null, string $end = null, bool $return_to_range = true) : array
    {
        $data = [
            'transactions_in_range' => [],
        ];
        if ($return_to_range) {
            $data['transactions_to_range'] = [];
        }
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



        if ($start ) {
            $transactions_in_range = $transactions_in_range->where('transaction_date', '>=', $start);
            if ($return_to_range) {
                $transactions_to_range = $transactions_to_range->where('transaction_date', '<', $start);
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
                        'bank_identifier' => $trans->bank_identifier,
                        'id' => $trans->id
                    ];
                }
            }
        }

        if ($end) {
          $transactions_in_range = $transactions_in_range->where('transaction_date', '<=', $end);
        }

        $cat_totals = [];
        foreach ($transactions_in_range->get() as $trans) {
            $acct = $trans->account;
            $type = AccountType::find($acct->type_id);
            $categories = [];
            foreach ($trans->categories as $cat) {
                // @todo, remove this after the category UI is built out
                $reg = '/^Utility.*$|^Recurring.*$|^Rent$|^Interest.*$|^Security Deposit.*$|Salary.*|Account Adjustment|^Interest.*$|^Refund.*$/';
                if (preg_match($reg, $cat->name)) {
                    continue;
                }
                $percent = $cat->pivot->percentage;
                // ..idk..i guess i wanted percentages to be extremely precise...
                // they're storeed as 10e4 in the db, so divide by
                // 10000 to get its numerical value
                $cat_value = $trans->amount *  ($percent / 10000);

                /*
                if ($cat->name === "Coffee") {
                Log::info([
                    'app/Models/Transaction.php:96 key' => $cat_value,
                ]);
                }
                 */
                if (isset($cat_totals[$cat->id])) {
                    $cat_totals[$cat->id]['value'] =+ $cat_value;

                } else {
                    $cat_totals[$cat->id] = [
                        'name' => $cat->name,
                        'value' => $cat_value,
                        'color' => $cat->hex_color
                    ];
                }
                $categories[] =  [
                    'name' => $cat->name,
                    'color' => $cat->hex_color,
                    'percent' => $percent / 100,
                    'value' => $cat_value / 100
                ];
            }



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
                'bank_identifier' => $trans->bank_identifier,
                'id' => $trans->id,
                'categories' => $categories
            ];
        }
        /*
        Log::info([
            'app/Models/Transaction.php:96 cattos1' => $cat_totals,
        ]);
         */
        foreach ($cat_totals as $id => &$cat_data) {
            $cat_data['value'] = $cat_data['value'] / 100;
        }

        Log::info([
            'app/Models/Transaction.php:96 cattos' => count($cat_totals),
        ]);
        /*
            Log::info([
                'app/Models/Transaction.php:59 trans' => $data['transactions_in_range'],
                'app/Models/Transaction.php:59 cat tots' => $cat_totals
            ]);
         */
        $data['category_totals'] = $cat_totals;
        return $data;
    }
}
