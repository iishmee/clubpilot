<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_proposals', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 150)->after('user_id');
            $table->text('details')->after('name');
            $table->text('objectives')->after('details');
            $table->string('constitution_path')->nullable()->after('objectives');
            $table->string('status', 30)->default('pending')->index()->after('constitution_path');
        });

        Schema::table('clubs', function (Blueprint $table) {
            $table->foreignId('club_proposal_id')->unique()->after('id')
                ->constrained('club_proposals')->restrictOnDelete();
            $table->string('name', 150)->after('club_proposal_id');
            $table->text('details')->after('name');
            $table->text('objectives')->after('details');
        });

        Schema::table('audit_entries', function (Blueprint $table) {
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->morphs('subject');
            $table->string('from_state', 30)->nullable();
            $table->string('to_state', 30);
            $table->text('reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('audit_entries', function (Blueprint $table) {
            $table->dropMorphs('subject');
            $table->dropConstrainedForeignId('actor_id');
            $table->dropColumn(['from_state', 'to_state', 'reason']);
        });

        Schema::table('clubs', function (Blueprint $table) {
            $table->dropForeign(['club_proposal_id']);
            $table->dropUnique(['club_proposal_id']);
            $table->dropColumn(['club_proposal_id', 'name', 'details', 'objectives']);
        });

        Schema::table('club_proposals', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['name', 'details', 'objectives', 'constitution_path', 'status']);
        });
    }
};
