
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('rating');
            $table->text('comments')->nullable();

            // One feedback submission per user per event
            $table->unique(['event_id', 'user_id']);
        });

        Schema::table('improvement_actions', function (Blueprint $table) {
            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('feedback_id')
                ->nullable()
                ->constrained('feedback')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('title');
            $table->text('description');
            $table->string('status')->default('pending')->index();
            $table->date('due_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('improvement_actions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('feedback_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('event_id');

            $table->dropIndex(['status']);
            $table->dropColumn([
                'title',
                'description',
                'status',
                'due_date',
            ]);
        });

        Schema::table('feedback', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'user_id']);
            $table->dropConstrainedForeignId('event_id');
            $table->dropConstrainedForeignId('user_id');

            $table->dropColumn(['rating', 'comments']);
        });
    }
};
