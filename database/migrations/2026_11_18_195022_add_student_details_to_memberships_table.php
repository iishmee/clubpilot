<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {

            $table->string('student_id', 50)
                ->nullable()
                ->after('user_id');

            $table->string('department', 150)
                ->nullable()
                ->after('student_id');

            $table->string('semester', 50)
                ->nullable()
                ->after('department');

        });
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {

            $table->dropColumn([
                'student_id',
                'department',
                'semester',
            ]);

        });
    }
};
