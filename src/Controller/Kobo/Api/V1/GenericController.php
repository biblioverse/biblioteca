<?php

namespace App\Controller\Kobo\Api\V1;

use App\Controller\Kobo\AbstractKoboController;
use App\Kobo\Proxy\KoboStoreProxy;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GenericController extends AbstractKoboController
{
    public function __construct(
        protected KoboStoreProxy $koboStoreProxy,
    ) {
    }

    /**
     * @throws GuzzleException
     */
    #[Route('/kobo/{accessKey}/v1/user/loyalty/benefits')]
    public function benefits(Request $request): Response
    {
        if ($this->koboStoreProxy->isEnabled()) {
            return $this->koboStoreProxy->proxyOrRedirect($request);
        }

        return new JsonResponse(['Benefits' => new \stdClass()]);
    }

    /**
     * @throws GuzzleException
     */
    #[Route('/kobo/{accessKey}/v1/affiliate', methods: ['GET', 'POST'])] // ?PlatformID=00000000-0000-0000-0000-000000000384&SerialNumber=xxxxxxx
    #[Route('/kobo/{accessKey}/v1/assets', methods: ['GET'])]
    #[Route('/kobo/{accessKey}/v1/deals', methods: ['GET', 'POST'])]
    #[Route('/kobo/{accessKey}/v1/products', methods: ['GET', 'POST'])]
    #[Route('/kobo/{accessKey}/v1/products/books/external/{uuid}', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/kobo/{accessKey}/v1/products/books/series/{uuid}', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/kobo/{accessKey}/v1/products/books/{uuid}', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/kobo/{accessKey}/v1/products/books/{uuid}/', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/kobo/{accessKey}/v1/products/books/{uuid}/access', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/kobo/{accessKey}/v1/products/dailydeal', methods: ['GET', 'POST'])]
    #[Route('/kobo/{accessKey}/v1/products/deals', methods: ['GET', 'POST'])]
    #[Route('/kobo/{accessKey}/v1/products/featured/', methods: ['GET', 'POST'])]
    #[Route('/kobo/{accessKey}/v1/products/featured/{uuid}', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/kobo/{accessKey}/v1/products/{uuid}/prices', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/kobo/{accessKey}/v1/products/{uuid}/recommendations', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/kobo/{accessKey}/v1/user/recommendations/feedback', methods: ['GET', 'POST'])]
    #[Route('/kobo/{accessKey}/v1/products/{uuid}/reviews', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/kobo/{accessKey}/v1/user/profile')]
    #[Route('/kobo/{accessKey}/v1/configuration')]
    #[Route('/kobo/{accessKey}/v1/auth/device')]
    #[Route('/kobo/{accessKey}/v1/auth/refresh')]
    #[Route('/kobo/{accessKey}/v1/library/borrow')]
    #[Route('/kobo/{accessKey}/v1/auth/exchange')]
    #[Route('/kobo/{accessKey}/v1/library/{uuid}', methods: ['DELETE'])]
    #[Route('/kobo/{accessKey}/v1/user/recommendations', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET', 'POST'])]
    #[Route('/kobo/{accessKey}/v1/user/wishlist')] // ?PageSize=100&PageIndex=0
    public function proxy(Request $request, LoggerInterface $koboLogger): Response
    {
        $koboLogger->info('Kobo API Proxy request on '.$request->getPathInfo(), ['request' => $request->getContent(), 'headers' => $request->headers->all()]);

        return $this->koboStoreProxy->proxyOrRedirect($request);
    }
}
