<?php

namespace App\Controller\Kobo;

use App\Entity\KoboDevice;
use App\Entity\Shelf;
use App\Kobo\Proxy\KoboStoreProxy;
use App\Kobo\Request\TagDeleteRequest;
use App\Repository\ShelfRepository;
use Doctrine\ORM\NonUniqueResultException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/kobo/{accessKey}', name: 'app_kobo')]
class KoboTagController extends AbstractController
{
    public function __construct(
        protected ShelfRepository $shelfRepository,
        protected KoboStoreProxy $koboStoreProxy,
        protected SerializerInterface $serializer,
        protected LoggerInterface $logger)
    {
    }

    /**
     * @throws GuzzleException
     *                         Yep, a POST for a DELETE, it's how Kobo does it
     */
    #[Route('/v1/library/tags/{tagId}/items/delete', methods: ['POST'])]
    public function delete(Request $request, KoboDevice $kobo, string $tagId): Response
    {
        if ($this->koboStoreProxy->isEnabled()) {
            return $this->koboStoreProxy->proxy($request);
        }

        /** @var TagDeleteRequest $deleteRequest */
        $deleteRequest = $this->serializer->deserialize($request->getContent(false), TagDeleteRequest::class, 'json');
        $this->logger->debug('Tag delete request', ['request' => $deleteRequest]);

        try {
            $shelf = $this->shelfRepository->findByKoboAndUuid($kobo, $tagId);
            if (null === $shelf) {
                throw $this->createNotFoundException(sprintf('Shelf with uuid %s not found', $tagId));
            }
        } catch (NonUniqueResultException $e) {
            throw new BadRequestException('Invalid tag id', 0, $e);
        }

        foreach ($shelf->getBooks() as $book) {
            if ($deleteRequest->hasItem($book)) {
                $shelf->removeBook($book);
            }
        }
        $this->shelfRepository->flush();

        // TODO Find the response format for this
        return new JsonResponse([], Response::HTTP_NOT_IMPLEMENTED);
    }

    #[Route('/v1/library/tags')]
    #[Route('/v1/library/tags/{tagId}')]
    public function tags(Request $request, KoboDevice $kobo, ?string $tagId = null): Response
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
        $shelf = $this->findShelfByNameOrTagId($kobo, $name, $tagId);

        if ($request->isMethod('DELETE')) {
            if ($shelf !== null) {
                $this->logger->debug('Removing kobo from shelf', ['shelf' => $shelf, 'kobo' => $kobo]);
                $shelf->removeKoboDevice($kobo);
                $this->shelfRepository->flush();

                return new JsonResponse(['deleted'], 200);
            }
            if ($this->koboStoreProxy->isEnabled()) {
                $this->logger->debug('Proxying request to delete tag {id}', ['id' => $tagId]);

                $proxyResponse = $this->koboStoreProxy->proxy($request);
                if ($proxyResponse->getStatusCode() === 404) {
                    return new JsonResponse(['unable to delete tag, skipped.'], 200);
                }

                return $proxyResponse;
            }
        }

        if (null === $shelf) {
            throw new NotFoundHttpException(sprintf('Shelf %s not found', $name ?? $tagId));
        }

        // TODO Add items to shelf
        // $data["Items"];

        return new JsonResponse($shelf->getId(), 201);
    }

    private function findShelfByNameOrTagId(KoboDevice $kobo, ?string $name, ?string $tagId): ?Shelf
    {
        if ($tagId !== null) {
            return $this->shelfRepository->findByKoboAndUuid($kobo, $tagId);
        }

        if ($name === null || trim($name) === '') {
            throw new BadRequestException('Name is required');
        }

        return $this->shelfRepository->findByKoboAndName($kobo, $name);
    }
}
