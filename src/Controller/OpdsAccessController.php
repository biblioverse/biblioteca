<?php

namespace App\Controller;

use App\Entity\OpdsAccess;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OpdsAccessController extends AbstractController
{
    #[Route('/opds-access/new', name: 'app_opds_access_new', methods: ['GET', 'POST'])]
    public function new(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $opdsAccess = new OpdsAccess($user);

        $opdsAccess->setToken(bin2hex(random_bytes(16)));

        $entityManager->persist($opdsAccess);
        $entityManager->flush();

        $this->addFlash('success', 'New OPDS access created');

        return $this->redirectToRoute('app_user_profile', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/opds-access/{id}', name: 'app_opds_access_delete')]
    public function delete(OpdsAccess $opdsAccess, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($opdsAccess);
        $entityManager->flush();

        $this->addFlash('success', 'OPDS access deleted');

        return $this->redirectToRoute('app_user_profile', [], Response::HTTP_SEE_OTHER);
    }
}
