<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2: make sure every relationship column has its own index.
 *
 * Foreign keys create an index automatically on MySQL, but not on SQLite (used
 * by the test suite). Composite unique indexes only cover their FIRST column,
 * so columns such as likes.user_id and follows.following_id still need an
 * index of their own for the "my likes" / "my followers" queries.
 *
 * The migration is defensive: it only creates an index when no single-column
 * index on that column already exists, so it is safe on both databases.
 */
return new class extends Migration
{
    /**
     * Table => relationship columns that need a single-column index.
     *
     * @var array<string, list<string>>
     */
    private const INDEXES = [
        'posts' => ['user_id'],
        'comments' => ['post_id', 'user_id'],
        'likes' => ['user_id'],
        'follows' => ['following_id'],
        'hashtag_post' => ['post_id'],
        'classes' => ['teacher_id'],
        'class_enrollments' => ['student_id'],
        'reports' => ['reporter_id'],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $columns) {
            foreach ($columns as $column) {
                // Skip when a single-column index already exists (MySQL FK index).
                if (Schema::hasIndex($table, [$column])) {
                    continue;
                }

                Schema::table($table, function (Blueprint $table) use ($column) {
                    $table->index($column, 'phase2_'.$table->getTable().'_'.$column.'_index');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $columns) {
            foreach ($columns as $column) {
                $name = 'phase2_'.$table.'_'.$column.'_index';

                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, function (Blueprint $table) use ($name) {
                        $table->dropIndex($name);
                    });
                }
            }
        }
    }
};
