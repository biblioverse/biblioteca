<?php

namespace App\Kobo\Proxy;

use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;

class KoboProxyConfiguration
{
    private bool $useProxy = true;
    private bool $useProxyEverywhere = false;

    private string $imageApiUrl = '';
    private string $storeApiUrl = '';
    private string $readingServiceUrl = '';

    public function useProxy(): bool
    {
        return $this->useProxy;
    }

    public function useProxyEverywhere(): bool
    {
        return $this->useProxyEverywhere;
    }

    public function getStoreApiUrl(): string
    {
        if ($this->storeApiUrl === '') {
            throw new \InvalidArgumentException('Store API URL is not set');
        }

        return $this->storeApiUrl;
    }

    public function setStoreApiUrl(string $storeApiUrl): self
    {
        $this->storeApiUrl = $storeApiUrl;

        return $this;
    }

    public function getImageApiUrl(): string
    {
        if ($this->storeApiUrl === '') {
            throw new \InvalidArgumentException('Image API URL is not set');
        }

        return $this->imageApiUrl;
    }

    public function isImageHostUrl(Request|RequestInterface $request): bool
    {
        $uri = $request instanceof Request ? $request->getRequestUri() : (string) $request->getUri();

        return str_ends_with($uri, '.jpg')
            || str_ends_with($uri, '.png')
            || str_ends_with($uri, '.jpeg');
    }

    public function isReadingServiceUrl(Request|RequestInterface $request): bool
    {
        $uri = $request instanceof Request ? $request->getRequestUri() : (string) $request->getUri();

        return str_contains($uri, '/api/v3/content/');
    }

    public function setImageApiUrl(string $imageApiUrl): KoboProxyConfiguration
    {
        $this->imageApiUrl = $imageApiUrl;

        return $this;
    }

    public function setEnabled(bool $useProxy): KoboProxyConfiguration
    {
        $this->useProxy = $useProxy;

        return $this;
    }

    /**
     * @return array{'Resources': array<string, mixed>}
     * @throws \RuntimeException
     */
    public function getNativeInitializationJson(): array
    {
        $file = __DIR__.'/KoboProxyConfiguration.json';
        if (!file_exists($file)) {
            throw new \RuntimeException('Kobo config not found');
        }

        try {
            /** @var array{'Resources': array<string,mixed>}|string $data */
            $data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                throw new \RuntimeException('Invalid JSON, not an array');
            }
        } catch (\JsonException $e) {
            throw new \RuntimeException('Invalid JSON', 0, $e);
        }

        return $data;
    }

    public function setUseProxyEverywhere(bool $useProxyEverywhere): self
    {
        $this->useProxyEverywhere = $useProxyEverywhere;

        return $this;
    }

    public function getReadingServiceUrl(): string
    {
        if ($this->readingServiceUrl === '') {
            throw new \InvalidArgumentException('Reading Service URL is not set');
        }

        return $this->readingServiceUrl;
    }

    public function setReadingServiceUrl(string $readingServiceUrl): self
    {
        $this->readingServiceUrl = $readingServiceUrl;

        return $this;
    }
}
