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
            if (count($result) == 0) {
                return null;
            }
        });
        $resolver->setNormalizer('PrioritizeRecentReads', function (Options $options, string|bool $value) {
            return in_array(strtolower((string) $value), ['true', '1', 'yes'], true);
        });

        return $resolver;
    }

    public function maxLastModified(?\DateTimeInterface $value): ?\DateTimeInterface
    {
        return $this->max(
            $this->lastModified,
            $value
        );
    }

    public function maxLastCreated(?\DateTimeInterface $value): ?\DateTimeInterface
    {
        return $this->max(
            $this->lastCreated,
            $value
        );
    }

    protected function max(?\DateTimeInterface $a, ?\DateTimeInterface $b): ?\DateTimeInterface
    {
        return $a !== null && $a > $b ? $a : $b;
    }
}
