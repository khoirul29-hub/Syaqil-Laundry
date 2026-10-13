<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCorrection extends Model
{
    use HasFactory;

    protected $table = 'order_corrections';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'user_id',
        'type',
        'reason',
        'old_values',
        'new_values',
        'created_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'user_id' => 'integer',
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
