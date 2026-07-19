<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('phone',30)->nullable()->after('email');
            $table->string('role',30)->default('buyer')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id(); $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name'); $table->string('slug')->unique(); $table->text('description')->nullable();
            $table->string('phone',30)->nullable(); $table->text('address')->nullable();
            $table->decimal('latitude',10,7)->nullable(); $table->decimal('longitude',10,7)->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id(); $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name'); $table->string('sku',100)->nullable()->index(); $table->text('description')->nullable();
            $table->string('category',120)->nullable()->index(); $table->decimal('price',15,2); $table->unsignedInteger('stock')->default(0);
            $table->string('unit',30)->default('pcs'); $table->string('image_path')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id(); $table->string('order_number')->unique();
            $table->foreignId('buyer_id')->constrained('users'); $table->foreignId('store_id')->constrained();
            $table->foreignId('courier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status',40)->default('PLACED')->index(); $table->string('payment_method',40); $table->string('payment_status',40)->default('UNPAID');
            $table->decimal('subtotal',15,2); $table->decimal('delivery_fee',15,2)->default(0); $table->decimal('service_fee',15,2)->default(0); $table->decimal('total',15,2);
            $table->string('recipient_name'); $table->string('recipient_phone',30); $table->text('delivery_address');
            $table->decimal('latitude',10,7)->nullable(); $table->decimal('longitude',10,7)->nullable();
            $table->text('buyer_note')->nullable(); $table->text('merchant_note')->nullable(); $table->text('cancel_reason')->nullable(); $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name'); $table->decimal('price',15,2); $table->unsignedInteger('quantity'); $table->decimal('subtotal',15,2); $table->timestamps();
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained('users')->nullOnDelete(); $table->string('status',40)->default('WAITING_MERCHANT');
            $table->timestamp('accepted_at')->nullable(); $table->timestamp('picked_up_at')->nullable(); $table->timestamp('delivered_at')->nullable();
            $table->string('proof_path')->nullable(); $table->text('courier_note')->nullable(); $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries'); Schema::dropIfExists('order_items'); Schema::dropIfExists('orders'); Schema::dropIfExists('products'); Schema::dropIfExists('stores');
        Schema::table('users', function (Blueprint $table) { $table->dropColumn(['username','phone','role','is_active']); });
    }
};
