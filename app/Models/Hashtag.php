<?php

namespace App\Models;

use Database\Factories\HashtagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Hashtag extends Model
{
    /** @use HasFactory<HashtagFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Hashtags are always stored in lower case.
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = mb_strtolower(ltrim(trim($value), '#'));
    }

    /* ---------------------------------------------------------------
     | Relationships
     * --------------------------------------------------------------- */

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'hashtag_post');
    }
}
