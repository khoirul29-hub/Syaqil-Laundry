<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';
    public $timestamps = false;

    protected $fillable = [
        'number',
        'request_key',
        'request_hash',
        'customer_id',
        'cashier_id',
        'customer_name',
        'customer_phone',
        'subtotal',
        'discount_percent',
        'discount',
        'total',
        'method',
        'paid',
        'change_amount',
        'status',
        'notes',
        'due_at',
        'created_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'cashier_id' => 'integer',
        'subtotal' => 'integer',
        'discount_percent' => 'float',
        'discount' => 'integer',
        'total' => 'integer',
        'paid' => 'integer',
        'change_amount' => 'integer',
        'due_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id')->orderBy('id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(StatusHistory::class, 'order_id')->orderBy('id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'order_id')->orderByDesc('id');
    }

    public function latestNotification(): HasOne
    {
        return $this->hasOne(Notification::class, 'order_id')->latestOfMany();
    }

    /**
     * Format array order sesuai kontrak frontend Syaqil Laundry.
     */
    public function toFrontendArray(): array
    {
        $arr = $this->toArray();
        $arr['cashier_name'] = $this->cashier ? $this->cashier->name : null;
        $arr['items'] = $this->items->map(function ($item) {
            return [
                'service_id' => (int)$item->service_id,
                'service_name' => $item->service_name,
                'unit' => $item->unit,
                'price' => (int)$item->price,
                'quantity' => (float)$item->quantity,
                'category' => $item->category,
                'subtotal' => (int)$item->subtotal,
            ];
        })->values()->all();

        $arr['history'] = $this->history->map(function ($h) {
            return [
                'status' => $h->status,
                'created_at' => $h->created_at ? $h->created_at->format('Y-m-d H:i:s') : null,
                'user_name' => $h->user ? $h->user->name : null,
            ];
        })->values()->all();

        $latestNotif = $this->latestNotification;
        $arr['notification'] = $latestNotif ? [
            'status' => $latestNotif->status,
            'message' => $latestNotif->message,
            'created_at' => $latestNotif->created_at ? $latestNotif->created_at->format('Y-m-d H:i:s') : null,
        ] : null;

        return $arr;
    }
}
