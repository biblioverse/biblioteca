<?php

namespace App\Controller;

use App\Entity\Shelf;
use App\Form\ShelfType;
use App\Repository\ShelfRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/shelves/crud')]
class ShelfCrudController extends AbstractController
{
    public function __construct(private readonly ShelfRepository $shelfRepository)
    {
    }

    #[Route('/', name: 'app_shelf_crud_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('shelf_crud/index.html.twig', [
            'shelves' => $this->shelfRepository->findBy(['user' => $this->getUser()]),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_shelf_crud_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Shelf $shelf, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ShelfType::class, $shelf);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_shelf_crud_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('shelf_crud/edit.html.twig', [
            'shelf' => $shelf,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_shelf_crud_delete')]
    public function delete(Shelf $shelf, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($shelf);
        $entityManager->flush();

        return $this->redirectToRoute('app_shelf_crud_index', [], Response::HTTP_SEE_OTHER);
    }
}
