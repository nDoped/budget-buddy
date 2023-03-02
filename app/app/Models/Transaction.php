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

    /**
     * Takes arg array of the type
     *  $args = [
     *      'start' => bool,
     *      'end' => bool,
     *      'jsonify_categories' => bool,
     *      'include_to_range' => bool,
     *      'filter_for_expense_breakdown' => bool,
     *      'order_by' => string,
     *  ];
     * @array $args
     * return array
     */
    public static function fetch_transaction_data_for_current_user(array $args) : array
    {
        $start = isset($args['start']) ? $args['start'] : null;
        $end = isset($args['end']) ? $args['end'] : null;
        $return_to_range = isset($args['include_to_range']) ? $args['include_to_range'] : null;
        $jsonify_categories = isset($args['jsonify_categories']) ? $args['jsonify_categories'] : null;
        $order_by = isset($args['order_by']) ? $args['order_by'] : null;
        $data = [
            'transactions_in_range' => [],
        ];
        if ($return_to_range) {
            $data['transactions_to_range'] = [];
        }

        if ($order_by && ! in_array($order_by, ['asc', 'desc'])) {
            return $data;
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
        }, $current_user->id)->orderBy('transaction_date', $order_by);



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
            /*
            Log::info([
                'app/Models/Transaction.php:102 transId' => $args
            ]);
             */
            foreach ($trans->categories as $cat) {
                if ($cat->id ===6) {
                    /*
                    Log::info([
                        'app/Models/Transaction.php:106 cat include in' => $cat->include_in_expense_breakdown,
                        'app/Models/Transaction.php:106 trans id' => $trans->id,
                        'app/Models/Transaction.php:106 trans amount' => $trans->amount,
                    ]);
                     */
                }

                if (isset($args['filter_for_expense_breakdown']) && $args['filter_for_expense_breakdown']) {
                    if (! $cat->include_in_expense_breakdown) {
                        continue;
                    }
                }

                $percent = $cat->pivot->percentage;
                // ..idk..i guess i wanted percentages to be extremely precise...
                // they're storeed as 10e4 in the db, so divide by
                // 10000 to get its numerical value
                $cat_value = $trans->amount *  ($percent / 10000);

                if (isset($cat_totals[$cat->id])) {
                    $cat_totals[$cat->id]['value'] += $cat_value;

                } else {
                    $cat_totals[$cat->id] = [
                        'name' => $cat->name,
                        'value' => $cat_value,
                        'color' => $cat->hex_color
                    ];
                }

                if ($jsonify_categories) {
                    $categories[$cat->name] =  $percent / 100;

                } else {
                    $categories[] =  [
                        'name' => $cat->name,
                        'color' => $cat->hex_color,
                        'percent' => $percent / 100,
                        'value' => $cat_value / 100
                    ];

                }
            }

            if ($jsonify_categories) {
                /*
                Log::info([
                    'app/Models/Transaction.php:142 los gatos' => $categories,
                ]);
                 */
                $categores = json_encode($categories);
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
