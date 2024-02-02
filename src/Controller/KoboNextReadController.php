<?php

namespace App\Controller;

use App\Kobo\Proxy\KoboStoreProxy;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/kobo/{accessKey}', name: 'kobo')]
class KoboNextReadController extends AbstractController
{
    public function __construct(protected KoboStoreProxy $koboStoreProxy)
    {
    }

    #[Route('/v1/products/{uuid}/nextread', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    public function nextRead(Request $request): Response
    {
        if ($this->koboStoreProxy->isEnabled()) {
            return $this->koboStoreProxy->proxyOrRedirect($request);
        }

        return new JsonResponse([]);
    }
}
