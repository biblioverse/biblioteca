<?php

namespace App\Controller;

use App\Entity\BookInteraction;
use App\Entity\Kobo;
use App\Kobo\Proxy\KoboStoreProxy;
use App\Kobo\Request\ReadingStates;
use App\Kobo\Request\ReadingStateStatusInfo;
use App\Kobo\Response\StateResponse;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/kobo/{accessKey}', name: 'kobo')]
class KoboStateController extends AbstractController
{
    public function __construct(
        protected BookRepository $bookRepository,
        protected KoboStoreProxy $koboStoreProxy,
        protected SerializerInterface $serializer,
        protected EntityManagerInterface $em,
    ) {
    }

    /**
     * Update reading state.
     **/
    #[Route('/v1/library/{uuid}/state', name: 'api_endpoint_state_put', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['PUT'])]
    public function state(Kobo $kobo, string $uuid, Request $request): Response|JsonResponse
    {
        $book = $this->bookRepository->findByUuidAndKobo($uuid, $kobo);

        // Handle book not found
        if ($book === null) {
            if ($this->koboStoreProxy->isEnabled()) {
                return $this->koboStoreProxy->proxy($request);
            }

            return new JsonResponse(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
        }

        // Deserialize request
        /** @var ReadingStates $entity */
        $entity = $this->serializer->deserialize($request->getContent(), ReadingStates::class, 'json');

        if (count($entity->readingStates) === 0) {
            return new JsonResponse(['error' => 'No reading state provided'], Response::HTTP_BAD_REQUEST);
        }
        $state = $entity->readingStates[0];
        $interactions = $book->getBookInteractions();
        $interaction = $interactions->current();
        if ($interaction === false) {
            $interaction = new BookInteraction();
            $interaction->setBook($book);
            $interactions->add($interaction);
            $this->em->persist($interaction);
        }

        $interaction->setUpdated($state->lastModified);
        $interaction->setFinished($state->statusInfo?->status === ReadingStateStatusInfo::STATUS_FINISHED);

        $this->em->flush();

        return new StateResponse($book);
    }

    /**
     * @throws GuzzleException
     */
    #[Route('/v1/library/{uuid}/state', name: 'api_endpoint_v1_getstate', requirements: ['uuid' => '^[a-zA-Z0-9\-]+$'], methods: ['GET'])]
    public function getState(Kobo $kobo, string $uuid, Request $request): Response|JsonResponse
    {
        if ($this->koboStoreProxy->isEnabled()) {
            return $this->koboStoreProxy->proxyOrRedirect($request);
        }
        throw new HttpException(200, 'Not implemented');
    }
}
