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
            if (!Schema::hasColumn('orders', 'perfume')) {
                $table->string('perfume', 100)->nullable()->after('notes');
            }
            if (!Schema::hasColumn('orders', 'public_token')) {
                $table->string('public_token', 64)->nullable()->unique()->after('perfume');
            }
        });

        // Pastikan order yang sudah ada memiliki public_token
        $existingOrders = DB::table('orders')->whereNull('public_token')->get();
        foreach ($existingOrders as $order) {
            DB::table('orders')->where('id', $order->id)->update([
                'public_token' => bin2hex(random_bytes(16)),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'public_token')) {
                $table->dropColumn('public_token');
            }
            if (Schema::hasColumn('orders', 'perfume')) {
                $table->dropColumn('perfume');
            }
        });
    }
};
