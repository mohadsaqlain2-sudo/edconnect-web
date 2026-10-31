<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Run it with: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        // 1. The one admin account (email + password come from .env).
        $this->call(AdminUserSeeder::class);

        // 2. The demo people: 3 teachers + 6 students (Phase 1).
        $this->call(DemoUserSeeder::class);

        // 3. Posts, comments, likes, follows, classes, enrollments, reports.
        $this->call(DemoContentSeeder::class);
    }
}
