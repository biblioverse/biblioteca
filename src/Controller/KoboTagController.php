<?php

namespace App\Controller;

use App\Entity\Kobo;
use App\Entity\Shelf;
use App\Kobo\Proxy\KoboStoreProxy;
use App\Repository\ShelfRepository;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/kobo/{accessKey}', name: 'app_kobo')]
class KoboTagController extends AbstractController
{
    public function __construct(
        protected ShelfRepository $shelfRepository,
        protected KoboStoreProxy $koboStoreProxy,
        protected LoggerInterface $logger)
    {
    }

    /**
     * @throws GuzzleException
     *                         Yep, a POST for a DELETE, it's how Kobo does it
     */
    #[Route('/v1/library/tags/{tagId}/items/delete', methods: ['POST'])]
    public function delete(Request $request): Response
    {
        if ($this->koboStoreProxy->isEnabled()) {
            return $this->koboStoreProxy->proxy($request);
        }
        throw $this->createNotFoundException('Deleting a tag item is not implemented yet');
    }

    #[Route('/v1/library/tags')]
    #[Route('/v1/library/tags/{tagId}')]
    public function tags(Request $request, Kobo $kobo, ?string $tagId = null): Response
    {
        try {
            /** @var array<string,string|null> $data */
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new BadRequestException('Invalid JSON', $e->getCode(), $e);
        }
        $name = (string) ($data['Name'] ?? null);
        $shelf = $this->findShelfByNameOrTagId($kobo, $name, $tagId);

        if ($request->isMethod('DELETE')) {
            if ($shelf !== null) {
                // TODO Delete shelf if it's not null
            }

            return new JsonResponse([], 405);
        }

        if (null === $shelf) {
            // TODO Create shelf with this name assigned to this kobo/user
            throw new NotFoundHttpException(sprintf('Shelf with name %s not found', $name));
        }

        // TODO Add items to shelf
        // $data["Items"];

        return new JsonResponse($shelf->getId(), 201);
    }

    private function findShelfByNameOrTagId(Kobo $kobo, ?string $name, ?string $tagId): ?Shelf
    {
        if ($tagId !== null) {
            return $this->shelfRepository->findByKoboAndId($kobo, $tagId);
        }

        if ($name === null || trim($name) === '') {
            throw new BadRequestException('Name is required');
        }

        return $this->shelfRepository->findByKoboAndName($kobo, $name);
    }
}
