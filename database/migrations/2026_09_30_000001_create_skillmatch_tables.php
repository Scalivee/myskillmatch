<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('volunteer')->index();
            $t->boolean('active')->default(true);
            $t->string('city')->nullable();
            $t->text('bio')->nullable();
            $t->json('availability')->nullable();
        });
        Schema::create('skills', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->timestamps();
        });
        Schema::create('skill_user', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('skill_id')->constrained()->restrictOnDelete();
            $t->primary(['user_id', 'skill_id']);
        });
        Schema::create('packages', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->unsignedInteger('price')->nullable();
            $t->unsignedInteger('positions_limit');
            $t->unsignedInteger('applications_limit');
            $t->unsignedInteger('days_limit');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organizer_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('package_id')->constrained()->restrictOnDelete();
            $t->string('title');
            $t->string('category');
            $t->string('city');
            $t->string('venue');
            $t->text('description');
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->dateTime('registration_opens_at');
            $t->dateTime('deadline');
            $t->string('moderation')->default('draft')->index();
            $t->string('publication')->default('unpublished');
            $t->string('lifecycle')->default('upcoming');
            $t->text('review_note')->nullable();
            $t->json('package_snapshot')->nullable();
            $t->timestamps();
        });
        Schema::create('positions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->unsignedInteger('quota');
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->json('skill_ids');
            $t->json('required_skill_ids');
            $t->boolean('full_availability')->default(false);
            $t->boolean('same_city')->default(false);
            $t->boolean('cv_required')->default(true);
            $t->json('questions');
            $t->unsignedInteger('duration_minutes')->default(15);
            $t->timestamps();
        });
        Schema::create('applications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('volunteer_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('event_id')->constrained()->restrictOnDelete();
            $t->foreignId('position_id')->constrained()->restrictOnDelete();
            $t->string('status')->default('draft')->index();
            $t->json('snapshot')->nullable();
            $t->json('screening')->nullable();
            $t->decimal('skill_score', 5, 2)->nullable();
            $t->decimal('availability_score', 5, 2)->nullable();
            $t->decimal('location_score', 5, 2)->nullable();
            $t->decimal('match_score', 5, 2)->nullable();
            $t->decimal('assessment_score', 5, 2)->nullable();
            $t->text('decision_note')->nullable();
            $t->string('attendance')->default('not_recorded');
            $t->dateTime('submitted_at')->nullable();
            $t->timestamps();
            $t->unique(['volunteer_id', 'event_id']);
        });
        Schema::create('documents', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('application_id')->constrained()->cascadeOnDelete();
            $t->foreignId('uploader_id')->constrained('users')->restrictOnDelete();
            $t->string('kind');
            $t->string('path')->unique();
            $t->string('original_name');
            $t->string('mime');
            $t->unsignedBigInteger('size');
            $t->string('checksum', 64);
            $t->string('status')->default('quarantined')->index();
            $t->timestamps();
        });
        Schema::create('attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->unique()->constrained()->restrictOnDelete();
            $t->json('questions');
            $t->json('answers')->nullable();
            $t->dateTime('started_at');
            $t->dateTime('expires_at');
            $t->dateTime('finished_at')->nullable();
            $t->timestamps();
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('reference')->unique();
            $t->unsignedInteger('amount');
            $t->json('package_snapshot');
            $t->string('status')->default('pending');
            $t->string('checkout_url', 1000)->nullable();
            $t->dateTime('paid_at')->nullable();
            $t->timestamps();
        });
        Schema::create('notices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('message');
            $t->string('dedupe')->unique();
            $t->dateTime('read_at')->nullable();
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action');
            $t->string('subject');
            $t->json('meta')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'notices', 'orders', 'attempts', 'documents', 'applications', 'positions', 'events', 'packages', 'skill_user', 'skills'] as $table) {
            Schema::dropIfExists($table);
        }Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'active', 'city', 'bio', 'availability']));
    }
};
