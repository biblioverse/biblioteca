<?php

namespace App\Controller\Kobo\Api\V3;

use App\Controller\Kobo\AbstractKoboController;
use App\Kobo\Proxy\KoboStoreProxy;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContentController extends AbstractKoboController
{
    public function __construct(private readonly KoboStoreProxy $koboStoreProxy)
    {
    }

    #[Route('/api/v3/content/checkforchanges', name: 'check_for_changes', methods: ['POST'])]
    public function checkForChanges(): Response
    {
        // If you set "reading_services_host" on your Kobo's config you should point here.
        // This endpoint tells the kobo that the book reading status has not changed.
        // If you don't implement it, opening a book on the kobo will reset your progress
        // to the beginning of the current chapter.
        return new JsonResponse([]);
    }

    /**
     * @throws GuzzleException
     */
    #[Route('/api/v3/content/{uuid}/annotations', name: 'check_for_annotations', methods: ['GET'])]
    public function getAnnotations(Request $request): Response
    {
        if ($this->koboStoreProxy->isEnabled()) {
            $response = $this->koboStoreProxy->proxy($request);
            if ($response->isOk()) {
                return $response;
            }
        }

        return new JsonResponse([]);
    }
}
