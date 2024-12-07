<?php

namespace App\Controller\Kobo\Api;

use App\Controller\Kobo\AbstractKoboController;
use App\Entity\KoboDevice;
use App\Kobo\Proxy\KoboStoreProxy;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/kobo/{accessKey}', name: 'kobo_')]
class ApiEndpointController extends AbstractKoboController
{
    public function __construct(
        protected KoboStoreProxy $koboStoreProxy,
    ) {
    }

    #[Route('/', name: 'api_endpoint')]
    public function index(KoboDevice $koboDevice): Response
    {
        return new Response(sprintf('<html><body>%s</body></html>', htmlentities('Hello Kobo "'.($koboDevice->getName() ?? $koboDevice->getId()).'"')));
    }
}
