<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceHistory extends Model
{
    use HasFactory;

    protected $table = 'price_histories';
    public $timestamps = false;

    protected $fillable = [
        'service_id',
        'service_name',
        'date',
        'old_price',
        'new_price',
        'actor',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'service_id' => 'integer',
        'date' => 'date:Y-m-d',
        'old_price' => 'integer',
        'new_price' => 'integer',
        'created_at' => 'datetime',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
