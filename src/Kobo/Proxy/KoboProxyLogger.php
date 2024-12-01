<?php

namespace App\Kobo\Proxy;

use GuzzleHttp\Promise\Create;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class KoboProxyLogger
{
    use KoboHeaderFilterTrait;

    public function __construct(
        protected KoboProxyConfiguration $configuration,
        protected LoggerInterface $koboProxyLogger,
        protected string $accessToken,
    ) {
    }

    /**
     * Called when the middleware is handled by the client.
     */
    public function __invoke(callable $handler): \Closure
    {
        return function ($request, array $options) use ($handler) {
            return $handler($request, $options)->then(
                $this->onSuccess($request),
                $this->onFailure($request)
            );
        };
    }

    /**
     * Returns a function which is handled when a request was successful.
     */
    protected function onSuccess(RequestInterface $request): \Closure
    {
        return function ($response) use ($request) {
            $this->log($request, $response);

            return $response;
        };
    }

    /**
     * Returns a function which is handled when a request was rejected.
     */
    protected function onFailure(RequestInterface $request): \Closure
    {
        return function ($reason) use ($request) {
            $this->log($request, null, $reason);

            return Create::rejectionFor($reason);
        };
    }

    private function log(RequestInterface $request, ?ResponseInterface $response = null, ?\Throwable $error = null): void
    {
        try {
            $requestContent = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $requestContent = $request->getBody()->getContents();
        }
        $responseContent = $response?->getBody()->getContents();
        if ($responseContent !== null) {
            try {
                $responseContent = json_decode($responseContent, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
            }
        }

        $this->koboProxyLogger->info(sprintf($request->getMethod().': %s', (string) $request->getUri()), [
            'method' => $request->getMethod(),
            'status' => $response?->getStatusCode(),
            'token_hash' => md5($this->accessToken),
            'request' => $requestContent,
            'response' => $responseContent,
            'request_headers' => $request->getHeaders(),
            'response_headers' => $response?->getHeaders(),
        ]);

        if ($error instanceof \Throwable) {
            $this->koboProxyLogger->error('Proxy error: '.$error->getMessage(), [
                'exception' => $error,
                'token_hash' => md5($this->accessToken),
            ]);
        }
    }
}
