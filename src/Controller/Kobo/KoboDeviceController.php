<?php

namespace App\Controller\Kobo;

use App\Entity\KoboDevice;
use App\Entity\User;
use App\Form\KoboType;
use App\Repository\KoboDeviceRepository;
use Devdot\Monolog\Parser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/user/kobo')]
class KoboDeviceController extends AbstractController
{
    #[Route('/', name: 'app_kobodevice_user_index', methods: ['GET'])]
    public function index(KoboDeviceRepository $koboDeviceRepository): Response
    {
        if (!$this->getUser() instanceof UserInterface) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('kobodevice_user/index.html.twig', [
            'kobos' => $koboDeviceRepository->findAllByUser($this->getUser()),
        ]);
    }

    #[Route('/logs', name: 'app_kobodevice_user_logs', methods: ['GET'])]
    public function logs(ParameterBagInterface $parameterBag): Response
    {
        if (!$this->getUser() instanceof UserInterface) {
            throw $this->createAccessDeniedException();
        }

        $records = [];

        try {
            $logDir = $parameterBag->get('kernel.logs_dir');
            $env = $parameterBag->get('kernel.environment');

            if (!is_string($logDir) || !is_string($env)) {
                throw new \RuntimeException('Invalid log directory or environment');
            }

            $parser = new Parser($logDir.'/proxy.'.$env.'-'.date('Y-m-d').'.log');

            $records = $parser->get();
        } catch (\Exception $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->render('kobodevice_user/logs.html.twig', [
            'records' => $records,
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
