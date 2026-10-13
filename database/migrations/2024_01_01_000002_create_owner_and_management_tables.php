<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->date('date');
                $table->string('category', 50); // Operasional, Karyawan, Peralatan, Lainnya
                $table->string('description', 255);
                $table->unsignedInteger('amount');
                $table->string('method', 30)->default('Tunai'); // Tunai, Transfer, E-Wallet, QRIS
                $table->string('actor', 100);
                $table->timestamps();
                $table->index(['date', 'category']);
            });
        }

        if (!Schema::hasTable('price_histories')) {
            Schema::create('price_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('service_id');
                $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
                $table->string('service_name', 80);
                $table->date('date');
                $table->unsignedInteger('old_price');
                $table->unsignedInteger('new_price');
                $table->string('actor', 100);
                $table->string('reason', 255)->default('');
                $table->dateTime('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('order_corrections')) {
            Schema::create('order_corrections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->string('type', 50); // Pembatalan, Koreksi Berat, Penyesuaian Harga, Koreksi Layanan
                $table->string('reason', 255);
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->dateTime('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('account_activities')) {
            Schema::create('account_activities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->string('name', 100);
                $table->string('type', 50); // Login, Logout, Update Data, Reset Password, Status Diubah
                $table->string('detail', 255);
                $table->string('ip_address', 45)->nullable();
                $table->dateTime('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('business_settings')) {
            Schema::create('business_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 80)->unique();
                $table->text('value')->nullable();
                $table->string('group', 40)->default('general');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('whatsapp_logs')) {
            Schema::create('whatsapp_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
                $table->string('phone', 25);
                $table->string('type', 40); // receipt, status_ready, notification
                $table->text('payload')->nullable();
                $table->string('status', 30)->default('pending'); // pending, sent, failed
                $table->text('error_message')->nullable();
                $table->dateTime('sent_at')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->index(['status', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        // Safe down
    }
};
