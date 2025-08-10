<?php

namespace App\Controller;

use App\Entity\LibraryFolder;
use App\Form\LibraryFolderType;
use App\Repository\LibraryFolderRepository;
use App\Service\DefaultLibrary;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/library/folder')]
final class LibraryFolderController extends AbstractController
{
    #[Route(name: 'app_library_folder_index', methods: ['GET'])]
    public function index(LibraryFolderRepository $libraryFolderRepository): Response
    {
        return $this->render('library_folder/index.html.twig', [
            'library_folders' => $libraryFolderRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_library_folder_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $libraryFolder = new LibraryFolder();
        $form = $this->createForm(LibraryFolderType::class, $libraryFolder);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($libraryFolder);
            $entityManager->flush();

            return $this->redirectToRoute('app_library_folder_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('library_folder/new.html.twig', [
            'library_folder' => $libraryFolder,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_library_folder_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, LibraryFolder $libraryFolder, EntityManagerInterface $entityManager, DefaultLibrary $defaultLibrary): Response
    {
        $form = $this->createForm(LibraryFolderType::class, $libraryFolder);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($libraryFolder->isDefaultLibrary() && $defaultLibrary->folderData()->getId() !== $libraryFolder->getId()) {
                $defaultLibrary->folderData()->setDefaultLibrary(false);
            }

            if (!$libraryFolder->isDefaultLibrary() && $defaultLibrary->folderData()->getId() === $libraryFolder->getId()) {
                throw $this->createAccessDeniedException('At Least One Default Library Folder Must Exist');
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_library_folder_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('library_folder/edit.html.twig', [
            'library_folder' => $libraryFolder,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_library_folder_delete', methods: ['POST'])]
    public function delete(Request $request, LibraryFolder $libraryFolder, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$libraryFolder->getId(), $request->getPayload()->getString('_token'))) {
            if ($libraryFolder->isDefaultLibrary()) {
                throw $this->createAccessDeniedException('You cannot delete the default library folder.');
            }

            $entityManager->remove($libraryFolder);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_library_folder_index', [], Response::HTTP_SEE_OTHER);
    }
}
