<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member')->index();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('organizer_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('club_name')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->text('requirements')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('proposal_status')->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->text('review_feedback')->nullable();
        });

        Schema::table('venues', function (Blueprint $table) {
            $table->string('name')->nullable()->unique();
            $table->string('location')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('availability_status')->default('available')->index();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('requester_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('review_feedback')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->index(['venue_id', 'status', 'starts_at', 'ends_at'], 'reservations_conflict_lookup');
        });

        Schema::create('event_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision');
            $table->text('feedback')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_reviews');

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_conflict_lookup');
            $table->dropConstrainedForeignId('reviewer_id');
            $table->dropConstrainedForeignId('requester_id');
            $table->dropConstrainedForeignId('venue_id');
            $table->dropConstrainedForeignId('event_id');
            $table->dropColumn(['starts_at', 'ends_at', 'status', 'review_feedback', 'reviewed_at']);
        });

        Schema::table('venues', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropIndex(['availability_status']);
            $table->dropColumn(['name', 'location', 'capacity', 'availability_status']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['proposal_status']);
            $table->dropConstrainedForeignId('organizer_id');
            $table->dropColumn([
                'club_name', 'title', 'description', 'objectives', 'requirements',
                'starts_at', 'ends_at', 'proposal_status', 'submitted_at', 'review_feedback',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
