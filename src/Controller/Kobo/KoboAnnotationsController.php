<?php

namespace App\Controller\Kobo;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v3', name: 'kobo_apiv3')]
class KoboAnnotationsController extends AbstractKoboController
{
    /**
     * Update reading state.
     **/
    #[Route('/content/{uuid}/annotations', name: 'api_v3_annotations', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'])]
    public function state(string $uuid, Request $request): Response|JsonResponse
    {
        return new JsonResponse([]);
    }
}
