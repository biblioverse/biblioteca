<?php

namespace App\Kobo;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SyncToken
{
    public string $version = '1-1-0';
    public ?\DateTimeInterface $lastModified = null;
    public ?\DateTimeInterface $lastCreated = null;
    public ?\DateTimeInterface $archiveLastModified = null;
    public ?\DateTimeInterface $readingStateLastModified = null;
    public ?\DateTimeInterface $tagLastModified = null;
    public ?string $rawKoboStoreToken = null;
    public array $filters = [];

    public ?\DateTimeInterface $currentDate = null;

    public function getFilterResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'Filter' => 'ALL',
            'DownloadUrlFilter' => 'Generic,Android',
            'PrioritizeRecentReads' => true,
        ]);
        $resolver->setAllowedTypes('Filter', 'string');
        $resolver->setAllowedTypes('DownloadUrlFilter', ['string', 'array', 'null']);
        $resolver->setAllowedValues('DownloadUrlFilter', ['Generic', 'Android', 'Generic,Android']);
        $resolver->setAllowedTypes('PrioritizeRecentReads', ['string', 'bool']);

        $resolver->setNormalizer('DownloadUrlFilter', function (Options $options, string|array|null $value) {
            $result = is_array($value) ? $value : explode(',', (string) $value);
            if (count($result) === 0) {
                return null;
            }
        });
        $resolver->setNormalizer('PrioritizeRecentReads', fn (Options $options, string|bool $value) => in_array(strtolower((string) $value), ['true', '1', 'yes'], true));

        return $resolver;
    }

    public function maxLastModified(?\DateTimeInterface ...$value): ?\DateTimeInterface
    {
        return self::max(
            $this->lastModified,
            ...$value
        );
    }

    public function maxLastCreated(?\DateTimeInterface ...$value): ?\DateTimeInterface
    {
        return self::max(
            $this->lastCreated,
            ...$value
        );
    }

    protected static function max(?\DateTimeInterface ...$dates): ?\DateTimeInterface
    {
        $max = null;
        foreach ($dates as $date) {
            if (!$date instanceof \DateTimeInterface) {
                continue;
            }

            if (!$max instanceof \DateTimeInterface || $date > $max) {
                $max = $date;
            }
        }

        return $max;
    }
}
