<?php

namespace App\Controller\Kobo\Api\V1;

use App\Controller\Kobo\AbstractKoboController;
use App\Entity\KoboDevice;
use App\Entity\Shelf;
use App\Kobo\Proxy\KoboStoreProxy;
use App\Kobo\Request\TagDeleteRequest;
use App\Repository\ShelfRepository;
use Doctrine\ORM\NonUniqueResultException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/kobo/{accessKey}/v1/library/tags', name: 'kobo_')]
class TagController extends AbstractKoboController
{
    public function __construct(
        protected ShelfRepository $shelfRepository,
        protected KoboStoreProxy $koboStoreProxy,
        protected SerializerInterface $serializer,
        protected LoggerInterface $koboSyncLogger,
    ) {
    }

    /**
     * @throws GuzzleException
     *                         Yep, a POST for a DELETE, it's how Kobo does it
     */
    #[Route('/{shelfId}/items/delete', methods: ['POST'])]
    public function delete(Request $request, KoboDevice $koboDevice, string $shelfId): Response
    {
        $shelf = $this->shelfRepository->findByKoboAndUuid($koboDevice, $shelfId);

        // Proxy query if we do not know the shelf
        if ($this->koboStoreProxy->isEnabled() && !$shelf instanceof Shelf) {
            $response = $this->koboStoreProxy->proxy($request);

            // Avoid the Kobo to send the request over and over again by marking it successful
            if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
                $this->koboSyncLogger->debug('Shelf not found locally and via the proxy, marking deletion as successful anyway', ['shelfId' => $shelfId]);

                return new JsonResponse($shelfId, Response::HTTP_CREATED);
            }

            return $response;
        }

        /** @var TagDeleteRequest $deleteRequest */
        $deleteRequest = $this->serializer->deserialize($request->getContent(false), TagDeleteRequest::class, 'json');
        $this->koboSyncLogger->debug('Tag delete request', ['request' => $deleteRequest]);

        try {
            if (!$shelf instanceof Shelf) {
                throw $this->createNotFoundException(sprintf('Shelf with uuid %s not found', $shelfId));
            }
        } catch (NonUniqueResultException $e) {
            throw new BadRequestException('Invalid shelf id', 0, $e);
        }

        foreach ($shelf->getBooks() as $book) {
            if ($deleteRequest->hasItem($book)) {
                $shelf->removeBook($book);
            }
        }
        $this->shelfRepository->flush();

        return new JsonResponse($shelfId, Response::HTTP_CREATED);
    }

    #[Route('/')]
    #[Route('/{tagId}')]
    public function tags(Request $request, KoboDevice $koboDevice, ?string $tagId = null): Response
    {
        try {
            $content = $request->getContent();
            /** @var array<string,string|null>|null $data */
            $data = trim($content) === '' ? null : json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new BadRequestException('Invalid JSON', $e->getCode(), $e);
        }
        $name = (string) ($data['Name'] ?? null);
        $name = trim($name) === '' ? null : $name;
        $shelf = $this->findShelfByNameOrTagId($koboDevice, $name, $tagId);

        if ($request->isMethod('DELETE')) {
            if ($shelf instanceof Shelf) {
                $this->koboSyncLogger->debug('Removing kobo from shelf', ['shelf' => $shelf, 'kobo' => $koboDevice]);
                $shelf->removeKoboDevice($koboDevice);
                $this->shelfRepository->flush();

                return new JsonResponse(['deleted'], Response::HTTP_OK);
            }
            if ($this->koboStoreProxy->isEnabled()) {
                $this->koboSyncLogger->debug('Proxying request to delete tag {id}', ['id' => $tagId]);

                $proxyResponse = $this->koboStoreProxy->proxy($request);
                if ($proxyResponse->getStatusCode() === Response::HTTP_NOT_FOUND) {
                    return new JsonResponse(['unable to delete tag, skipped.'], Response::HTTP_OK);
                }

                return $proxyResponse;
            }
        }

        if (!$shelf instanceof Shelf) {
            throw new NotFoundHttpException(sprintf('Shelf %s not found', $name ?? $tagId));
        }

        // TODO Add items to shelf
        // $data["Items"];

        return new JsonResponse($shelf->getId(), Response::HTTP_CREATED);
    }

    private function findShelfByNameOrTagId(KoboDevice $koboDevice, ?string $name, ?string $tagId): ?Shelf
    {
        if ($tagId !== null) {
            return $this->shelfRepository->findByKoboAndUuid($koboDevice, $tagId);
        }

        if ($name === null || trim($name) === '') {
            throw new BadRequestException('Name is required');
        }

        return $this->shelfRepository->findByKoboAndName($koboDevice, $name);
    }
}
