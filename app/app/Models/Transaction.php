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
     *  ];
     * @array $args
     * return array
     */
    public static function fetch_transaction_data_for_current_user(array $args) : array
    {
        $start = isset($args['start']) ? $args['start'] : null;
        $end = isset($args['end']) ? $args['end'] : null;
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

        $primary_income_breakdown = [];
        $secondary_income_breakdown = [];
        $regular_expense_breakdown = [];
        $recurring_expense_breakdown = [];
        $extra_expense_breakdown = [];
        $housing_expense_breakdown = [];
        $utility_expense_breakdown = [];

        $total_primary_income = 0;
        $total_secondary_income = 0;
        $total_regular_expenses = 0;
        $total_recurring_expenses = 0;
        $total_extra_expenses = 0;
        $total_housing_expenses = 0;
        $total_utility_expenses = 0;
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


                if ($cat->primary_income) {
                    $total_primary_income += $cat_value;
                    if (isset($primary_income_breakdown[$cat->id])) {
                        $primary_income_breakdown[$cat->id]['value'] += $cat_value;
                        $primary_income_breakdown[$cat->id]['transactions'][] = [
                            'id' => $trans->id,
                            'date' => $trans->transaction_date,
                            'cat_value' => $cat_value / 100,
                            'trans_total' => $trans->amount / 100,
                        ];

                    } else {
                        $primary_income_breakdown[$cat->id] = [
                            'name' => $cat->name,
                            'value' => $cat_value,
                            'color' => $cat->hex_color,
                            'transactions' => [
                                [
                                    'id' => $trans->id,
                                    'date' => $trans->transaction_date,
                                    'cat_value' => $cat_value / 100,
                                    'trans_total' => $trans->amount / 100,
                                ]
                            ]
                        ];
                    }

                } else if ($cat->secondary_income) {
                    $total_secondary_income += $cat_value;
                    if (isset($secondary_income_breakdown[$cat->id])) {
                        $secondary_income_breakdown[$cat->id]['value'] += $cat_value;
                        $secondary_income_breakdown[$cat->id]['transactions'][] = [
                            'id' => $trans->id,
                            'date' => $trans->transaction_date,
                            'cat_value' => $cat_value / 100,
                            'trans_total' => $trans->amount / 100,
                        ];

                    } else {
                        $secondary_income_breakdown[$cat->id] = [
                            'name' => $cat->name,
                            'value' => $cat_value,
                            'color' => $cat->hex_color,
                            'transactions' => [
                                [
                                    'id' => $trans->id,
                                    'date' => $trans->transaction_date,
                                    'cat_value' => $cat_value / 100,
                                    'trans_total' => $trans->amount / 100,
                                ]
                            ]
                        ];
                    }

                } else if ($cat->regular_expense) {
                    $total_regular_expenses += $cat_value;
                    if (isset($regular_expense_breakdown[$cat->id])) {
                        $regular_expense_breakdown[$cat->id]['value'] += $cat_value;
                        $regular_expense_breakdown[$cat->id]['transactions'][] = [
                            'id' => $trans->id,
                            'date' => $trans->transaction_date,
                            'cat_value' => $cat_value / 100,
                            'trans_total' => $trans->amount / 100,
                        ];

                    } else {
                        $regular_expense_breakdown[$cat->id] = [
                            'name' => $cat->name,
                            'value' => $cat_value,
                            'color' => $cat->hex_color,
                            'transactions' => [
                                [
                                    'id' => $trans->id,
                                    'date' => $trans->transaction_date,
                                    'cat_value' => $cat_value / 100,
                                    'trans_total' => $trans->amount / 100,
                                ]
                            ]
                        ];
                    }

                } else if ($cat->recurring_expense) {
                    $total_recurring_expenses += $cat_value;
                    if (isset($recurring_expense_breakdown[$cat->id])) {
                        $recurring_expense_breakdown[$cat->id]['value'] += $cat_value;
                        $recurring_expense_breakdown[$cat->id]['transactions'][] = [
                            'id' => $trans->id,
                            'date' => $trans->transaction_date,
                            'cat_value' => $cat_value / 100,
                            'trans_total' => $trans->amount / 100,
                        ];

                    } else {
                        $recurring_expense_breakdown[$cat->id] = [
                            'name' => $cat->name,
                            'value' => $cat_value,
                            'color' => $cat->hex_color,
                            'transactions' => [
                                [
                                    'id' => $trans->id,
                                    'date' => $trans->transaction_date,
                                    'cat_value' => $cat_value / 100,
                                    'trans_total' => $trans->amount / 100,
                                ]
                            ]
                        ];
                    }

                } else if ($cat->extra_expense) {
                    $total_extra_expenses += $cat_value;
                    if (isset($extra_expense_breakdown[$cat->id])) {
                        $extra_expense_breakdown[$cat->id]['value'] += $cat_value;
                        $extra_expense_breakdown[$cat->id]['transactions'][] = [
                            'id' => $trans->id,
                            'date' => $trans->transaction_date,
                            'cat_value' => $cat_value / 100,
                            'trans_total' => $trans->amount / 100,
                        ];

                    } else {
                        $extra_expense_breakdown[$cat->id] = [
                            'name' => $cat->name,
                            'value' => $cat_value,
                            'color' => $cat->hex_color,
                            'transactions' => [
                                [
                                    'id' => $trans->id,
                                    'date' => $trans->transaction_date,
                                    'cat_value' => $cat_value / 100,
                                    'trans_total' => $trans->amount / 100,
                                ]
                            ]
                        ];
                    }

                } else if ($cat->housing_expense) {
                    $total_housing_expenses += $cat_value;
                    if (isset($housing_expense_breakdown[$cat->id])) {
                        $housing_expense_breakdown[$cat->id]['value'] += $cat_value;
                        $housing_expense_breakdown[$cat->id]['transactions'][] = [
                            'id' => $trans->id,
                            'date' => $trans->transaction_date,
                            'cat_value' => $cat_value / 100,
                            'trans_total' => $trans->amount / 100,
                        ];

                    } else {
                        $housing_expense_breakdown[$cat->id] = [
                            'name' => $cat->name,
                            'value' => $cat_value,
                            'color' => $cat->hex_color,
                            'transactions' => [
                                [
                                    'id' => $trans->id,
                                    'date' => $trans->transaction_date,
                                    'cat_value' => $cat_value / 100,
                                    'trans_total' => $trans->amount / 100,
                                ]
                            ]
                        ];
                    }

                } else if ($cat->utility_expense) {
                    $total_utility_expenses += $cat_value;
                    if (isset($utility_expense_breakdown[$cat->id])) {
                        $utility_expense_breakdown[$cat->id]['value'] += $cat_value;
                        $utility_expense_breakdown[$cat->id]['transactions'][] = [
                            'id' => $trans->id,
                            'date' => $trans->transaction_date,
                            'cat_value' => $cat_value / 100,
                            'trans_total' => $trans->amount / 100,
                        ];

                    } else {
                        $utility_expense_breakdown[$cat->id] = [
                            'name' => $cat->name,
                            'value' => $cat_value,
                            'color' => $cat->hex_color,
                            'transactions' => [
                                [
                                    'id' => $trans->id,
                                    'date' => $trans->transaction_date,
                                    'cat_value' => $cat_value / 100,
                                    'trans_total' => $trans->amount / 100,
                                ]
                            ]
                        ];
                    }
                }

                $categories[] =  [
                    'name' => $cat->name,
                    'cat_id' => $cat->id,
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
                'categories' => $categories,
            ];
        }

        foreach ($extra_expense_breakdown as $id => &$cat_data) {
            $cat_data['value'] = $cat_data['value'] / 100;
        }
        foreach ($regular_expense_breakdown as $id => &$cat_data) {
            $cat_data['value'] = $cat_data['value'] / 100;
        }
        foreach ($recurring_expense_breakdown as $id => &$cat_data) {
            $cat_data['value'] = $cat_data['value'] / 100;
        }
        foreach ($housing_expense_breakdown as $id => &$cat_data) {
            $cat_data['value'] = $cat_data['value'] / 100;
        }
        foreach ($utility_expense_breakdown as $id => &$cat_data) {
            $cat_data['value'] = $cat_data['value'] / 100;
        }
        foreach ($primary_income_breakdown as $id => &$cat_data) {
            $cat_data['value'] = $cat_data['value'] / 100;
        }
        foreach ($secondary_income_breakdown as $id => &$cat_data) {
            $cat_data['value'] = $cat_data['value'] / 100;
        }
        $data['extra_expense_breakdown'] = $extra_expense_breakdown;
        $data['regular_expense_breakdown'] = $regular_expense_breakdown;
        $data['recurring_expense_breakdown'] = $recurring_expense_breakdown;
        $data['housing_expense_breakdown'] = $housing_expense_breakdown;
        $data['utility_expense_breakdown'] = $utility_expense_breakdown;
        $data['primary_income_breakdown'] = $primary_income_breakdown;
        $data['secondary_income_breakdown'] = $secondary_income_breakdown;
        $data['total_extra_expenses'] =  $total_extra_expenses / 100;
        $data['total_regular_expenses'] =  $total_regular_expenses / 100;
        $data['total_recurring_expenses'] =  $total_recurring_expenses / 100;
        $data['total_housing_expenses'] =  $total_housing_expenses / 100;
        $data['total_utility_expenses'] =  $total_utility_expenses / 100;
        $data['total_primary_income'] =  $total_primary_income / 100;
        $data['total_secondary_income'] =  $total_secondary_income / 100;
        return $data;
    }
}
