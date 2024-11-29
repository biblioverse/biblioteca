<?php

namespace App\Kobo\Proxy;

use App\Security\KoboTokenExtractor;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
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

    public function __construct(
        protected KoboProxyLoggerFactory $koboProxyLoggerFactory,
        protected KoboProxyConfiguration $configuration,
        protected LoggerInterface $koboProxyLogger,
        protected KoboTokenExtractor $tokenExtractor,
        protected ?ClientInterface $client = null,
    ) {
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

        $url = $this->getUpstreamUrl($request);

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
     * @throws GuzzleException
     */
    public function proxyOrRedirect(Request $request, array $options = []): Response
    {
        $this->assertEnabled();

        if ($request->isMethod('GET')) {
            return new RedirectResponse((string) $this->getTransformedUrl($request), Response::HTTP_TEMPORARY_REDIRECT);
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

        $client = $this->getClient($request);

        $psrResponse = $client->send($psrRequest, [
            'base_uri' => $hostname,
            'http_errors' => false,
            'connect_timeout' => 5,
        ] + $config
        );

        return $this->convertResponse($psrResponse, $config['stream'] ?? true);
    }

    protected function getTransformedUrl(Request $request): UriInterface
    {
        $psrRequest = $this->toPsrRequest($request);
        $upstreamUrl = $this->getUpstreamUrl($request);

        return $this->transformUrl($psrRequest, $upstreamUrl);
    }

    private function transformUrl(ServerRequestInterface $psrRequest, string $hostnameOrUrl): UriInterface
    {
        $host = parse_url($hostnameOrUrl, PHP_URL_HOST);
        $host = $host === false ? $hostnameOrUrl : $host;
        $host = $host ?? $hostnameOrUrl;
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
        $upstreamUrl = $this->getUpstreamUrl($request);

        $psrRequest = $this->convertRequest($request, $upstreamUrl);

        $accessToken = $this->tokenExtractor->extractAccessToken($request) ?? 'unknown';

        $client = $this->getClient($request);

        return $client->sendAsync($psrRequest, [
            'base_uri' => $upstreamUrl,
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

        $psrResponse = $this->cleanupPsrResponse($psrResponse);

        return $httpFoundationFactory->createResponse($psrResponse, $streamAllowed);
    }

    private function getUpstreamUrl(Request $request): string
    {
        $url = $this->configuration->isImageHostUrl($request) ? $this->configuration->getImageApiUrl() : $this->configuration->getStoreApiUrl();
        if ($this->configuration->isReadingServiceUrl($request)) {
            $url = $this->configuration->getReadingServiceUrl();
        }

        return $url;
    }

    private function getConfig(array $config): array
    {
        // By default, we do not follow redirects, except if explicitly asked otherwise
        $config['redirect.disable'] ??= true;

        // By default, we do stream, except if explicitly asked otherwise
        $config['stream'] ??= true;

        return $config;
    }

    public function setClient(?ClientInterface $client): void
    {
        $this->client = $client;
    }

    private function getClient(Request $request): ClientInterface
    {
        if ($this->client instanceof ClientInterface) {
            return $this->client;
        }

        $accessToken = $this->tokenExtractor->extractAccessToken($request) ?? 'unknown';

        return new Client([
            'handler' => $this->koboProxyLoggerFactory->createStack($accessToken),
        ]);
    }
}
