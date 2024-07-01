<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * App\Models\Transaction
 *
 * @property int $id
 * @property int $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Exam whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Transaction extends Model
{
    use HasFactory;

    /**
     * Get the account that owns the transaction.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Updates the current transaction with the given data
     *
     * @param array{
     *          amount: int,
     *          account_id: int,
     *          transaction_date: string,
     *          credit: bool,
     *          categories?: array{
     *              cat_data: array{
     *                  cat_id?: int,
     *                  hex_color: string,
     *                  name: string
     *              },
     *              percent?: float
     *          },
     *          trans_buddy?: bool,
     *          edit_child_transactions?: bool,
     *          note?: string,
     *          bank_identifier?: string,
     *          recurring?: bool,
     *        } $data
     *
     * @return Transaction The updated transaction
     * @throws \InvalidArgumentException
     */
    public function updateTrans(array $data): Transaction
    {
        $this->amount = $data['amount'] * 100;
        $this->account_id = $data['account_id'];
        $this->credit = $data['credit'];
        $this->transaction_date = $data['transaction_date'];
        if (array_key_exists('bank_identifier', $data)) {
            $this->bank_identifier = strip_tags($data['bank_identifier']);
        }
        $updateChildren = (array_key_exists('edit_child_transactions', $data)
            && $data['edit_child_transactions']);

        if (array_key_exists('note', $data)) {
            $this->note = strip_tags($data['note']);
        }

        $this->_syncBuddyTransaction();

        if ($updateChildren) {
            foreach ($this->children() as $child) {
                $child->amount = $data['amount'] * 100;
                $child->credit = $data['credit'];
                $child->account_id = $data['account_id'];
                if ($data['note']) {
                    $child->note = $data['note'];
                }

                $child->_syncBuddyTransaction();
                $child->save();
            }
        }
        $this->_handleCategories($data['categories'], true, $updateChildren);
        $this->save();
        return $this;
    }

    /**
     * Creates a new transaction with the given data
     *
     * @param array{
     *          amount: int,
     *          account_id: int,
     *          transaction_date: string,
     *          credit: bool,
     *          note?: string,
     *          categories?: array{
     *              cat_data: array{
     *                  cat_id?: int,
     *                  hex_color: string,
     *                  name: string
     *              },
     *              percent?: float
     *          },
     *          trans_buddy?: bool,
     *          recurring?: bool,
     *          recurring_end_date?: date,
     *          frequency?: string,
     *        } $data
     *
     * @return Transaction The created transaction
     * @throws \InvalidArgumentException
     */
    public function create(array $data): Transaction
    {
        $this->amount = $data['amount'] * 100;
        $this->credit = ($data['credit']) ? true : false;
        $this->account_id = $data['account_id'];
        $this->transaction_date = $data['transaction_date'];
        if (array_key_exists('note', $data)) {
            $this->note = strip_tags($data['note']);
        }

        $this->save();
        if (array_key_exists('trans_buddy', $data) && $data['trans_buddy']) {
            // need to create the buddy transaction first so that any recurring
            // buddy transactions will be created

            if (! array_key_exists('trans_buddy_account', $data)) {
                throw new \InvalidArgumentException('Trying to create a transaction buddy without providing a buddy account');
            }
            $account = Account::find($data['trans_buddy_account']);
            if (! $account) {
                throw new \InvalidArgumentException('Invalid account id for buddy transaction');
            }

            $this->createBuddyTransaction(
                $account,
                $data['trans_buddy_note']
            );
        }

        if (array_key_exists('recurring', $data) && $data['recurring']) {
            if (! array_key_exists('recurring_end_date', $data)) {
                throw new \InvalidArgumentException('Trying to create recurring transactions without providing an end date');
            } else if (! array_key_exists('frequency', $data)) {
                throw new \InvalidArgumentException('Trying to create recurring transactions without providing afrequency');
            }

            $this->createRecurringSeries(
                $data['recurring_end_date'],
                $data['frequency']
            );
        }

        $this->_handleCategories($data['categories'], false, true);
        return $this;
    }

    /**
     * Parses and handles the category data sent from the front end
     *
     * @param array{
     *          cat_data: array{
     *              cat_id?: int,
     *              hex_color: string,
     *              name: string
     *          },
     *          percent: float
     *        } $data
     * @param bool $isUpdate won't touch buddy transactions when true
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    private function _handleCategories(array $data = [], bool $isUpdate = false, bool $updateChildren = false)
    {
        $current_user = Auth::user();
        $catsToSet = [];
        if ($data) {
            foreach ($data as $cat) {
                $catData = $cat['cat_data'];
                $catModel = new Category();
                if (isset($catData['cat_id'])) {
                    $catModel = Category::find($catData['cat_id']);
                    if (! $catModel) {
                        throw new \InvalidArgumentException('Invalid category id');
                    }
                } else {
                    if (! isset($catData['name'])) {
                        throw new \InvalidArgumentException('Missing category name');
                    }
                    $catModel->name = $catData['name'];
                    if (! array_key_exists('hex_color', $catData)) {
                        $catModel->hex_color = $catData['hex_color'];
                    }
                    if (array_key_exists('cat_type_id', $catData)) {
                        $catModel->category_type_id = $catData['cat_type_id'];
                    }
                    $catModel->user_id = $current_user->id;
                    $catModel->save();
                }
                $catsToSet[] = [
                    'category' => $catModel,
                    'percentage' => $cat['percent']
                ];
            }
        }

        $this->_setCategories($catsToSet, $isUpdate, $updateChildren);
    }

    /**
     * Removes existing categories and sets the new categories and percentages for
     * this transaction, its buddy, its children, and its children's buddies.
     * Throws InvalidArgumentException if the sum of the percentages
     * does not equal 100
     *
     * @param array{category: Category, percent: float} $data
     * @param bool $updateBuddies We don't update buddy transactions categories...
     *                            this should only be set when creating a new transaction
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    private function _setCategories(array $data, bool $updateBuddies, bool $updateChildren): bool
    {
        // verify that percentage adds to 1000
        if ($data) {
            $percentSum = 0;
            foreach ($data as $cat) {
                $percentSum += $cat['percentage'];
            }
            if ($percentSum != 100) {
                throw new \InvalidArgumentException('Category percentages do not sum to 100');
            }
        }

        $this->categories()->detach();
        $transBuddy = null;
        if (! $updateBuddies) {
            $transBuddy = $this->buddyTransaction();
        }
        if ($transBuddy) {
            $transBuddy->categories()->detach();
        }
        foreach ($data as $catData) {
            $percent = $catData['percentage'];
            $catModel = $catData['category'];
            $this->categories()
                 ->save(
                     $catModel,
                     [ 'percentage' => $percent * 100 ]
                 );
            if ($transBuddy) {
                $transBuddy
                    ->categories()
                    ->save(
                        $catModel,
                        [ 'percentage' => $percent * 100 ]
                    );
            }
        }

        if ($updateChildren) {
            // first clear all cats from the children
            foreach ($this->children() as $child) {
                $child->categories()->detach();
            }
            // now process the new cats for each child
            foreach ($data as $catData) {
                $percent = $catData['percentage'];
                $catModel = $catData['category'];
                foreach ($this->children() as $child) {
                    $child
                        ->categories()
                        ->save(
                            $catModel,
                            [ 'percentage' => $percent * 100 ]
                        );
                    // sync buddy categories on create only.. note that there's no
                    // need to detach current categories since none should yet exist
                    if (! $updateBuddies) {
                        $childBuddy = $child->buddyTransaction();
                        if ($childBuddy) {
                            $childBuddy
                                ->categories()
                                ->save(
                                    $catModel,
                                    [ 'percentage' => $percent * 100 ]
                                );
                        }
                    }
                }
            }
        }
        return true;
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('percentage');
    }

    /**
     * Fetches transaction data for the current user based on the provided arguments
     *
     * @param array{
     *          start_date?: string,
     *          start_date?: string,
     *          include_to_range?: bool,
     *          filter_account_ids?: array<int>,
     *        } $data
     *
     * @return array
     */
    public static function fetch_transaction_data_for_current_user(array $args) : array
    {
        $start = isset($args['start_date']) ? $args['start_date'] : null;
        $end = isset($args['end_date']) ? $args['end_date'] : null;
        $filter_accounts = isset($args['filter_account_ids']) ? $args['filter_account_ids'] : null;
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

        $transactions_to_range = $current_user->transactions();
        $transactions_in_range = $current_user->transactions()->orderBy('transaction_date', $order_by);
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
                        'parent_transaction_date' => $trans->parentTransaction()?->transaction_date,
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
                // percentages are stored as an integer with two sigfigs
                // so dividing by 100 gets the % value, dividing by another
                // 100 gets the float value suitable for mulitplication
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
                                'hex_color' => $cat->hex_color,
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
                            'hex_color' => $cat_type->hex_color,
                            'total' => $cat_value,
                            'data' => [
                                $cat->id =>  [
                                    'name' => $cat->name,
                                    'value' => $cat_value,
                                    'hex_color' => $cat->hex_color,
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
                        'hex_color' => $cat->hex_color,
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
                'parent_transaction_date' => $trans->parentTransaction()?->transaction_date,
                'categories' => $categories,
            ];
        }

        foreach ($category_type_breakdowns as &$catt_data) {
                $catt_data['total'] = round($catt_data['total'] / 100, 2);
            foreach ($catt_data['data'] as &$cat_data) {
                $cat_data['value'] = round($cat_data['value'] / 100, 2);
            }
        }

        $data['category_type_breakdowns'] = $category_type_breakdowns;
        return $data;
    }

    public function parentTransaction(): ?Transaction
    {
        return Transaction::where('id', '=', $this->parent_id)->first();
    }

    public function buddyTransaction(): ?Transaction
    {
        return Transaction::where('id', '=', $this->buddy_id)->first();
    }

    /**
     * Creates a buddy transaction for the given account.
     *
     * A buddy transaction is a replica of the current transaction but with some properties modified.
     * The buddy transaction will have the opposite credit value, will be associated with the buddy account,
     * and will not have a parent transaction. The categories of the current transaction are also replicated
     * to the buddy transaction with the same percentage.
     *
     * @param Account $buddyAccount The account for which the buddy transaction is to be created.
     * @param string|null $buddyNote An optional note for the buddy transaction.
     *
     * @return Transaction The created buddy transaction.
     */
    public function createBuddyTransaction(Account $buddyAccount, ?string $buddyNote): Transaction
    {
        $buddy = $this->replicate();
        // buddy transactions never have a parent id
        $buddy->parent_id = null;
        $buddy->credit = ! $this->credit;
        $buddy->account_id = $buddyAccount->id;
        $buddy->note = $buddyNote;
        $buddy->buddy_id = $this->id;
        $buddy->save();
        $this->buddy_id = $buddy->id;
        $buddy->categories()->detach();

        foreach ($this->categories as $cat) {
            $percent = $cat->pivot->percentage / 100;
            $buddy->categories()->save($cat, [ 'percentage' => $percent * 100 ]);
        }
        $this->save();
        return $buddy;
    }

    /**
     * Returns the children of this transaction, ordered by transaction date in
     * ascending order. This works when called on a child transaction as well. In
     * which case it will return all transactions with the same parent_id as $this
     * whose transaction date is greater than or equal to $this's
     *
     * @return Collection
     */
    public function children() : Collection
    {
        return Transaction::where('parent_id', '=', $this->parent_id)
            ->where('id', '!=', $this->id)
            ->where('transaction_date', '>=', $this->transaction_date)
            ->orderBy('transaction_date', 'asc')
            ->get();
    }

    /**
     * Creates a series of recurring transactions based on the current transaction
     *
     * @param string|null $endDate   Default is 1 year from the this transaction_date
     * @param string      $frequency One of 'yearly', 'quarterly', 'monthly', 'biweekly'
     *                               Default is 'monthly'
     *
     * @return Collection A collection of the newly created child transactions
     */
    public function createRecurringSeries(
        string $endDate = null,
        string $frequency = 'monthly'
    ): Collection {
        $validFrequency = [
            'yearly' => 'P1Y',
            'quarterly' => 'P3M',
            'monthly' => 'P1M',
            'biweekly' => 'P14D'
        ];
        if (! array_key_exists($frequency, $validFrequency)) {
            throw new \InvalidArgumentException('Invalid frequency');
        }
        $duration = $validFrequency[$frequency];
        $d = new \DateTime($this->transaction_date);
        if ($endDate === null) {
            $endDate = clone $d;
            $endDate = $endDate->add(new \DateInterval('P1Y'))->format('Y-m-d');
        }

        $this->parent_id = $this->id;
        $this->save();

        $nextDate = $d->add(new \DateInterval($duration));
        while ($nextDate->format('Y-m-d') <= $endDate) {
            $this->createChildTransaction($nextDate);
            $nextDate = $nextDate->add(new \DateInterval($duration));
        }
        return $this->children();
    }

    public function createChildTransaction(\DateTime $nextDate): Transaction
    {
        $child = $this->replicate();
        $child->transaction_date = $nextDate->format(\DateTime::ATOM);
        $child->parent_id = $this->id;
        $child->save();
        foreach ($this->categories as $cat) {
            $percent = $cat->pivot->percentage / 100;
            $child->categories()->save($cat, [ 'percentage' => $percent * 100 ]);
        }
        $buddy = $this->buddyTransaction();
        if ($buddy) {
            $parentBuddyAccount = Account::find($buddy->account_id);
            if (! $parentBuddyAccount) {
                throw new \InvalidArgumentException('Invalid account id for buddy transaction');
            }
            $child->createBuddyTransaction(
                $parentBuddyAccount,
                $buddy->note
            );
        }
        return $child;
    }

    /**
     * Syncs the buddy transaction with the current transaction. Calls $this->save()
     * first. Does not sync categories
     */
    private function _syncBuddyTransaction()
    {
        if (! $this->buddy_id) {
            return;
        }
        $this->save();
        $bud = $this->buddyTransaction();
        $bud->amount = $this->amount;
        $bud->credit = ! $this->credit;
        $bud->transaction_date = $this->transaction_date;
        $bud->note = $this->note;
        $bud->save();
    }
}
