<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('username', 60)->unique();
                $table->string('password_hash', 255);
                $table->enum('role', ['cashier', 'owner'])->default('cashier');
                $table->string('phone', 20)->nullable();
                $table->string('shift', 30)->nullable()->default('Pagi');
                $table->boolean('active')->default(true);
                $table->dateTime('last_login_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'phone')) $table->string('phone', 20)->nullable();
                if (!Schema::hasColumn('users', 'shift')) $table->string('shift', 30)->nullable()->default('Pagi');
                if (!Schema::hasColumn('users', 'last_login_at')) $table->dateTime('last_login_at')->nullable();
                if (!Schema::hasColumn('users', 'remember_token')) $table->rememberToken();
                if (!Schema::hasColumn('users', 'updated_at')) $table->timestamp('updated_at')->nullable();
                if (!Schema::hasColumn('users', 'created_at')) $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('phone', 20)->unique();
                $table->string('address', 300)->default('');
                $table->string('notes', 1000)->default('');
                $table->dateTime('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('services')) {
            Schema::create('services', function (Blueprint $table) {
                $table->id();
                $table->string('name', 80);
                $table->unsignedInteger('price');
                $table->string('unit', 16);
                $table->unsignedInteger('duration_hours')->nullable();
                $table->boolean('active')->default(true);
                $table->string('description', 255)->nullable();
                $table->string('tone', 20)->nullable()->default('blue');
                $table->string('symbol', 30)->nullable()->default('shirt');
            });
        } else {
            Schema::table('services', function (Blueprint $table) {
                if (!Schema::hasColumn('services', 'description')) $table->string('description', 255)->nullable();
                if (!Schema::hasColumn('services', 'tone')) $table->string('tone', 20)->nullable()->default('blue');
                if (!Schema::hasColumn('services', 'symbol')) $table->string('symbol', 30)->nullable()->default('shirt');
            });
        }

        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('number', 40)->nullable()->unique();
                $table->string('request_key', 80)->unique();
                $table->char('request_hash', 64)->nullable();
                $table->foreignId('customer_id')->constrained('customers');
                $table->foreignId('cashier_id')->constrained('users');
                $table->string('customer_name', 100);
                $table->string('customer_phone', 20);
                $table->unsignedInteger('subtotal');
                $table->decimal('discount_percent', 5, 2)->nullable();
                $table->unsignedInteger('discount')->default(0);
                $table->unsignedInteger('total');
                $table->enum('method', ['cash', 'qris', 'transfer']);
                $table->unsignedInteger('paid');
                $table->unsignedInteger('change_amount');
                $table->enum('status', ['Diterima', 'Diproses', 'Selesai', 'Diambil'])->default('Diterima');
                $table->string('notes', 1000)->default('');
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('due_at')->nullable();
                $table->index(['status', 'created_at']);
            });
        }

        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders');
                $table->foreignId('service_id')->constrained('services');
                $table->string('service_name', 80);
                $table->string('unit', 16);
                $table->unsignedInteger('price');
                $table->decimal('quantity', 6, 2);
                $table->string('category', 40);
                $table->unsignedInteger('subtotal');
            });
        }

        if (!Schema::hasTable('status_history')) {
            Schema::create('status_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders');
                $table->string('status', 20);
                $table->foreignId('user_id')->constrained('users');
                $table->dateTime('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders');
                $table->string('status', 24);
                $table->string('message', 300);
                $table->dateTime('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('login_attempts')) {
            Schema::create('login_attempts', function (Blueprint $table) {
                $table->char('ip_hash', 64)->primary();
                $table->integer('attempts')->default(0);
                $table->dateTime('last_attempt');
            });
        }
    }

    public function down(): void
    {
        // Safe down: do not delete production data tables unconditionally
    }
};
