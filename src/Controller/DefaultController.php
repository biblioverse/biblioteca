<?php

namespace App\Controller;

use Andante\PageFilterFormBundle\PageFilterFormTrait;
use App\Ai\CommunicatorDefiner;
use App\Form\BookFilterType;
use App\Repository\BookInteractionRepository;
use App\Repository\BookRepository;
use App\Service\FilteredBookUrlGenerator;
use App\Suggestion\TagPrompt;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DefaultController extends AbstractController
{
    use PageFilterFormTrait;

    #[Route('/', name: 'app_dashboard')]
    public function index(BookRepository $bookRepository, BookInteractionRepository $bookInteractionRepository): Response
    {
        $exts = $bookRepository->countBooks(false);
        $types = $bookRepository->countBooks(true);

        $books = $bookInteractionRepository->getStartedBooks();
        $readList = $bookInteractionRepository->getFavourite(6);

        $series = $bookRepository->getStartedSeries()->getResult();

        $tags = $bookRepository->getAllTags();

        $keys = $tags === [] ? [] : array_rand($tags, min(count($tags), 4));

        if (!is_array($keys)) {
            $keys = [];
        }

        $inspiration = [];
        foreach ($keys as $key) {
            $randomBooks = $bookRepository->findByTag($tags[$key]['item'], 6);
            $inspiration[] = [
                ...$tags[$key],
                'books' => $randomBooks,
            ];
        }

        return $this->render('default/dashboard.html.twig', [
            'extensions' => $exts,
            'types' => $types,
            'books' => $books,
            'readlist' => $readList,
            'series' => $series,
            'inspiration' => $inspiration,
        ]);
    }

    #[Route('/reading-list', name: 'app_readinglist')]
    public function readingList(BookRepository $bookRepository, BookInteractionRepository $bookInteractionRepository): Response
    {
        $readList = $bookInteractionRepository->getFavourite(hideFinished: false);

        $statuses = [
            'unread' => [],
            'finished' => [],
        ];
        foreach ($readList as $bookInteraction) {
            if ($bookInteraction->isFinished()) {
                $statuses['finished'][] = $bookInteraction->getBook();
            } else {
                $statuses['unread'][] = $bookInteraction->getBook();
            }
        }

        return $this->render('default/readingList.html.twig', [
            'readlist' => $statuses,
        ]);
    }

    #[Route('/all/{page}', name: 'app_allbooks', requirements: ['page' => '\d+'])]
    public function allbooks(Request $request, BookRepository $bookRepository, FilteredBookUrlGenerator $filteredBookUrlGenerator, PaginatorInterface $paginator, int $page = 1): Response
    {
        $qb = $bookRepository->getAllBooksQueryBuilder();

        $form = $this->createAndHandleFilter(BookFilterType::class, $qb, $request);

        if ($request->getQueryString() === null) {
            $modifiedParams = $filteredBookUrlGenerator->getParametersArrayForCurrent();

            return $this->redirectToRoute('app_allbooks', ['page' => 1, ...$modifiedParams]);
        }

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $page,
            18
        );

        if ($page > ($pagination->getTotalItemCount() / 18) + 1) {
            return $this->redirectToRoute('app_allbooks', ['page' => 1, ...$request->query->all()]);
        }

        return $this->render('default/index.html.twig', [
            'pagination' => $pagination,
            'page' => $page,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/timeline/{type?}/{year?}', name: 'app_timeline', requirements: ['page' => '\d+'])]
    public function timeline(?string $type, ?string $year, BookRepository $bookRepository, FilteredBookUrlGenerator $filteredBookUrlGenerator, PaginatorInterface $paginator, int $page = 1): Response
    {
        $redirectType = $type ?? 'all';
        $redirectYear = $year ?? date('Y');
        if ($redirectYear !== $year || $redirectType !== $type) {
            return $this->redirectToRoute('app_timeline', ['type' => $redirectType, 'year' => $redirectYear]);
        }

        if ($year === 'null') {
            $year = null;
        }

        $qb = $bookRepository->getReadBooks($year, $type);

        $types = $bookRepository->getReadTypes();
        $years = $bookRepository->getReadYears();

        $books = $qb->getQuery()->getResult();

        return $this->render('default/timeline.html.twig', [
            'books' => $books,
            'year' => $year,
            'type' => $type,
            'types' => $types,
            'years' => $years,
        ]);
    }


    #[Route('/prompter', name: 'app_prompter')]
    public function prompter(CommunicatorDefiner $communicatorDefiner, BookRepository $bookRepository, HttpClientInterface $client): Response
    {
        $communicator = $communicatorDefiner->getCommunicator();

        $book = $bookRepository->find(18911);

        $tagPrompt = new TagPrompt($book, null);

        $response = $client->request('GET', 'https://www.goodreads.com/search?utf8=%E2%9C%93&query='.urlencode($book->getAuthors()[0].' '.$book->getSerie()));


        $crawler = new Crawler($response->getContent(), baseHref: 'https://www.goodreads.com/');


        $prompt = 'Knowing that I  found this information about the author books: ';

        $linkCrawler = $crawler->filter('a.bookTitle');
        foreach ($linkCrawler->links() as $link) {
            $subLink = $client->request('GET', $link->getUri());
            $subCrawler = new Crawler($subLink->getContent(), baseHref: 'https://www.goodreads.com/');

            $info = '';
            foreach ([
                         'div.BookPageTitleSection','div.BookPageMetadataSection__description','div.BookPageMetadataSection__genres'
                     ] as $region){

                $filtered = $subCrawler->filter($region);
                if( $filtered->count() > 0) {
                    $info .=' '. strip_tags($filtered->html());
                }
            }

            $prompt .= '  
'.$info.'  
';

        }

        $prompt = $prompt.'    '.$tagPrompt->getPrompt();

        $tagPrompt->setPrompt($prompt);

        dump($prompt);

        $result = $communicator->interrogate($tagPrompt);

        dd($result);

        return $this->render('default/timeline.html.twig', [
            'books' => $books,
            'year' => $year,
            'type' => $type,
            'types' => $types,
            'years' => $years,
        ]);
    }
}
