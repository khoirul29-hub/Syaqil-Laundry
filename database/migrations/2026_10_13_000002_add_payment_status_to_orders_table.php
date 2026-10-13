<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status', 20)->default('paid')->after('change_amount');
            }
        });

        // Ubah enum method menjadi string agar mendukung opsi bayar nanti ('later')
        try {
            DB::statement("ALTER TABLE `orders` MODIFY `method` VARCHAR(20) NOT NULL DEFAULT 'cash'");
        } catch (\Throwable $e) {
            // Abaikan jika database engine SQLite saat testing lokal
        }

        // Set semua pesanan lama yang sudah ada menjadi 'paid'
        DB::table('orders')->whereNull('payment_status')->orWhere('payment_status', '')->update([
            'payment_status' => 'paid',
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
    }
};
