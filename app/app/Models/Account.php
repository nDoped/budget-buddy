<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * Account
 *
 * @mixin \Eloquent\Model
 */
class Account extends Model
{
    use HasFactory;

    /**
    * Get the comments for the blog post.
    * @return \Illuminate\Database\Eloquent\Relations\HasMany
    */
    public function transactions() : \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
