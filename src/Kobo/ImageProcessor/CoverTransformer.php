<?php

namespace App\Kobo\ImageProcessor;

class CoverTransformer
{
    public const JPG = '.jpg';
    public const PNG = '.png';
    public const GIF = '.gif';
    public const JPEG = '.jpeg';
    public const WEBP = '.webp';

    private function fallback(string $coverPath): void
    {
        readfile($coverPath);
    }

    public function canConvertFile(string $coverPath): bool
    {
        if (!function_exists('imagecreatetruecolor')) {
            return false;
        }

        // We only support jpeg & png & gif & webp
        return !(false === str_ends_with($coverPath, self::JPG)
            && false === str_ends_with($coverPath, self::PNG)
            && false === str_ends_with($coverPath, self::GIF)
            && false === str_ends_with($coverPath, self::JPEG)
            && false === str_ends_with($coverPath, self::WEBP));
    }

    public function streamFile(string $coverPath, int $maxWidth, int $maxHeight, string $extensionWithDot, bool $grayscale = false): void
    {
        // Make sure the extension starts with a dot
        if (false === str_starts_with($extensionWithDot, '.')) {
            $extensionWithDot = '.'.$extensionWithDot;
        }

        // Check if the file can be converted
        if (false === $this->canConvertFile($coverPath)) {
            $this->fallback($coverPath);

            return;
        }

        // Resize image based on the width and height
        $sizes = getimagesize($coverPath);
        if ($sizes === false) {
            $this->fallback($coverPath);

            return;
        }
        [$originalWidth, $originalHeight] = $sizes;

        $aspectRatio = $originalWidth / max(1, $originalHeight);

        // Calculate the new dimensions while maintaining the aspect ratio and respecting maxWidth and maxHeight
        $newWidth = $maxWidth;
        $newHeight = $maxHeight;

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
        $originalExtensionWithDot = '.'.pathinfo($coverPath, PATHINFO_EXTENSION); // Add a dot to the extension
        $originalImage = match ($originalExtensionWithDot) {
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
        $x = (int) (($maxWidth - $newWidth) / 2);
        $y = (int) (($maxHeight - $newHeight) / 2);

        // Copy the original image onto the new image and center it
        imagecopyresampled($image, $originalImage, $x, $y, 0, 0, (int) $newWidth, $newHeight, $originalWidth, $originalHeight);

        if ($grayscale) {
            imagefilter($image, IMG_FILTER_GRAYSCALE);
        }

        // Output the image based on the extension
        match ($extensionWithDot) {
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
