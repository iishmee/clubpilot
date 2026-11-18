<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('volunteer_opportunity_id')->nullable()->constrained('volunteer_opportunities')->cascadeOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('certificate_number')->nullable()->unique();
            $table->decimal('hours', 7, 2)->default(0);
            $table->timestamp('issued_at')->nullable();
            // One certificate per volunteer per opportunity.
            $table->unique(['user_id', 'volunteer_opportunity_id'], 'certificates_user_opportunity_unique');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropUnique('certificates_user_opportunity_unique');
            $table->dropUnique(['certificate_number']);
            $table->dropConstrainedForeignId('issued_by');
            $table->dropConstrainedForeignId('volunteer_opportunity_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['certificate_number', 'hours', 'issued_at']);
        });
    }
};
