<?php

namespace App\Tests\Controller\Kobo;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ApiEndpointControllerTest extends AbstractKoboControllerTest
{
    public function testIndex(): void
    {
        $client = static::getClient();
        if(!$client instanceof KernelBrowser){
            self::markTestSkipped('Profiler is not available');
        }

        $this->injectFakeFileSystemManager();

        $client->enableProfiler();
        $client->request(Request::METHOD_GET, '/kobo/'.$this->accessKey."/");
        self::assertResponseIsSuccessful();

        // Setup does 2 requests:
        // - Select kobo device by accessKey
        // - Select Book by id
        // -----------------------
        // 4 requests are made for the endpoint
        // - 1) Select KoboDevice by access key
        // - 2) begin transaction
        // - 3) Update user's last_login
        // - 4) commit transaction
        // We check that the number of queries is as expected
        self::assertLessThanOrEqual(
            2 + 4,
            $this->getNumberOfQueries($client)
        );
    }
}