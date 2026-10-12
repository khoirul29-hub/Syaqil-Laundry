<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    protected $table = 'business_settings';

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function allAsArray(): array
    {
        $rows = static::all();
        $res = [
            'name' => 'Syaqil Laundry',
            'address' => '',
            'phone' => '',
            'bank_name' => '',
            'bank_account' => '',
            'bank_holder' => '',
            'qris_image' => '',
            'demo' => false,
        ];

        foreach ($rows as $row) {
            $res[$row->key] = $row->value;
            if ($row->key === 'business_name') $res['name'] = $row->value ?: $res['name'];
            if ($row->key === 'business_address') $res['address'] = $row->value ?: '';
            if ($row->key === 'business_phone') $res['phone'] = $row->value ?: '';
            if ($row->key === 'bank_name') $res['bank_name'] = $row->value ?: '';
            if ($row->key === 'bank_account') $res['bank_account'] = $row->value ?: '';
            if ($row->key === 'bank_holder') $res['bank_holder'] = $row->value ?: '';
            if ($row->key === 'qris_image') $res['qris_image'] = $row->value ?: '';
        }

        return $res;
    }
}
