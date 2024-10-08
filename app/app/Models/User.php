<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the account types for this user
     */
    public function accountTypes() : HasMany
    {
        return $this->hasMany(AccountType::class);
    }

    /**
     * Get the accounts for this user
     */
    public function accounts() : HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Get the transaction categories for this user
     */
    public function categories() : HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get the transaction categories for this user
     */
    public function categoryTypes() : HasMany
    {
        return $this->hasMany(CategoryType::class);
    }

    /**
     * Get the transactions for this user
     */
    public function transactions() : HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, Account::class);
    }

    /**
     * Fetches transaction data for the current user based on the provided arguments
     *
     * @param string|null $start_date
     * @param string|null $end_date
     * @param string|null $orderBy
     * @param bool|null $includeToRange
     * @param array|null $ filterAccountIds
     * @param bool|null $includeCategoryTypeBreakdowns
     *
     * @return array
     */
    public function fetchTransactionData(
        $start = null,
        $end = null,
        string $orderBy = null,
        bool $includeToRange = null,
        array $filterAccountIds = null,
        bool $includeCategoryTypeBreakdowns = null
    ) : array {
        $data = [
            'transactions_in_range' => [],
        ];
        if ($includeToRange) {
            $data['transactions_to_range'] = [];
        }

        if ($orderBy && ! in_array($orderBy, ['asc', 'desc'])) {
            return $data;
        }

        $transactions_in_range
            = $this->transactions()
                   ->orderBy('transaction_date', $orderBy)
                   ->orderBy('buddy_id', $orderBy);

        if ($filterAccountIds) {
            $transactions_in_range = $transactions_in_range->whereIn('account_id', $filterAccountIds);
        }

        if ($start) {
            $transactions_in_range = $transactions_in_range->where('transaction_date', '>=', $start);
            if ($includeToRange) {
                $transactions_to_range = $this->transactions()->where('transaction_date', '<', $start);
                if ($filterAccountIds) {
                    $transactions_to_range = $transactions_to_range->whereIn('account_id', $filterAccountIds);
                }
                foreach ($transactions_to_range->get() as $trans) {
                    $acct = $trans->account;
                    $type = AccountType::find($acct->type_id);
                    $data['transactions_to_range'][] = [
                        'amount_raw' => $trans->amount,
                        'account_id' => strval($acct->id),
                        'asset' => ($trans->credit) ? true : false,
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
            $type = $acct->accountType;
            $categories = [];
            foreach ($trans->categories as $cat) {
                $percent = $cat->pivot->percentage;
                // percentages are stored as an integer with two sigfigs
                // so dividing by 100 gets the % value, dividing by another
                // 100 gets the float value suitable for mulitplication
                $cat_value = $trans->amount * ($percent / 10000);

                if ($includeCategoryTypeBreakdowns && $cat->category_type_id) {
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

                $catt = $cat->categoryType;
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

            $existing_images =  $trans->transactionImages;
            foreach ($existing_images as &$transImg) {
                $allowedMimeTypes = [ 'image/png', 'image/jpeg' ];
                if (in_array(Storage::mimeType($transImg->path), $allowedMimeTypes)) {
                    $path = storage_path() . '/app/' . $transImg->path;
                    $img = new \Imagick($path);
                    $img->thumbnailImage(100, 0);
                    $imgType = pathinfo($path, PATHINFO_EXTENSION);
                    $base64 = 'data:image/' . $imgType . ';base64,' . base64_encode($img->getImageBlob());
                    $transImg['thumbnail'] = $base64;
                }
            }
            $data['transactions_in_range'][] = [
                'amount_raw' => $trans->amount,
                'amount' => strval($trans->amount / 100),
                'account_id' => strval($acct->id),
                'account' => $acct->name,
                'account_type' => $type->name,
                'asset_text' => ($trans->credit) ? 'Credit' : 'Debit',
                'asset' => ($trans->credit) ? true : false,
                'transaction_date' => $trans->transaction_date,
                'created_at' => $trans->created_at,
                'expand' => true,
                'note' => $trans->note,
                'bank_identifier' => $trans->bank_identifier,
                'id' => $trans->id,
                'buddy_id' => $trans->buddy_id,
                'parent_id' => $trans->parent_id,
                'is_last_child' => $trans->isLastChild(),
                'existing_images' =>  $trans->transactionImages,
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

        if ($includeCategoryTypeBreakdowns) {
            $data['category_type_breakdowns'] = $category_type_breakdowns;
        }
        return $data;
    }

    /*
     * @return array<int, array<string, mixed>>
     */
    public function fetchAccountData() : array
    {
        $ret = [];
        foreach ($this->accounts as $acct) {
            $ret[$acct->id] = [
                'name' => $acct->name,
                'init_balance' => $acct->initial_balance / 100,
                'init_balance_raw' => $acct->initial_balance,
                'asset' => $acct->accountType->asset,
                'url' => $acct->url,
                /* 'in_range_net_growth' => 0, */
                /* 'pre_range_net_growth' => 0, */
                /* 'expand' => true, */
                /* 'daily_net_growths' => [], */
                /* 'end_balance' => 0 */
            ];
        }
        return $ret;
    }
}
