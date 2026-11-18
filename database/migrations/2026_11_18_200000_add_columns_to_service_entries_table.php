<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_entries', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('volunteer_opportunity_id')->nullable()->constrained('volunteer_opportunities')->cascadeOnDelete();
            $table->decimal('hours', 5, 2)->default(0);
            $table->date('worked_on')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('service_entries', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('volunteer_opportunity_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['hours', 'worked_on', 'description', 'status', 'reviewed_at', 'review_note']);
        });
    }
};
