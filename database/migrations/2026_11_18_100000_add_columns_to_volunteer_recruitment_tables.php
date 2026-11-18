<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('volunteer_opportunities', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('slots_needed')->default(1);
            $table->string('status')->default('open')->index();
        });

        Schema::table('volunteer_applications', function (Blueprint $table) {
            $table->foreignId('volunteer_opportunity_id')->nullable()->constrained('volunteer_opportunities')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('reviewed_at')->nullable();
            // One application per volunteer per opportunity.
            $table->unique(['volunteer_opportunity_id', 'user_id'], 'volunteer_applications_unique');
        });
    }

    public function down(): void
    {
        Schema::table('volunteer_applications', function (Blueprint $table) {
            $table->dropUnique('volunteer_applications_unique');
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('volunteer_opportunity_id');
            $table->dropColumn(['message', 'status', 'reviewed_at']);
        });

        Schema::table('volunteer_opportunities', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('event_id');
            $table->dropColumn(['title', 'description', 'slots_needed', 'status']);
        });
    }
};
