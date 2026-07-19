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
            $table->string('role', 40)->default('operator')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
        });

        Schema::create('wa_devices', function (Blueprint $table) {
            $table->id();
            $table->string('session_key', 80)->unique();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('status', 40)->default('DISCONNECTED');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wa_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone', 30)->unique();
            $table->string('label')->nullable();
            $table->boolean('consent')->default(false);
            $table->timestamp('consent_at')->nullable();
            $table->boolean('opted_out')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('wa_messages', function (Blueprint $table) {
            $table->id();
            $table->string('session_key', 80);
            $table->string('wa_message_id')->nullable()->index();
            $table->string('phone', 30)->index();
            $table->string('direction', 10);
            $table->string('type', 30)->default('TEXT');
            $table->text('body')->nullable();
            $table->string('status', 30)->default('QUEUED');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('wa_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('message');
            $table->string('status', 30)->default('DRAFT');
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedInteger('delay_seconds')->default(8);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_campaigns');
        Schema::dropIfExists('wa_messages');
        Schema::dropIfExists('wa_contacts');
        Schema::dropIfExists('wa_devices');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role', 'is_active']);
        });
    }
};
