<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('id')->constrained('units')->nullOnDelete();
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('role', 40)->default('employee')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
        });

        Schema::create('archive_classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('archive_classifications')->nullOnDelete();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->unsignedSmallInteger('active_retention_years')->default(2);
            $table->unsignedSmallInteger('inactive_retention_years')->default(3);
            $table->string('final_action', 30)->default('REVIEW');
            $table->string('default_security_level', 40)->default('INTERNAL');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('archive_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('code', 100)->unique();
            $table->string('building')->nullable();
            $table->string('room')->nullable();
            $table->string('cabinet')->nullable();
            $table->string('rack')->nullable();
            $table->string('box')->nullable();
            $table->string('folder')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units');
            $table->foreignId('classification_id')->constrained('archive_classifications');
            $table->foreignId('location_id')->nullable()->constrained('archive_locations')->nullOnDelete();
            $table->string('archive_number', 160)->unique();
            $table->string('agenda_number', 100)->nullable()->index();
            $table->string('document_number', 150)->nullable()->index();
            $table->string('type', 20)->index();
            $table->string('title');
            $table->text('subject')->nullable();
            $table->string('sender')->nullable();
            $table->string('recipient')->nullable();
            $table->date('document_date')->nullable();
            $table->date('received_date')->nullable();
            $table->string('security_level', 40)->default('INTERNAL');
            $table->string('status', 30)->default('PENDING');
            $table->date('retention_start_date')->nullable();
            $table->unsignedSmallInteger('active_retention_years')->default(2);
            $table->unsignedSmallInteger('inactive_retention_years')->default(3);
            $table->string('final_action', 30)->default('REVIEW');
            $table->text('keywords')->nullable();
            $table->text('physical_location_note')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['unit_id', 'type', 'status']);
        });

        Schema::create('archive_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_id')->constrained('archives')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('file_path', 500);
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('checksum_sha256', 64);
            $table->text('change_note')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->boolean('is_final')->default(false);
            $table->timestamps();
            $table->unique(['archive_id', 'version_number']);
        });

        Schema::create('dispositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_id')->constrained('archives')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('assigned_to')->constrained('users');
            $table->text('instruction');
            $table->string('priority', 20)->default('NORMAL');
            $table->string('status', 30)->default('UNREAD');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('follow_up_note')->nullable();
            $table->timestamps();
        });

        Schema::create('archive_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_id')->constrained('archives');
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->string('status', 30)->default('REQUESTED');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('borrowed_at')->nullable();
            $table->timestamp('due_at');
            $table->timestamp('returned_at')->nullable();
            $table->text('return_condition')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('archive_loans');
        Schema::dropIfExists('dispositions');
        Schema::dropIfExists('archive_versions');
        Schema::dropIfExists('archives');
        Schema::dropIfExists('archive_locations');
        Schema::dropIfExists('archive_classifications');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['unit_id', 'username', 'role', 'is_active']);
        });
        Schema::dropIfExists('units');
    }
};
