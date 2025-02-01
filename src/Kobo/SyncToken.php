<?php

namespace App\Kobo;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SyncToken
{
    public string $version = '1-1-0';
    public ?\DateTimeImmutable $lastModified = null;
    public ?\DateTimeImmutable $lastCreated = null;
    public ?\DateTimeImmutable $archiveLastModified = null;
    public ?\DateTimeImmutable $readingStateLastModified = null;
    public ?\DateTimeImmutable $tagLastModified = null;
    public ?string $rawKoboStoreToken = null;
    public array $filters = [];

    public ?\DateTimeImmutable $currentDate = null;

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
            if ($result === []) {
                return null;
            }
        });
        $resolver->setNormalizer('PrioritizeRecentReads', fn (Options $options, string|bool $value) => in_array(strtolower((string) $value), ['true', '1', 'yes'], true));

        return $resolver;
    }

    public function maxLastModified(?\DateTimeImmutable ...$value): ?\DateTimeImmutable
    {
        return self::max(
            $this->lastModified,
            ...$value
        );
    }

    public function maxLastCreated(?\DateTimeImmutable ...$value): ?\DateTimeImmutable
    {
        return self::max(
            $this->lastCreated,
            ...$value
        );
    }

    protected static function max(?\DateTimeImmutable ...$dates): ?\DateTimeImmutable
    {
        $max = null;
        foreach ($dates as $date) {
            if (!$date instanceof \DateTimeImmutable) {
                continue;
            }

            if (!$max instanceof \DateTimeImmutable || $date > $max) {
                $max = $date;
            }
        }

        return $max;
    }
}
