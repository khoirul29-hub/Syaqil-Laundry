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
        'total',
        'method',
        'paid',
        'change_amount',
        'payment_status',
        'status',
        'notes',
        'public_token',
        'due_at',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->public_token)) {
                $order->public_token = bin2hex(random_bytes(16));
            }
        });
    }

    protected $casts = [
        'customer_id' => 'integer',
        'cashier_id' => 'integer',
        'subtotal' => 'integer',
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
        $arr['payment_status'] = $this->payment_status ?: ($this->method === 'later' ? 'unpaid' : 'paid');
        $arr['public_token'] = $this->public_token;
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

        $arr['public_url'] = $this->public_token
            ? app(\App\Services\WhatsAppNotificationService::class)->getPublicReceiptUrl($this->public_token)
            : null;

        return $arr;
    }

    /**
     * Format data nota publik khusus untuk pelanggan via tautan nota digital.
     * Hanya mengekspos informasi yang diizinkan untuk pelanggan, tanpa membuka struktur internal
     * seperti ID database internal, identitas kasir, hash idempotensi, atau log gateway.
     */
    public function toPublicReceiptArray(): array
    {
        return [
            'number' => $this->number,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'status' => $this->status,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'due_at' => $this->due_at ? $this->due_at->format('Y-m-d H:i:s') : null,
            'subtotal' => (int)$this->subtotal,
            'total' => (int)$this->total,
            'method' => $this->method,
            'paid' => (int)$this->paid,
            'change_amount' => (int)$this->change_amount,
            'payment_status' => $this->payment_status ?: ($this->method === 'later' ? 'unpaid' : 'paid'),
            'notes' => $this->notes,
            'public_token' => $this->public_token,
            'public_url' => $this->public_token
                ? app(\App\Services\WhatsAppNotificationService::class)->getPublicReceiptUrl($this->public_token)
                : null,
            'items' => $this->items->map(function ($item) {
                return [
                    'service_name' => $item->service_name,
                    'unit' => $item->unit,
                    'price' => (int)$item->price,
                    'quantity' => (float)$item->quantity,
                    'category' => $item->category,
                    'subtotal' => (int)$item->subtotal,
                ];
            })->values()->all(),
            'history' => $this->history->map(function ($h) {
                return [
                    'status' => $h->status,
                    'created_at' => $h->created_at ? $h->created_at->format('Y-m-d H:i:s') : null,
                ];
            })->values()->all(),
        ];
    }
}
