<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 8 performance pass (SRS 5.1).
 *
 * The application filters and sorts by a relationship column plus a date:
 * "my posts, newest first", "comments on this post, oldest first", "pending
 * reports, newest first", "visible classes by date", and so on. A single
 * column index cannot serve the filter and the sort at the same time, so each
 * hot path gets one composite index.
 *
 * Indexes are created defensively (by name) so the migration is safe on MySQL
 * and on the SQLite database used by the test suite.
 */
return new class extends Migration
{
    /**
     * @var list<array{0: string, 1: list<string>, 2: string}>
     */
    private const INDEXES = [
        // Feed, profile pages and "my posts" sorting.
        ['posts', ['user_id', 'created_at'], 'phase8_posts_user_created_index'],
        ['posts', ['is_hidden', 'created_at'], 'phase8_posts_hidden_created_index'],

        // Post detail pages and the profile comment activity.
        ['comments', ['post_id', 'created_at'], 'phase8_comments_post_created_index'],
        ['comments', ['user_id', 'created_at'], 'phase8_comments_user_created_index'],

        // "Posts I liked" and follower/following lists.
        ['likes', ['user_id', 'created_at'], 'phase8_likes_user_created_index'],
        ['follows', ['follower_id', 'created_at'], 'phase8_follows_follower_created_index'],
        ['follows', ['following_id', 'created_at'], 'phase8_follows_following_created_index'],

        // Class catalogue, teacher table and hidden-class checks.
        ['classes', ['status', 'date'], 'phase8_classes_status_date_index'],
        ['classes', ['teacher_id', 'date'], 'phase8_classes_teacher_date_index'],
        ['classes', ['is_hidden', 'date'], 'phase8_classes_hidden_date_index'],

        // "My enrolled classes" and the student dashboard.
        ['class_enrollments', ['student_id', 'created_at'], 'phase8_enrollments_student_created_index'],

        // Moderation queue: filter by status, newest first; per-content lookup.
        ['reports', ['status', 'created_at'], 'phase8_reports_status_created_index'],
        ['reports', ['reportable_type', 'reportable_id', 'status'], 'phase8_reports_reportable_status_index'],

        // Notification bell: unread notifications for one user.
        ['notifications', ['notifiable_type', 'notifiable_id', 'read_at'], 'phase8_notifications_unread_index'],

        // Admin user table: role + blocked filters.
        ['users', ['role', 'is_blocked'], 'phase8_users_role_blocked_index'],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as [$table, $columns, $name]) {
            if (! Schema::hasTable($table) || Schema::hasIndex($table, $name)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
                $blueprint->index($columns, $name);
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as [$table, , $name]) {
            if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $name)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($name): void {
                $blueprint->dropIndex($name);
            });
        }
    }
};
