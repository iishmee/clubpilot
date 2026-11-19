
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_roles', function (Blueprint $table) {

            $table->foreignId('club_id')
                  ->constrained('clubs')
                  ->cascadeOnDelete();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('role', 30);

            $table->unique(
                ['club_id', 'user_id', 'role']
            );

            $table->index(['user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('club_roles', function (Blueprint $table) {

            $table->dropUnique(
                ['club_id', 'user_id', 'role']
            );

            $table->dropIndex(['user_id', 'role']);

            $table->dropForeign(['club_id']);
            $table->dropForeign(['user_id']);

            $table->dropColumn([
                'club_id',
                'user_id',
                'role'
            ]);
        });
    }
};
