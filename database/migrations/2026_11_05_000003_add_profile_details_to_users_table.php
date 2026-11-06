<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extra profile details shown on the View / Edit profile pages.
     *
     * Everything is nullable and added next to the existing profile columns
     * (bio, photo, subject), so no existing row is touched and no data is lost.
     * The columns a role does not use simply stay empty: an admin never fills
     * the teacher or student fields, and the forms only show what applies.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // --- Personal details (every role) ---
            $table->string('phone', 30)->nullable()->after('photo');
            $table->string('cover_photo')->nullable()->after('phone'); // relative path on the "public" disk
            $table->string('location', 120)->nullable()->after('cover_photo');

            // --- Social links (every role) ---
            $table->string('website')->nullable()->after('location');
            $table->string('linkedin')->nullable()->after('website');
            $table->string('twitter')->nullable()->after('linkedin');
            $table->string('facebook')->nullable()->after('twitter');

            // --- Skills (teachers and students) ---
            $table->text('skills')->nullable()->after('facebook');

            // --- Teacher education / experience ---
            $table->string('qualification', 150)->nullable()->after('subject');
            $table->string('degree', 150)->nullable()->after('qualification');
            $table->string('institution', 150)->nullable()->after('degree'); // university (teachers) / school (students)
            $table->string('specialization', 150)->nullable()->after('institution');
            $table->unsignedTinyInteger('experience_years')->nullable()->after('specialization');
            $table->text('certifications')->nullable()->after('experience_years');

            // --- Student education ---
            $table->string('education_level', 100)->nullable()->after('certifications');
            $table->string('grade', 50)->nullable()->after('education_level');
            $table->text('interests')->nullable()->after('grade');
        });
    }

    /**
     * Remove the columns again when rolling back.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'cover_photo',
                'location',
                'website',
                'linkedin',
                'twitter',
                'facebook',
                'skills',
                'qualification',
                'degree',
                'institution',
                'specialization',
                'experience_years',
                'certifications',
                'education_level',
                'grade',
                'interests',
            ]);
        });
    }
};
