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
        'path'
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function delete()
    {
        Storage::disk('local')->delete($this->path);
        return parent::delete();
    }
}
