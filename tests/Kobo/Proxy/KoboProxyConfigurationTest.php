<?php

namespace App\Tests\Kobo\Proxy;

use App\Kobo\Proxy\KoboProxyConfiguration;
use App\Tests\TestCaseHelperTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class KoboProxyConfigurationTest extends KernelTestCase
{
    use TestCaseHelperTrait;

    public function testGetNativeInitializationJson(): void
    {
        $data = $this->getService(KoboProxyConfiguration::class)->getNativeInitializationJson();
        self::assertArrayHasKey('image_url_template', $data['Resources']);
        $imageTemplate = $data['Resources']['image_url_template'];
        self::assertIsString($imageTemplate);
        self::assertStringContainsString('/{ImageId}/{Width}/{Height}/false/image.jpg', $imageTemplate);
    }
}
