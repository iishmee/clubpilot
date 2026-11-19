<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('memberships', 'student_id')) {
            Schema::table('memberships', function (Blueprint $table) {
                $table->string('student_id', 50)
                    ->nullable()
                    ->after('user_id');
            });
        }

        if (!Schema::hasColumn('memberships', 'department')) {
            Schema::table('memberships', function (Blueprint $table) {
                $table->string('department', 150)
                    ->nullable()
                    ->after('student_id');
            });
        }

        if (!Schema::hasColumn('memberships', 'semester')) {
            Schema::table('memberships', function (Blueprint $table) {
                $table->string('semester', 50)
                    ->nullable()
                    ->after('department');
            });
        }
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {

            $columns = [];

            foreach (['student_id', 'department', 'semester'] as $column) {
                if (Schema::hasColumn('memberships', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
