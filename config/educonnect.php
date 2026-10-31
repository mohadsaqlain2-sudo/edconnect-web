<?php

/*
|--------------------------------------------------------------------------
| EduConnect settings
|--------------------------------------------------------------------------
| Small application specific settings kept in one place so that no secret
| is ever hardcoded inside a class. Values come from the .env file.
*/

return [

    // The administrator account that AdminUserSeeder creates.
    'admin' => [
        'name' => env('ADMIN_NAME', 'EduConnect Admin'),
        'email' => env('ADMIN_EMAIL', 'admin@educonnect.test'),
        // Never hardcode this. It must be set in .env, otherwise seeding fails loudly.
        'password' => env('ADMIN_PASSWORD'),
    ],

    // Upload rules (used by the Form Requests and ImageUploadService).
    // Files are always re-encoded with GD, so these limits also decide the
    // dimensions and quality of what actually lands on disk (SRS 5.1).
    'uploads' => [
        'post_image_dir' => 'posts',                          // -> storage/app/public/posts
        'post_image_max_kb' => 2048,                          // 2 MB
        'post_image_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'post_image_max_width' => 1600,
        'post_image_max_height' => 1600,
        'post_image_quality' => 82,

        'profile_photo_dir' => 'profiles',                    // -> storage/app/public/profiles
        'profile_photo_max_kb' => 2048,                       // 2 MB
        'profile_photo_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'profile_photo_max_width' => 512,
        'profile_photo_max_height' => 512,
        'profile_photo_quality' => 85,
    ],

    // Default meta description for pages that do not provide their own.
    'meta' => [
        'description' => env(
            'APP_DESCRIPTION',
            'EduConnect is an online classroom with live video classes, a friendly social feed and simple class management for teachers and students.'
        ),
    ],

];
