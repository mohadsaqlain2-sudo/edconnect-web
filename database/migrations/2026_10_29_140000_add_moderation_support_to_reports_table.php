<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extra moderation data for reports (FR-32 ... FR-34).
     *
     *  - "details" is the optional free text the reporter may add.
     *  - the unique index makes a second report of the same item by the same
     *    user impossible, even if somebody bypasses the controller.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->text('details')->nullable()->after('reason');

            $table->unique(
                ['reporter_id', 'reportable_type', 'reportable_id'],
                'reports_reporter_reportable_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropUnique('reports_reporter_reportable_unique');
            $table->dropColumn('details');
        });
    }
};
