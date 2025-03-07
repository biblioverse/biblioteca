<?php

namespace App\Controller\Kobo\Api\V1;

use App\Controller\Kobo\AbstractKoboController;
use App\Kobo\Proxy\KoboStoreProxy;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/kobo/{accessKey}/v1/products', name: 'kobo_')]
class ProductsController extends AbstractKoboController
{
    public function __construct(protected KoboStoreProxy $koboStoreProxy)
    {
    }

    #[Route('/{uuid}/nextread', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    public function nextRead(Request $request): Response
    {
        if ($this->koboStoreProxy->isEnabled()) {
            return $this->koboStoreProxy->proxyOrRedirect($request);
        }

        return new JsonResponse([]);
    }
}
