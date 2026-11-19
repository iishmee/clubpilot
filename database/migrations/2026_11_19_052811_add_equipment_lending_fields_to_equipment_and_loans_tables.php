<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Equipment inventory fields
        Schema::table('equipment', function (Blueprint $table) {

            $table->foreignId('club_id')
                ->after('id')
                ->constrained('clubs')
                ->restrictOnDelete();

            $table->string('name', 150);

            $table->string('category', 100)->nullable();

            $table->text('description')->nullable();

            $table->unsignedInteger('quantity')->default(1);

            $table->string('item_condition', 30)
                ->default('good');

            $table->boolean('is_active')->default(true);

            $table->index(
                ['club_id', 'is_active'],
                'equipment_club_active_idx'
            );
        });

        // Equipment borrowing and loan fields
        Schema::table('loans', function (Blueprint $table) {

            $table->foreignId('equipment_id')
                ->after('id')
                ->constrained('equipment')
                ->restrictOnDelete();

            $table->foreignId('membership_id')
                ->constrained('memberships')
                ->restrictOnDelete();

            $table->unsignedInteger('requested_quantity')
                ->default(1);

            $table->dateTime('starts_at');

            $table->dateTime('ends_at');

            $table->string('status', 20)
                ->default('pending');

            $table->text('request_note')->nullable();

            $table->text('decision_note')->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->foreignId('issued_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('issued_at')->nullable();

            $table->foreignId('returned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('returned_at')->nullable();

            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('rejected_at')->nullable();

            $table->timestamp('cancelled_at')->nullable();

            $table->index(
                ['equipment_id', 'status', 'starts_at', 'ends_at'],
                'loans_equipment_period_idx'
            );

            $table->index(
                ['membership_id', 'status'],
                'loans_membership_status_idx'
            );

            $table->index('status', 'loans_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {

            $table->dropIndex('loans_equipment_period_idx');

            $table->dropIndex('loans_membership_status_idx');

            $table->dropIndex('loans_status_idx');

            $table->dropConstrainedForeignId('rejected_by');

            $table->dropConstrainedForeignId('returned_to');

            $table->dropConstrainedForeignId('issued_by');

            $table->dropConstrainedForeignId('approved_by');

            $table->dropConstrainedForeignId('membership_id');

            $table->dropConstrainedForeignId('equipment_id');

            $table->dropColumn([
                'requested_quantity',
                'starts_at',
                'ends_at',
                'status',
                'request_note',
                'decision_note',
                'approved_at',
                'issued_at',
                'returned_at',
                'rejected_at',
                'cancelled_at',
            ]);
        });

        Schema::table('equipment', function (Blueprint $table) {

            $table->dropIndex('equipment_club_active_idx');

            $table->dropConstrainedForeignId('club_id');

            $table->dropColumn([
                'name',
                'category',
                'description',
                'quantity',
                'item_condition',
                'is_active',
            ]);
        });
    }
};
