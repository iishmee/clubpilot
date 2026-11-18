<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('volunteer_tasks', function (Blueprint $table) {
            $table->foreignId('volunteer_opportunity_id')->nullable()->constrained('volunteer_opportunities')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('todo')->index();
            $table->dateTime('due_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('volunteer_tasks', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropConstrainedForeignId('volunteer_opportunity_id');
            $table->dropColumn(['title', 'description', 'status', 'due_at']);
        });
    }
};
