<?php

namespace App\Controller;

use App\Entity\EreaderEmail;
use App\Entity\User;
use App\Form\EreaderEmailType;
use App\Repository\EreaderEmailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/user/ereader-email')]
class EreaderEmailController extends AbstractController
{
    #[Route('/', name: 'app_ereader_email_index', methods: ['GET'])]
    public function index(EreaderEmailRepository $ereaderEmailRepository): Response
    {
        if (!$this->getUser() instanceof UserInterface) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('ereader_email/index.html.twig', [
            'ereader_emails' => $ereaderEmailRepository->findAllByUser($this->getUser()),
        ]);
    }

    #[Route('/new', name: 'app_ereader_email_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $ereaderEmail = new EreaderEmail();
        $ereaderEmail->setUser($user);

        if (!$this->isGranted('CREATE', $ereaderEmail)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EreaderEmailType::class, $ereaderEmail);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ereaderEmail);
            $entityManager->flush();

            $this->addFlash('success', 'E-reader email added successfully');

            return $this->redirectToRoute('app_user_profile', ['tab' => 'ereader'], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ereader_email/new.html.twig', [
            'ereader_email' => $ereaderEmail,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ereader_email_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EreaderEmail $ereaderEmail, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('EDIT', $ereaderEmail)) {
            throw $this->createAccessDeniedException('You don\'t have permission to edit this e-reader email');
        }

        $form = $this->createForm(EreaderEmailType::class, $ereaderEmail);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'E-reader email updated successfully');

            return $this->redirectToRoute('app_user_profile', ['tab' => 'ereader'], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ereader_email/edit.html.twig', [
            'ereader_email' => $ereaderEmail,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ereader_email_delete', methods: ['POST'])]
    public function delete(Request $request, EreaderEmail $ereaderEmail, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('DELETE', $ereaderEmail)) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$ereaderEmail->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($ereaderEmail);
            $entityManager->flush();

            $this->addFlash('success', 'E-reader email deleted successfully');
        }

        return $this->redirectToRoute('app_user_profile', ['tab' => 'ereader'], Response::HTTP_SEE_OTHER);
    }
}
