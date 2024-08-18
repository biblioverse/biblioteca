<?php

namespace App\Tests\Contraints;

use PHPUnit\Framework\Constraint\Constraint;

class AssertHasDownloadWithFormat  extends Constraint
{
    public function __construct(private readonly string $format)
    {
    }

    public function matches($other): bool{
        try{
            $this->test($other);
        }catch (\InvalidArgumentException $e){
            return false;
        }
        return true;
    }

    /**
     * @param array|mixed $other
     */
    public function test($other): void
    {
        if (false === is_array($other)) {
            throw new \InvalidArgumentException('JSON is not an array');
        }

        if (count($other) < 1) {
            throw new \InvalidArgumentException('array is empty');
        }

        $other = $other[0];

        if (!array_key_exists('DownloadUrls', $other)) {
            throw new \InvalidArgumentException('DownloadUrls exists');
        }

        $downloads = $other['DownloadUrls'];
        if (false === is_array($downloads) || count($downloads) < 1) {
            throw new \InvalidArgumentException('DownloadUrls is empty');
        }


        foreach ($downloads as $pos => $download) {
            if (!array_key_exists('Format', $download)) {
                throw new \InvalidArgumentException('Download has no key Format');
            }

            if ($download['Format'] !== $this->format) {
                throw new \InvalidArgumentException(sprintf('Invalid format, expected %s got %s', $this->format, $download['Format']));
            }

            foreach (['Format', 'Platform', 'Url', 'Size'] as $key) {
                if (!array_key_exists($key, $download)) {
                    throw new \InvalidArgumentException('Download ' . $pos . ' has ko key ' . $key);
                }

                if (trim((string)$download[$key]) === '') {
                    throw new \InvalidArgumentException('Download ' . $pos . ' has an empty value for key ' . $key);
                }
            }
        }
    }

    public function toString(): string
    {
        return sprintf('JSON contains a download key with the specified format: %s', $this->format);
    }
}