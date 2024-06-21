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
     *      'include_to_range' => bool,
     *      'order_by' => string,
     *      'filter_accounts' => array,
     *  ];
     * @array $args
     * return array
     */
    public static function fetch_transaction_data_for_current_user(array $args) : array
    {
        $start = isset($args['start']) ? $args['start'] : null;
        $end = isset($args['end']) ? $args['end'] : null;
        $filter_accounts = isset($args['filter_accounts']) ? $args['filter_accounts'] : null;
        $return_to_range = isset($args['include_to_range']) ? $args['include_to_range'] : null;
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
        if ($filter_accounts) {
            $transactions_in_range = $transactions_in_range->whereIn('account_id', $filter_accounts);
        }

        if ($start) {
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
                        'expand' => true,
                        'transaction_date' => $trans->transaction_date,
                        'note' => $trans->note,
                        'bank_identifier' => $trans->bank_identifier,
                        'id' => $trans->id,
                        'buddy_id' => $trans->buddy_id,
                        'parent_id' => $trans->parent_id,
                        'parent_transaction_date' => self::_fetch_parent_transaction_date($trans)
                    ];
                }
            }
        }

        if ($end) {
            $transactions_in_range = $transactions_in_range->where('transaction_date', '<=', $end);
        }

        $category_type_breakdowns = [];

        foreach ($transactions_in_range->get() as $trans) {
            $acct = $trans->account;
            $type = AccountType::find($acct->type_id);
            $categories = [];
            foreach ($trans->categories as $cat) {
                $percent = $cat->pivot->percentage;
                // ..idk..i guess i wanted percentages to be extremely precise...
                // they're stored as 10e4 in the db, so divide by
                // 10000 to get its numerical value
                $cat_value = $trans->amount * ($percent / 10000);

                if ($cat->category_type_id) {
                    $cat_type = CategoryType::find($cat->category_type_id);
                    if (isset($category_type_breakdowns[$cat->category_type_id])) {
                        $category_type_breakdowns[$cat->category_type_id]['total'] += $cat_value;
                        if (isset($category_type_breakdowns[$cat->category_type_id]['data'][$cat->id])) {
                            $category_type_breakdowns[$cat->category_type_id]['data'][$cat->id]['value'] += $cat_value;
                            $category_type_breakdowns[$cat->category_type_id]['data'][$cat->id]['transactions'][] = [
                                'id' => $trans->id,
                                'date' => $trans->transaction_date,
                                'cat_value' => $cat_value / 100,
                                'note' => $trans->note,
                                'trans_total' => $trans->amount / 100,
                            ];

                        } else {
                            $category_type_breakdowns[$cat->category_type_id]['data'][$cat->id] = [
                                'name' => $cat->name,
                                'value' => $cat_value,
                                'color' => $cat->hex_color,
                                'transactions' => [
                                    [
                                        'id' => $trans->id,
                                        'date' => $trans->transaction_date,
                                        'cat_value' => $cat_value / 100,
                                        'note' => $trans->note,
                                        'trans_total' => $trans->amount / 100,
                                    ]
                                ]
                            ];
                        }

                    } else {
                        $category_type_breakdowns[$cat->category_type_id] = [
                            'name' => $cat_type->name,
                            'color' => $cat_type->hex_color,
                            'total' => $cat_value,
                            'data' => [
                                $cat->id =>  [
                                    'name' => $cat->name,
                                    'value' => $cat_value,
                                    'color' => $cat->hex_color,
                                    'transactions' => [
                                        [
                                            'id' => $trans->id,
                                            'date' => $trans->transaction_date,
                                            'note' => $trans->note,
                                            'cat_value' => $cat_value / 100,
                                            'trans_total' => $trans->amount / 100,
                                        ]
                                    ]
                                ]
                            ]
                        ];
                    }

                }

                $catt = CategoryType::find($cat->category_type_id);
                $categories[] =  [
                    'cat_data' => [
                        'name' => $cat->name,
                        'cat_id' => $cat->id,
                        'cat_type_name' => ($catt) ? $catt->name : null,
                        'cat_type_id' => ($catt) ? $catt->id : null,
                        'color' => $cat->hex_color,
                    ],
                    'percent' => $percent / 100,
                    //'value' => $cat_value / 100
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
                'expand' => true,
                'note' => $trans->note,
                'bank_identifier' => $trans->bank_identifier,
                'id' => $trans->id,
                'buddy_id' => $trans->buddy_id,
                'parent_id' => $trans->parent_id,
                'parent_transaction_date' => self::_fetch_parent_transaction_date($trans),
                'categories' => $categories,
            ];
        }

        foreach ($category_type_breakdowns as &$catt_data) {
                $catt_data['total'] = $catt_data['total'] / 100;
            foreach ($catt_data['data'] as &$cat_data) {
                $cat_data['value'] = $cat_data['value'] / 100;
            }
        }

        $data['category_type_breakdowns'] = $category_type_breakdowns;
        return $data;
    }

    private static function _fetch_parent_transaction_date(Transaction $trans) : string|null
    {
        $parent_trans_date = null;
        if ($trans->parent_id) {
            $parent_trans = Transaction::where('id', '=', $trans->parent_id)->first();
            $parent_trans_date = $parent_trans->transaction_date;

        }
        return $parent_trans_date;
    }
}
