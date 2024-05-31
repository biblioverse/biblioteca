<?php

namespace App\Controller\Kobo;

use App\Entity\KoboDevice;
use App\Entity\User;
use App\Form\KoboType;
use App\Repository\KoboDeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user/kobo')]
class KoboDeviceController extends AbstractController
{
    #[Route('/', name: 'app_kobodevice_user_index', methods: ['GET'])]
    public function index(KoboDeviceRepository $koboDeviceRepository): Response
    {
        if ($this->getUser() === null) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('kobodevice_user/index.html.twig', [
            'kobos' => $koboDeviceRepository->findAllByUser($this->getUser()),
        ]);
    }

    #[Route('/new', name: 'app_kobodevice_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $koboDevice = new KoboDevice();
        $koboDevice->setUser($user);

        if (!$this->isGranted('CREATE', $koboDevice)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(KoboType::class, $koboDevice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($koboDevice);
            $entityManager->flush();

            return $this->redirectToRoute('app_kobodevice_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('kobodevice_user/new.html.twig', [
            'kobo' => $koboDevice,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_kobodevice_user_show', methods: ['GET'])]
    public function show(KoboDevice $koboDevice): Response
    {
        if (!$this->isGranted('VIEW', $koboDevice)) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('kobodevice_user/show.html.twig', [
            'kobo' => $koboDevice,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_kobodevice_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, KoboDevice $koboDevice, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('EDIT', $koboDevice)) {
            throw $this->createAccessDeniedException('You don\'t have permission to edit this koboDevice');
        }

        $form = $this->createForm(KoboType::class, $koboDevice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_kobodevice_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('kobodevice_user/edit.html.twig', [
            'kobo' => $koboDevice,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_kobodevice_user_delete', methods: ['POST'])]
    public function delete(Request $request, KoboDevice $koboDevice, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('DELETE', $koboDevice)) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$koboDevice->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($koboDevice);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_kobodevice_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
