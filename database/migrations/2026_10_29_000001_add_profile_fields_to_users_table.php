<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the EduConnect profile fields to the users table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // admin | teacher | student
            $table->enum('role', ['admin', 'teacher', 'student'])->default('student')->after('password');
            $table->text('bio')->nullable()->after('role');
            $table->string('photo')->nullable()->after('bio');     // relative path on the "public" disk
            $table->string('subject')->nullable()->after('photo'); // e.g. "Mathematics" (teachers)
            $table->boolean('is_blocked')->default(false)->after('subject');

            // Indexes: we filter/sort by these columns a lot.
            $table->index('role');
            $table->index('is_blocked');
        });
    }

    /**
     * Remove the columns again when rolling back.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['is_blocked']);
            $table->dropColumn(['role', 'bio', 'photo', 'subject', 'is_blocked']);
        });
    }
};
