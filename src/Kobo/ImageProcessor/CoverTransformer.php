<?php

namespace App\Kobo\ImageProcessor;

class CoverTransformer
{
    public const JPG = '.jpg';
    public const PNG = '.png';
    public const GIF = '.gif';
    public const JPEG = '.jpeg';
    public const WEBP = '.wepb';

    public function __construct()
    {
    }

    private function fallback(string $coverPath): void
    {
        readfile($coverPath);
    }

    public function streamFile(string $coverPath, int $maxWidth, int $maxHeight, bool $grayscale = false): void
    {
        // Check if GD extension is loaded
        if (false === function_exists('imagecreatetruecolor')) {
            $this->fallback($coverPath);

            return;
        }

        // We only support jpeg & png & gif & webp
        if (false === str_ends_with($coverPath, self::JPG)
            && false === str_ends_with($coverPath, self::PNG)
            && false === str_ends_with($coverPath, self::GIF)
            && false === str_ends_with($coverPath, self::JPEG)
            && false === str_ends_with($coverPath, self::WEBP)
        ) {
            $this->fallback($coverPath);

            return;
        }

        // Resize image based on the width and height
        $sizes = getimagesize($coverPath);
        if ($sizes === false) {
            $this->fallback($coverPath);

            return;
        }
        list($originalWidth, $originalHeight) = $sizes;

        $aspectRatio = $originalWidth / max(1, $originalHeight);

        // Calculate the new dimensions while maintaining the aspect ratio and respecting maxWidth and maxHeight
        $newWidth = min($maxWidth, $originalWidth);
        $newHeight = min($maxHeight, $originalHeight);

        // New ratio
        if ($newWidth / $aspectRatio > $newHeight) {
            $newWidth = $newHeight * $aspectRatio;
        } else {
            $newHeight = (int) ($newWidth / max($aspectRatio, 1));
        }

        // Create a blank image with a black background
        $image = imagecreatetruecolor($maxWidth, $maxHeight);
        if ($image === false) {
            $this->fallback($coverPath);

            return;
        }

        // Fill the background with black
        $blackColor = imagecolorallocate($image, 0, 0, 0);
        if ($blackColor === false) {
            $this->fallback($coverPath);

            return;
        }
        imagefill($image, 0, 0, $blackColor);

        // Load the original image based on the extension
        $extension = pathinfo($coverPath, PATHINFO_EXTENSION);
        $originalImage = match ($extension) {
            self::PNG => imagecreatefrompng($coverPath),
            self::GIF => imagecreatefromgif($coverPath),
            self::WEBP => imagecreatefromwebp($coverPath),
            default => imagecreatefromjpeg($coverPath),
        };
        if (false === $originalImage) {
            $this->fallback($coverPath);

            return;
        }

        // Calculate the position to center the image on the canvas
        $x = ($maxWidth - $newWidth) / 2;
        $y = ($maxHeight - $newHeight) / 2;

        // Copy the original image onto the new image and center it
        imagecopyresampled($image, $originalImage, (int) $x, (int) $y, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        if ($grayscale) {
            imagefilter($image, IMG_FILTER_GRAYSCALE);
        }

        // Output the image based on the extension
        match ($extension) {
            self::PNG => imagepng($image, null, 9),
            self::GIF => imagegif($image),
            self::WEBP => imagewebp($image),
            default => imagejpeg($image, null, 100),
        };

        // Clean up resources
        imagedestroy($image);
        imagedestroy($originalImage);
    }
}
