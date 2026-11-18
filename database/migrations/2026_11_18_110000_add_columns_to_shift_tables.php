<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('volunteer_opportunity_id')->nullable()->constrained('volunteer_opportunities')->cascadeOnDelete();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('capacity')->default(1);
            $table->index(['starts_at', 'ends_at'], 'shifts_time_lookup');
        });

        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            // A volunteer can be assigned to a given shift only once.
            $table->unique(['shift_id', 'user_id'], 'shift_assignments_unique');
        });
    }

    public function down(): void
    {
        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->dropUnique('shift_assignments_unique');
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('shift_id');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex('shifts_time_lookup');
            $table->dropConstrainedForeignId('volunteer_opportunity_id');
            $table->dropColumn(['starts_at', 'ends_at', 'capacity']);
        });
    }
};
