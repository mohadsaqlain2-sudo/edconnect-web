<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * classes.started_at
 *
 * The moment the teacher actually pressed "Start class", kept next to the
 * planned "date" + "time" instead of overwriting them. The scheduled slot is
 * what the class was planned for, started_at is what really happened - the
 * teacher may start early or late, and both facts have to survive a refresh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('started_at');
        });
    }
};
