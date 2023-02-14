<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    /**
    * Get the comments for the blog post.
    */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
