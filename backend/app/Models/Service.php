<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'price',
        'unit',
        'duration_hours',
        'active',
        'description',
        'tone',
        'symbol',
    ];

    protected $casts = [
        'price' => 'integer',
        'duration_hours' => 'integer',
        'active' => 'boolean',
    ];
}
