<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Initial Users (Cashier & Owner)
        if (!DB::table('users')->where('username', 'owner')->exists()) {
            DB::table('users')->insert([
                'name' => 'Desi Gusrianita',
                'username' => 'owner',
                'password_hash' => Hash::make('OwnerSyaqil!2026'),
                'role' => 'owner',
                'phone' => '081234567890',
                'shift' => 'Fleksibel',
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        if (!DB::table('users')->where('username', 'kasir')->exists()) {
            DB::table('users')->insert([
                'name' => 'Kasir Syaqil',
                'username' => 'kasir',
                'password_hash' => Hash::make('KasirSyaqil!2026'),
                'role' => 'cashier',
                'phone' => '082123456789',
                'shift' => 'Pagi',
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // 2. Initial Services
        $defaultServices = [
            [
                'id' => 1,
                'name' => 'Reguler',
                'price' => 6000,
                'unit' => 'kg',
                'duration_hours' => null,
                'active' => 1,
                'description' => 'Laundry harian',
                'tone' => 'blue',
                'symbol' => 'shirt'
            ],
            [
                'id' => 2,
                'name' => 'Ekspres 1 Hari',
                'price' => 10000,
                'unit' => 'kg',
                'duration_hours' => 24,
                'active' => 1,
                'description' => 'Selesai maksimal 1 hari',
                'tone' => 'amber',
                'symbol' => 'zap'
            ],
            [
                'id' => 3,
                'name' => 'Ekspres Jam',
                'price' => 14000,
                'unit' => 'kg',
                'duration_hours' => null,
                'active' => 1,
                'description' => 'Prioritas paling cepat',
                'tone' => 'red',
                'symbol' => 'timer'
            ]
        ];

        foreach ($defaultServices as $s) {
            $existing = DB::table('services')->where('id', $s['id'])->first();
            if (!$existing) {
                DB::table('services')->insert($s);
            } else {
                DB::table('services')->where('id', $s['id'])->update([
                    'description' => $s['description'],
                    'tone' => $s['tone'],
                    'symbol' => $s['symbol']
                ]);
            }
        }

        // 3. Business Settings
        $settings = [
            ['key' => 'business_name', 'value' => 'Syaqil Laundry', 'group' => 'general'],
            ['key' => 'business_address', 'value' => 'Jl. Raya Laundry No. 10', 'group' => 'general'],
            ['key' => 'business_phone', 'value' => '081234567890', 'group' => 'general'],
            ['key' => 'bank_name', 'value' => '', 'group' => 'payment'],
            ['key' => 'bank_account', 'value' => '', 'group' => 'payment'],
            ['key' => 'bank_holder', 'value' => '', 'group' => 'payment'],
            ['key' => 'qris_image', 'value' => '', 'group' => 'payment'],
            ['key' => 'whatsapp_provider', 'value' => 'mock', 'group' => 'whatsapp'],
            ['key' => 'whatsapp_api_key', 'value' => '', 'group' => 'whatsapp'],
            ['key' => 'whatsapp_sender_number', 'value' => '', 'group' => 'whatsapp'],
        ];

        foreach ($settings as $item) {
            if (!DB::table('business_settings')->where('key', $item['key'])->exists()) {
                DB::table('business_settings')->insert(array_merge($item, [
                    'created_at' => now(),
                    'updated_at' => now()
                ]));
            }
        }
    }
}
