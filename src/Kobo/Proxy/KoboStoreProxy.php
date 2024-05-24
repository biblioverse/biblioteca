<?php

namespace App\Kobo\Proxy;

use App\Security\KoboTokenExtractor;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class KoboStoreProxy
 * Inspired by https://github.com/janeczku/calibre-web/blob/master/cps/kobo.py
 */
class KoboStoreProxy
{
    use KoboHeaderFilterTrait;

    public function __construct(protected KoboProxyLoggerFactory $koboProxyLoggerFactory, protected KoboProxyConfiguration $configuration, protected LoggerInterface $proxyLogger, protected KoboTokenExtractor $tokenExtractor)
    {
    }

    protected function assertEnabled(): void
    {
        if (!$this->configuration->useProxy() && !$this->configuration->useProxyEverywhere()) {
            throw new \RuntimeException('Proxy is not enabled');
        }
    }

    /**
     * @throws GuzzleException
     */
    public function proxy(Request $request, array $options = []): Response
    {
        $this->assertEnabled();

        $url = $this->configuration->isImageHostUrl($request) ? $this->configuration->getImageApiUrl() : $this->configuration->getStoreApiUrl();

        return $this->_proxy($request, $url, $options);
    }

    /**
     * @throws GuzzleException
     */
    public function proxyAndFollowRedirects(Request $request, array $options): Response
    {
        $this->assertEnabled();
        $options = $this->getConfig($options);
        $options['redirect.disable'] = false;
        $options['redirect.max'] = 3;

        return $this->proxy($request, $options);
    }

    /**
     * @param Request $request
     * @param array $options
     * @return Response
     * @throws GuzzleException
     */
    public function proxyOrRedirect(Request $request, array $options = []): Response
    {
        $this->assertEnabled();

        if ($request->isMethod('GET')) {
            return new RedirectResponse((string) $this->getTransformedUrl($request), 307);
        }

        return $this->proxy($request, $options);
    }

    public function isEnabled(): bool
    {
        return $this->configuration->useProxy();
    }

    /**
     * @throws GuzzleException
     */
    private function _proxy(Request $request, string $hostname, array $config = []): Response
    {
        $config = $this->getConfig($config);
        $psrRequest = $this->convertRequest($request, $hostname);

        $accessToken = $this->tokenExtractor->extractAccessToken($request) ?? 'unknown';

        $client = new Client();
        $psrResponse = $client->send($psrRequest, [
            'base_uri' => $hostname,
            'handler' => $this->koboProxyLoggerFactory->createStack($accessToken),
            'http_errors' => false,
            'connect_timeout' => 5,
        ] + $config
        );

        return $this->convertResponse($psrResponse, $config['stream'] ?? true);
    }

    protected function getTransformedUrl(Request $request): UriInterface
    {
        $psrRequest = $this->toPsrRequest($request);
        $hostname = $this->configuration->isImageHostUrl($psrRequest) ? $this->configuration->getImageApiUrl() : $this->configuration->getStoreApiUrl();

        return $this->transformUrl($psrRequest, $hostname);
    }

    private function transformUrl(ServerRequestInterface $psrRequest, string $hostname): UriInterface
    {
        $host = parse_url($hostname, PHP_URL_HOST);
        $host = $host === false ? $hostname : $host;
        $host = $host ?? $hostname;
        $path = $this->tokenExtractor->getOriginalPath($psrRequest, $psrRequest->getUri()->getPath());

        return $psrRequest->getUri()->withHost($host)->withPath($path);
    }

    private function toPsrRequest(Request $request): ServerRequestInterface
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        return $psrHttpFactory->createRequest($request);
    }

    public function proxyAsync(Request $request, bool $streamAllowed): PromiseInterface
    {
        $hostname = $this->configuration->isImageHostUrl($request) ? $this->configuration->getImageApiUrl() : $this->configuration->getStoreApiUrl();
        $psrRequest = $this->convertRequest($request, $hostname);

        $accessToken = $this->tokenExtractor->extractAccessToken($request) ?? 'unknown';

        $client = new Client();

        return $client->sendAsync($psrRequest, [
            'base_uri' => $hostname,
            'handler' => $this->koboProxyLoggerFactory->createStack($accessToken),
            'http_errors' => false,
            'connect_timeout' => 5,
            'stream' => $streamAllowed,
        ])->then(function (ResponseInterface $response) {
            return $this->cleanupPsrResponse($response);
        });
    }

    private function convertRequest(Request $request, string $hostname): RequestInterface
    {
        $host = parse_url($hostname, PHP_URL_HOST);
        $host = $host === false ? $hostname : $host;
        $request->headers->set('Host', $host);
        $request->server->set('HTTPS', 'on'); // Force HTTPS (for cli)
        $psrRequest = $this->toPsrRequest($request);
        $psrRequest = $this->cleanup($psrRequest);

        $url = $this->transformUrl($psrRequest, $hostname);

        return $psrRequest->withUri($url);
    }

    private function convertResponse(ResponseInterface $psrResponse, bool $streamAllowed = true): Response
    {
        $httpFoundationFactory = new HttpFoundationFactory();

        $response = $httpFoundationFactory->createResponse($psrResponse, $streamAllowed);
        $this->cleanupResponse($response);

        return $response;
    }

    private function getConfig(array $config): array
    {
        // By default, we do not follow redirects, except if explicitly asked otherwise
        $config['redirect.disable'] ??= true;

        // By default, we do stream, except if explicitly asked otherwise
        $config['stream'] ??= true;

        return $config;
    }
}
