<?php

namespace App\Controller;

use App\Entity\KoboDevice;
use App\Form\KoboType;
use App\Repository\KoboDeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/kobo')]
class KoboAdminController extends AbstractController
{
    #[Route('/', name: 'app_kobo_admin_index', methods: ['GET'])]
    public function index(KoboDeviceRepository $koboDeviceRepository): Response
    {
        return $this->render('kobo_admin/index.html.twig', [
            'kobos' => $koboDeviceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_kobo_admin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $koboDevice = new KoboDevice();
        $form = $this->createForm(KoboType::class, $koboDevice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($koboDevice);
            $entityManager->flush();

            return $this->redirectToRoute('app_kobo_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('kobo_admin/new.html.twig', [
            'kobo' => $koboDevice,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_kobo_admin_show', methods: ['GET'])]
    public function show(KoboDevice $kobo): Response
    {
        return $this->render('kobo_admin/show.html.twig', [
            'kobo' => $kobo,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_kobo_admin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, KoboDevice $kobo, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(KoboType::class, $kobo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_kobo_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('kobo_admin/edit.html.twig', [
            'kobo' => $kobo,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_kobo_admin_delete', methods: ['POST'])]
    public function delete(Request $request, KoboDevice $kobo, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$kobo->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($kobo);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_kobo_admin_index', [], Response::HTTP_SEE_OTHER);
    }
}
