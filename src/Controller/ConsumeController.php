<?php

namespace App\Controller;

use App\Entity\Book;
use App\Security\Voter\RelocationVoter;
use App\Service\BookFileSystemManagerInterface;
use App\Service\BookManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/books')]
class ConsumeController extends AbstractController
{
    public function __construct(
        private readonly BookManager $bookManager,
    ) {
    }

    #[Route('/new/consume/upload', name: 'app_book_upload_consume')]
    public function upload(Request $request, BookFileSystemManagerInterface $fileSystemManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You are not allowed to add books');

            return $this->redirectToRoute('app_dashboard');
        }

        $form = $this->createFormBuilder(options: ['label_translation_prefix' => 'upload.form.'])
            ->setMethod(Request::METHOD_POST)
            ->add('file', FileType::class, [
                'label' => 'Book',
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'accept' => '.epub,.pdf,.mobi,.cbr,.cbz',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Upload',
                'attr' => [
                    'class' => 'btn btn-success',
                ],
            ])
            ->getForm()
        ;
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<int, UploadedFile> $files */
            $files = (array) $form->get('file')->getData();
            if (count($files) > 0) {
                $fileSystemManager->uploadFilesToConsumeDirectory($files);

                return $this->redirectToRoute('app_book_consume');
            }
        }

        return $this->render('book/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new/consume/files', name: 'app_book_consume')]
    public function consume(Request $request, BookFileSystemManagerInterface $fileSystemManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'You are not allowed to add books');

            return $this->redirectToRoute('app_dashboard');
        }

        $bookFiles = $fileSystemManager->getAllBooksFiles(true);

        $bookFiles = iterator_to_array($bookFiles);

        // Sort book files by folder path first, then by filename
        uasort($bookFiles, function (\SplFileInfo $a, \SplFileInfo $b) {
            $pathA = dirname($a->getRealPath());
            $pathB = dirname($b->getRealPath());

            // First compare by directory
            $dirCompare = strcmp($pathA, $pathB);
            if ($dirCompare !== 0) {
                return $dirCompare;
            }

            // If same directory, compare by filename
            return strcmp($a->getFilename(), $b->getFilename());
        });

        $consume = $request->get('consume');
        if ($consume !== null) {
            set_time_limit(240);
            foreach ($bookFiles as $bookFile) {
                if ($bookFile->getRealPath() !== $consume) {
                    continue;
                }

                $book = $this->bookManager->consumeBook(new \SplFileInfo($bookFile->getRealPath()));
                $this->bookManager->save($book);

                $this->addFlash('success', 'Book '.$bookFile->getFilename().' consumed');

                return $this->redirectToRoute('app_book_consume');
            }
        }

        $delete = $request->get('delete');
        if ($delete !== null) {
            foreach ($bookFiles as $bookFile) {
                if ($bookFile->getRealPath() !== $delete) {
                    continue;
                }
                unlink($bookFile->getRealPath());
                $this->addFlash('success', 'Book '.$bookFile->getFilename().' deleted');

                return $this->redirectToRoute('app_book_consume');
            }
        }

        return $this->render('book/consume.html.twig', [
            'books' => $bookFiles,
        ]);
    }

    #[Route('/relocate/{id}/files', name: 'app_book_relocate')]
    public function relocate(Request $request, Book $book, BookFileSystemManagerInterface $fileSystemManager, EntityManagerInterface $entityManager): Response
    {
        try {
            if (!$this->isGranted(RelocationVoter::RELOCATE, $book)) {
                throw $this->createAccessDeniedException('Book relocation is not allowed');
            }
            $book = $fileSystemManager->renameFiles($book);
            $entityManager->persist($book);
            $entityManager->flush();
            $this->addFlash('success', 'Book relocated.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Error during relocation: '.$e->getMessage());
        }

        return $this->redirect($request->headers->get('referer') ?? '/');
    }
}
