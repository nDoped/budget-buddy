<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    use HasFactory;
    protected $table = 'categories';

    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class);
    }
    public function categoryType(): BelongsTo
    {
        return $this->belongsTo(CategoryType::class);
    }
}
