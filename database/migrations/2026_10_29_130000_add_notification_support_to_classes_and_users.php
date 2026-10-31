<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 support columns.
 *
 * classes.starting_soon_notified_at
 *   A one-shot flag: the "classes:notify-starting-soon" command sets it after
 *   it has warned the teacher and the enrolled students, so the reminder for
 *   that class can never be sent twice (FR-23).
 *
 * users.name / users.subject
 *   Indexes for the people search in the navbar (FR-14).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->timestamp('starting_soon_notified_at')->nullable()->after('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('name');
            $table->index('subject');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('starting_soon_notified_at');
        });

        Schema::table('users', function (Blueprint $table) {
            // Laravel builds the names "users_name_index" / "users_subject_index".
            $table->dropIndex(['name']);
            $table->dropIndex(['subject']);
        });
    }
};
