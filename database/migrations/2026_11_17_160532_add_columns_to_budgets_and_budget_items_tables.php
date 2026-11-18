
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->foreignId('event_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('notes')->nullable();
        });

        Schema::table('budget_items', function (Blueprint $table) {
            $table->foreignId('budget_id')
                ->constrained('budgets')
                ->cascadeOnDelete();

            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_cost', 12, 2);
        });
    }

    public function down(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('budget_id');
            $table->dropColumn([
                'description',
                'quantity',
                'unit_cost',
            ]);
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('notes');
        });
    }
};

