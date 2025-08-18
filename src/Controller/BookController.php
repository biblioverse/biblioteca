<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\BookInteraction;
use App\Entity\User;
use App\Enum\ReadStatus;
use App\Repository\BookRepository;
use App\Repository\ShelfRepository;
use App\Security\Voter\BookVoter;
use App\Service\BookFileSystemManagerInterface;
use App\Service\BookProgressionService;
use App\Service\DefaultLibrary;
use App\Service\ThemeSelector;
use Biblioverse\TypesenseBundle\Exception\SearchException;
use Biblioverse\TypesenseBundle\Query\SearchQuery;
use Biblioverse\TypesenseBundle\Search\SearchCollectionInterface;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/all/{libraryFolder}')]
class BookController extends AbstractController
{
    public function __construct(
        private readonly BookProgressionService $bookProgressionService,
    ) {
    }

    #[Route('/{book}/{slug}', name: 'app_book')]
    public function index(Book $book, string $slug, DefaultLibrary $default, BookRepository $bookRepository, SearchCollectionInterface $searchBooks, BookFileSystemManagerInterface $fileSystemManager, ShelfRepository $shelfRepository): Response
    {
        if ($slug !== $book->getSlug()) {
            return $this->redirectToRoute('app_book', [
                'book' => $book->getId(),
                'slug' => $book->getSlug(),
            ], 301);
        }

        if (!$this->isGranted(BookVoter::VIEW, $book)) {
            $this->addFlash('danger', 'You are not allowed to view this book');

            return $this->redirectToRoute('app_dashboard', [
                'book' => $book->getId(),
                'slug' => $book->getSlug(),
            ], 301);
        }

        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createFormBuilder(options: ['label_translation_prefix' => 'book.form.'])
            ->setAction($this->generateUrl('app_book_delete', [
                'id' => $book->getId(),
                'libraryFolder' => $book->getLibraryFolder()?->getSlug() ?? $default->folderData()->getSlug(),
            ]))
            ->setMethod(Request::METHOD_POST)
            ->add('delete', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-danger',
                ],
            ])
            ->getForm();

        $serie = [];
        $serieMax = 0;
        if ($book->getSerie() !== null) {
            $booksInSerie = $bookRepository->findBySerie($book->getSerie());
            foreach ($booksInSerie as $bookInSerie) {
                $index = $bookInSerie->getSerieIndex();
                if ($index === 0.0 || floor($index ?? 0.0) !== $index) {
                    $index = '?';
                }
                $serie[$index] ??= [];
                $serie[$index][] = $bookInSerie;
            }
            $keys = array_filter(array_keys($serie), static fn ($key) => is_numeric($key));

            if ($keys !== []) {
                $serieMax = max($keys);
            }
        }

        $filter = null;
        if ($book->getSerie() !== null) {
            $filter = 'serie:!'.$book->getSerie();
        }

        $querySimilar = new SearchQuery(
            q: '*',
            queryBy: 'title',
            filterBy: $filter,
            vectorQuery: 'embedding:([], id: '.$book->getId().')',
            limit: 12
        );
        try {
            $similar = $searchBooks->search($querySimilar);
        } catch (SearchException) {
            $similar = [];
        }

        $calculatedPath = $fileSystemManager->getCalculatedFilePath($book, false).$fileSystemManager->getCalculatedFileName($book);
        $needsRelocation = $fileSystemManager->getCalculatedFilePath($book, false) !== $book->getBookPath();

        $interaction = $book->getLastInteraction($user);

        return $this->render('book/index.html.twig', [
            'book' => $book,
            'shelves' => $shelfRepository->findManualShelvesForUser($user),
            'serie' => $serie,
            'serieMax' => $serieMax,
            'similar' => $similar,
            'interaction' => $interaction,
            'form' => $form->createView(),
            'calculatedPath' => $calculatedPath,
            'needsRelocation' => $needsRelocation,
        ]);
    }

    #[Route('/{book}/{slug}/read', name: 'app_book_read')]
    public function read(
        Request $request,
        Book $book,
        string $slug,
        BookFileSystemManagerInterface $fileSystemManager,
        PaginatorInterface $paginator,
        ThemeSelector $themeSelector,
        EntityManagerInterface $manager,
    ): Response {
        set_time_limit(120);
        if ($slug !== $book->getSlug()) {
            return $this->redirectToRoute('app_book', [
                'book' => $book->getId(),
                'slug' => $book->getSlug(),
            ], 301);
        }

        if (!$this->isGranted(BookVoter::VIEW, $book)) {
            $this->addFlash('danger', 'You are not allowed to view this book');

            return $this->redirectToRoute('app_dashboard', [], 301);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('danger', 'You need to be logged in to read books');
            throw $this->createAccessDeniedException();
        }

        switch ($book->getExtension()) {
            case 'epub':
            case 'mobi':
                if ($request->isMethod('POST') && $request->headers->get('Content-Type') === 'application/json') {
                    return $this->updateProgression($request, $book, $user);
                }

                return $this->render('book/reader-files-epub.html.twig', [
                    'book' => $book,
                    'percent' => $this->bookProgressionService->getProgression($book, $user),
                    'file' => $fileSystemManager->getBookPublicPath($book),
                    'body_class' => $themeSelector->isDark() ? 'bg-darker' : '',
                    'isDark' => $themeSelector->isDark(),
                    'backUrl' => $this->generateUrl('app_book', [
                        'book' => $book->getId(),
                        'slug' => $book->getSlug(),
                    ]),
                ]);
            case 'pdf':
            case 'cbr':
            case 'cbz':
                $files = $fileSystemManager->extractFilesToRead($book);
                break;
            default:
                $this->addFlash('danger', 'Unsupported book format: '.$book->getExtension());

                return $this->redirectToRoute('app_book', [
                    'book' => $book->getId(),
                    'slug' => $book->getSlug(),
                ]);
        }

        if ($book->getPageNumber() !== count($files)) {
            $book->setPageNumber(count($files));
            $manager->flush();
        }
        $interaction = $manager->getRepository(BookInteraction::class)->findOneBy([
            'book' => $book,
            'user' => $this->getUser(),
        ]);
        if ($interaction === null) {
            $interaction = new BookInteraction();
            $interaction->setBook($book);
            $interaction->setUser($user);
        }

        $page = $request->get('page', $interaction->getReadPages() ?? 1);

        if (!is_numeric($page)) {
            $page = 1;
        }

        $page = (int) max(1, $page);

        if ($interaction->getReadStatus() !== ReadStatus::Finished && $interaction->getReadPages() < $page) {
            $interaction->setReadStatus(ReadStatus::Started);
            $interaction->setReadPages($page);
        }
        $manager->persist($interaction);
        $manager->flush();

        if ($interaction->getReadStatus() !== ReadStatus::Finished && $page === $book->getPageNumber()) {
            $interaction->setReadStatus(ReadStatus::Finished);

            // TODO Add next unread in serie to reading list?

            $interaction->setFinishedDate(new \DateTimeImmutable());
            $this->addFlash('success', 'Book finished! Congratulations!');
            $manager->flush();

            return $this->redirectToRoute('app_book', [
                'book' => $book->getId(),
                'slug' => $book->getSlug(),
            ]);
        }

        $pagination = $paginator->paginate(
            $files,
            $page,
            1
        );

        // @phpstan-ignore-next-line
        $pagination->setTemplate('book/_knp_minimal_pagination.html.twig');

        return $this->render('book/reader-files.html.twig', [
            'book' => $book,
            'page' => $page,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/extract-cover/{id}/fromFile', name: 'app_extractCover')]
    public function extractCover(Request $request, Book $book, EntityManagerInterface $entityManager, BookFileSystemManagerInterface $fileSystemManager): Response
    {
        if (!$this->isGranted(BookVoter::EDIT, $book)) {
            $this->addFlash('danger', 'You are not allowed to edit this book');

            return $this->redirectToRoute('app_book', [
                'book' => $book->getId(),
                'slug' => $book->getSlug(),
            ], 301);
        }

        $book = $fileSystemManager->extractCover($book);

        $entityManager->flush();

        $referer = $request->headers->get('referer');

        if ($referer !== null) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_book', [
            'book' => $book->getId(),
            'slug' => $book->getSlug(),
        ], 301);
    }

    #[Route('/delete/{id}/now', name: 'app_book_delete', methods: ['POST'])]
    public function deleteBook(int $id, EntityManagerInterface $entityManager, BookFileSystemManagerInterface $fileSystemManager): Response
    {
        /** @var Book $book */
        $book = $entityManager->getRepository(Book::class)->find($id);

        if (!$this->isGranted(BookVoter::EDIT, $book)) {
            $this->addFlash('danger', 'You are not allowed to delete this book');

            return $this->redirectToRoute('app_book', [
                'book' => $book->getId(),
                'slug' => $book->getSlug(),
            ], 301);
        }

        $fileSystemManager->deleteBookFiles($book);

        $entityManager->remove($book);

        $entityManager->flush();

        $this->addFlash('success', 'Book deleted');

        return $this->redirectToRoute('app_allbooks');
    }

    private function updateProgression(Request $request, Book $book, User $user): JsonResponse
    {
        try {
            /** @var array{percent?:string|float, cfi?:string} $json */
            $json = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new BadRequestException('Invalid percent in json', 0, $e);
        }
        $percent = $json['percent'] ?? null;
        $percent = $percent === null ? null : floatval((string) $percent);

        if ($percent !== null && $percent <= 1.0 && $percent >= 0) {
            $this->bookProgressionService->setProgression($book, $user, $percent)
                ->flush();

            return new JsonResponse([
                'percent' => $percent,
            ]);
        }
        throw new BadRequestException('Invalid percent in json: '.json_encode($json));
    }
}
