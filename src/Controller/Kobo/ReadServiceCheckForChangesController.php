<?php

namespace App\Controller\Kobo;

use App\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReadServiceCheckForChangesController extends AbstractController
{
    #[Route('/api/v3/content/checkforchanges', name: 'check_for_changes', methods: ['POST'])]
    public function checkForChanges(): Response
    {
        // If you set "reading_services_host" on your Kobo's config you should point here.
        // This endpoint tells the kobo that the book reading status has not changed.
        // If you don't implement it, opening a book on the kobo will reset your progress
        // to the beginning of the current chapter.
        return new JsonResponse([]);
    }
}
