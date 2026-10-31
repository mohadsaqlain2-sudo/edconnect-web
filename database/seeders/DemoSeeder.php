<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Backwards-compatible entry point used by earlier phase notes.
 * New installs call DemoUserSeeder and DemoContentSeeder directly from
 * DatabaseSeeder; this wrapper keeps "db:seed --class=DemoSeeder" working.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoUserSeeder::class,
            DemoContentSeeder::class,
        ]);
    }
}
