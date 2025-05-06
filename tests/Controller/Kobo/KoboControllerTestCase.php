<?php

namespace App\Tests\Controller\Kobo;

use App\Kobo\Kepubify\KepubifyEnabler;
use App\Kobo\Proxy\KoboProxyConfiguration;
use App\Kobo\Proxy\KoboStoreProxy;
use App\Tests\TestCaseHelperTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpFoundation\Response;

abstract class KoboControllerTestCase extends WebTestCase
{
    use TestCaseHelperTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        self::createClient();
    }

    protected static function getRawResponse(): Response
    {
        self::assertInstanceOf(AbstractBrowser::class, self::getClient());

        /** @var Response $response */
        $response = self::getClient()->getResponse();

        return $response;
    }

    protected static function getJsonResponse(): array
    {
        $response = self::getRawResponse();
        self::assertResponseIsSuccessful();
        $content = $response->getContent();

        if ($content === false) {
            static::fail('Unable to read response content');
        }

        try {
            return (array) json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('Invalid JSON', 0, $exception);
        }
    }

    protected function getKepubifyEnabler(): KepubifyEnabler
    {
        $service = self::getContainer()->get(KepubifyEnabler::class);
        self::assertInstanceOf(KepubifyEnabler::class, $service);

        return $service;
    }

    protected function getKoboStoreProxy(): KoboStoreProxy
    {
        $service = self::getContainer()->get(KoboStoreProxy::class);
        self::assertInstanceOf(KoboStoreProxy::class, $service);

        return $service;
    }

    protected function getKoboProxyConfiguration(): KoboProxyConfiguration
    {
        $service = self::getContainer()->get(KoboProxyConfiguration::class);
        self::assertInstanceOf(KoboProxyConfiguration::class, $service);

        return $service;
    }

    protected function enableRemoteSync(): void
    {
        // Enable remote sync
        $this->getKoboDevice()->setUpstreamSync(true);
        $this->getKoboProxyConfiguration()->setEnabled(true);
        $this->getEntityManager()->flush();
    }
}
