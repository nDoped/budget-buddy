<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    use HasFactory;
    /**
     * Get the user that owns the account type
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
