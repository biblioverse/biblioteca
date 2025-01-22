<?php

namespace App\Tests\Contraints;

use PHPUnit\Framework\Constraint\Constraint;

/**
 * @phpstan-type Format 'EPUB'|'EPUB3'|'KEPUB'
 * @phpstan-type DownloadUrl array{Format: string, Platform: string, Url: string, Size: string}
 * @phpstan-type MatchContent array{DownloadUrls: DownloadUrl[]}
 */
class AssertHasDownloadWithFormat extends Constraint
{
    /**
     * @param Format $format
     */
    public function __construct(private readonly string $format)
    {
    }

    #[\Override]
    public function matches($other): bool
    {
        try {
            // @phpstan-ignore-next-line
            $this->test($other);
        } catch (\InvalidArgumentException) {
            return false;
        }

        return true;
    }

    /**
     * @param MatchContent[] $other
     */
    public function test($other): void
    {
        // @phpstan-ignore-next-line
        if (false === is_array($other)) {
            throw new \InvalidArgumentException('JSON is not an array');
        }

        if (count($other) < 1) {
            throw new \InvalidArgumentException('array is empty');
        }

        $other = $other[0];
        // @phpstan-ignore-next-line
        if (!array_key_exists('DownloadUrls', $other)) {
            throw new \InvalidArgumentException('DownloadUrls exists');
        }

        $downloads = $other['DownloadUrls'];
        // @phpstan-ignore-next-line
        if (false === is_array($downloads) || count($downloads) < 1) {
            throw new \InvalidArgumentException('DownloadUrls is empty');
        }

        foreach ($downloads as $pos => $download) {
            // @phpstan-ignore-next-line
            if (!array_key_exists('Format', $download)) {
                throw new \InvalidArgumentException('Download has no key Format');
            }

            if ($download['Format'] !== $this->format) {
                throw new \InvalidArgumentException(sprintf('Invalid format, expected %s got %s', $this->format, $download['Format']));
            }

            foreach (['Format', 'Platform', 'Url', 'Size'] as $key) {
                if (!array_key_exists($key, $download)) {
                    throw new \InvalidArgumentException('Download '.$pos.' has ko key '.$key);
                }

                // @phpstan-ignore-next-line
                if (trim((string) $download[$key]) === '') {
                    throw new \InvalidArgumentException('Download '.$pos.' has an empty value for key '.$key);
                }
            }
        }
    }

    #[\Override]
    public function toString(): string
    {
        return sprintf('JSON contains a download key with the specified format: %s', $this->format);
    }
}
