<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * Account
 *
 * @mixin \Eloquent\Model
 */
class Account extends Model
{
    use HasFactory;

    /**
    * Get transactions for this account
    * @return \Illuminate\Database\Eloquent\Relations\HasMany
    */
    public function transactions() : HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the user for this acocunt
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    /**
     * Get the accountType for this account
     */
    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class, 'type_id');
    }
}
