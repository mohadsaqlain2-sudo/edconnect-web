<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores uploaded images safely (SRS 5.1, SRS 5.2).
 *
 * Every upload is decoded with GD and re-encoded from scratch:
 *
 *  - the original file is never copied to disk, so a PHP payload hidden inside
 *    a "JPEG" cannot survive the round trip,
 *  - EXIF metadata (location, camera, author) is dropped,
 *  - large photos are resized before they are written,
 *  - the file name is 40 random characters, never the client's name,
 *  - the extension is chosen by us (webp, or jpg when GD has no WebP support).
 *
 * A file that GD cannot decode is rejected with a normal validation error,
 * even when its MIME type claimed to be an image.
 */
class ImageUploadService
{
    /**
     * Store a post image, resized to the configured maximum width and height.
     */
    public function storePostImage(UploadedFile $file): string
    {
        $config = config('educonnect.uploads');

        return $this->store(
            $file,
            (string) $config['post_image_dir'],
            (int) $config['post_image_max_width'],
            (int) $config['post_image_max_height'],
            (int) $config['post_image_quality'],
            'image'
        );
    }

    /**
     * Store a profile photo, cropped down to the configured square-ish size.
     */
    public function storeProfilePhoto(UploadedFile $file): string
    {
        $config = config('educonnect.uploads');

        return $this->store(
            $file,
            (string) $config['profile_photo_dir'],
            (int) $config['profile_photo_max_width'],
            (int) $config['profile_photo_max_height'],
            (int) $config['profile_photo_quality'],
            'photo'
        );
    }

    /**
     * Decode, resize, re-encode and write one file. Returns the relative path
     * on the "public" disk.
     *
     * @param  string  $field  the form field name, used for the error message
     */
    private function store(
        UploadedFile $file,
        string $directory,
        int $maxWidth,
        int $maxHeight,
        int $quality,
        string $field
    ): string {
        $path = $file->getRealPath();
        $contents = $path !== false ? @file_get_contents($path) : false;
        $source = $contents !== false ? @imagecreatefromstring($contents) : false;

        if ($source === false) {
            throw ValidationException::withMessages([
                $field => __('That file is not a readable image. Please upload a JPG, PNG or WebP photo.'),
            ]);
        }

        $width = imagesx($source);
        $height = imagesy($source);

        // Never enlarge a small image; only shrink what is too big.
        $scale = min(1, $maxWidth / max(1, $width), $maxHeight / max(1, $height));
        $newWidth = max(1, (int) floor($width * $scale));
        $newHeight = max(1, (int) floor($height * $scale));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        $useWebp = function_exists('imagewebp');

        if ($useWebp) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $transparent);
        } else {
            // JPEG has no transparency: flatten onto white first.
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $white);
        }

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );

        ob_start();

        if ($useWebp) {
            imagewebp($canvas, null, $quality);
        } else {
            imagejpeg($canvas, null, $quality);
        }

        $encoded = ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($source);

        if (! is_string($encoded) || $encoded === '') {
            throw ValidationException::withMessages([
                $field => __('The image could not be processed. Please try another file.'),
            ]);
        }

        $path = trim($directory, '/').'/'.Str::random(40).($useWebp ? '.webp' : '.jpg');

        Storage::disk('public')->put($path, $encoded);

        return $path;
    }
}
