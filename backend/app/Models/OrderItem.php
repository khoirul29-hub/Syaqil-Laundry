<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'service_id',
        'service_name',
        'unit',
        'price',
        'quantity',
        'category',
        'subtotal',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'service_id' => 'integer',
        'price' => 'integer',
        'quantity' => 'float',
        'subtotal' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
