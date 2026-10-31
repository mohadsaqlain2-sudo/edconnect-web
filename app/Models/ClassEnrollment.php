<?php

namespace App\Models;

use Database\Factories\ClassEnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The link between a class and an enrolled student (FR-17).
 */
class ClassEnrollment extends Model
{
    /** @use HasFactory<ClassEnrollmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'class_id',
        'student_id',
    ];

    /* ---------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------- */

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
