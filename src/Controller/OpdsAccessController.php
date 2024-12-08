<?php

namespace App\Controller;

use App\Entity\OpdsAccess;
use App\Form\OpdsAccessType;
use App\Repository\OpdsAccessRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/opds-access')]
final class OpdsAccessController extends AbstractController
{
    #[Route(name: 'app_opds_access_index', methods: ['GET'])]
    public function index(OpdsAccessRepository $opdsAccessRepository): Response
    {
        return $this->render('opds_access/index.html.twig', [
            'opds_accesses' => $opdsAccessRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_opds_access_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ): Response
    {
        $opdsAccess = new OpdsAccess();

        $opdsAccess->setToken(bin2hex(random_bytes(16)));
        $opdsAccess->setUser($this->getUser());
        $entityManager->persist($opdsAccess);
        $entityManager->flush();

        $this->addFlash('success', 'New OPDS access created');

        return $this->redirectToRoute('app_opds_access_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_opds_access_delete')]
    public function delete(Request $request, OpdsAccess $opdsAccess, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($opdsAccess);
        $entityManager->flush();

        $this->addFlash('success', 'OPDS access deleted');

        return $this->redirectToRoute('app_opds_access_index', [], Response::HTTP_SEE_OTHER);
    }
}
