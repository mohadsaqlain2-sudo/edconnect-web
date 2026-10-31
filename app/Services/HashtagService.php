<?php

namespace App\Services;

use App\Models\Hashtag;
use App\Models\Post;
use Illuminate\Support\HtmlString;

/**
 * Everything that has to do with hashtags (FR-15).
 *
 * The service has two jobs:
 *   1. pull the #tags out of a post body and sync the hashtag_post pivot,
 *   2. turn those tags into links inside an already escaped body.
 */
class HashtagService
{
    /**
     * Matches "#word" where word is 1-50 Unicode letters, digits or underscores.
     * The /u flag makes the pattern operate on UTF-8 characters, so tags such
     * as #Éducation and #数学 are extracted as single hashtags (FR-13).
     */
    public const PATTERN = '/#([\p{L}\p{N}_]{1,50})/u';

    /**
     * Every hashtag found in the text, lower case and without duplicates.
     *
     * @return list<string>
     */
    public function extract(?string $body): array
    {
        if ($body === null || $body === '') {
            return [];
        }

        preg_match_all(self::PATTERN, $body, $matches);

        // Storing them lower case means #Maths and #maths are the same tag (FR-15).
        return array_values(array_unique(array_map(
            static fn (string $name): string => mb_strtolower($name),
            $matches[1] ?? []
        )));
    }

    /**
     * Replace the hashtags of a post with the tags found in its body.
     * sync() also detaches tags the author removed while editing.
     *
     * @return list<string> the hashtag names that are now attached
     */
    public function sync(Post $post, ?string $body): array
    {
        $names = $this->extract($body);

        $ids = [];

        foreach ($names as $name) {
            // firstOrCreate keeps one row per tag, the mutator lowercases the name.
            $ids[] = Hashtag::firstOrCreate(['name' => $name])->id;
        }

        $post->hashtags()->sync($ids);

        return $names;
    }

    /**
     * Turn the hashtags of a body into links to /hashtag/{name} (FR-15).
     *
     * Safety: the text is escaped FIRST with e() and only then are the <a>
     * tags inserted. A body like "<script>alert(1)</script>" therefore reaches
     * the browser as text, never as markup, so there is no XSS hole.
     */
    public function linkify(?string $body): HtmlString
    {
        $safe = e($body ?? '');

        $html = preg_replace_callback(self::PATTERN, static function (array $match): string {
            $name = mb_strtolower($match[1]);

            // Built by the router, then escaped again because it lands in an attribute.
            $url = e(route('hashtags.show', ['hashtag' => $name]));

            return '<a href="'.$url.'" class="text-edu fw-semibold text-decoration-none">#'.$name.'</a>';
        }, $safe);

        // preg_replace_callback only returns null on a broken pattern.
        return new HtmlString($html ?? $safe);
    }
}
