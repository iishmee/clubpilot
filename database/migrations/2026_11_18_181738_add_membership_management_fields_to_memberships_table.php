<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->foreignId('club_id')
                ->after('id')
                ->constrained('clubs')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->after('club_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('invited_by')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status', 20)
                ->default('pending')
                ->after('invited_by');

            $table->text('application_message')->nullable();
            $table->text('status_reason')->nullable();

            $table->timestamp('applied_at')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->unique(
                ['club_id', 'user_id'],
                'memberships_club_user_unique'
            );

            $table->index(['club_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropUnique('memberships_club_user_unique');

            $table->dropIndex(['club_id', 'status']);
            $table->dropIndex(['user_id', 'status']);

            $table->dropConstrainedForeignId('invited_by');
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('club_id');

            $table->dropColumn([
                'status',
                'application_message',
                'status_reason',
                'applied_at',
                'invited_at',
                'joined_at',
                'ended_at',
            ]);
        });
    }
};

