<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappLog extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_logs';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'phone',
        'type',
        'payload',
        'status',
        'error_message',
        'sent_at',
        'created_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
