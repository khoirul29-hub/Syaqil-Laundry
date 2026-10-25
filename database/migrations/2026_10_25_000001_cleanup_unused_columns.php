<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan pembersihan kolom yang tidak lagi terpakai:
     * 1. orders: discount, discount_percent, perfume
     * 2. expenses: method
     * 3. customers: address
     */
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $columnsToDrop = [];
                if (Schema::hasColumn('orders', 'discount')) {
                    $columnsToDrop[] = 'discount';
                }
                if (Schema::hasColumn('orders', 'discount_percent')) {
                    $columnsToDrop[] = 'discount_percent';
                }
                if (Schema::hasColumn('orders', 'perfume')) {
                    $columnsToDrop[] = 'perfume';
                }
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }

        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                if (Schema::hasColumn('expenses', 'method')) {
                    $table->dropColumn('method');
                }
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (Schema::hasColumn('customers', 'address')) {
                    $table->dropColumn('address');
                }
            });
        }
    }

    /**
     * Kembalikan kolom jika rollback.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'discount_percent')) {
                    $table->decimal('discount_percent', 5, 2)->nullable();
                }
                if (!Schema::hasColumn('orders', 'discount')) {
                    $table->unsignedInteger('discount')->default(0);
                }
                if (!Schema::hasColumn('orders', 'perfume')) {
                    $table->string('perfume', 100)->nullable();
                }
            });
        }

        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                if (!Schema::hasColumn('expenses', 'method')) {
                    $table->string('method', 30)->default('Tunai');
                }
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (!Schema::hasColumn('customers', 'address')) {
                    $table->string('address', 300)->default('');
                }
            });
        }
    }
};
