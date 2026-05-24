<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TransactionImage extends Model
{
    use HasFactory;
    protected $fillable = [
        'transaction_id',
        'name',
        'path',
        'ai_analysis'
    ];

    protected function casts(): array
    {
        return [
            'ai_analysis' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (TransactionImage $image) {
            Storage::disk('local')->delete($image->path);
        });
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
