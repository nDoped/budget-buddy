<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryType extends Model
{
    use HasFactory;
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }
    /**
     * Get the user that owns the account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
