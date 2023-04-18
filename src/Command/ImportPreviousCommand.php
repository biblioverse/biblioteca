<?php

namespace App\Command;

use App\Entity\Article;
use App\Entity\Library;
use App\Entity\Page;
use App\Repository\ArticleRepository;
use App\Repository\LibraryRepository;
use App\Repository\OrderRepository;
use App\Repository\PageRepository;
use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:import-previous',
    description: 'Add a short description for your command',
)]
class ImportPreviousCommand extends Command
{
    private KernelInterface $appKernel;
    private ArticleRepository $articleRepository;
    private MarkdownParserInterface $parser;
    private LibraryRepository $libraryRepository;
    private PageRepository $pageRepository;

    public function __construct(
        KernelInterface $appKernel,
        ArticleRepository $articleRepository,
        MarkdownParserInterface $parser,
        LibraryRepository $libraryRepository,
        PageRepository $pageRepository
    )
    {
        parent::__construct();
        $this->appKernel = $appKernel;
        $this->articleRepository = $articleRepository;
        $this->parser = $parser;
        $this->libraryRepository = $libraryRepository;
        $this->pageRepository = $pageRepository;
    }

    protected function configure(): void
    {

    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $previousFolder = $this->appKernel->getProjectDir() . '/../previous';
        $previousContentFolder = $previousFolder . '/content/articles';

        $finder = new Finder();
        $finder->files()->name('*.json')->in($previousContentFolder);

        $filesystem = new Filesystem();

        $filesystem->remove($this->appKernel->getProjectDir() . '/public/images/*.*');

        $existingArticles = $this->articleRepository->findAll();
        foreach ($existingArticles as $library) {
            $this->articleRepository->remove($library, true);
        }

        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();
            $fileNameWithExtension = $file->getRelativePathname();
            $io->note('Working on ' . $fileNameWithExtension);

            $contents = json_decode(file_get_contents($absoluteFilePath), true);

            $library = new Article();
            $library->setTitle($contents['title']);
            $library->setHeading($contents['heading'] ?? '');
            $library->setBody($this->parser->transformMarkdown($contents['body']));
            $library->setType($contents['type']);
            $library->setPublished(true);
            $library->setCreated(new \DateTime($contents['date']));
            $this->articleRepository->save($library, true);

            if (array_key_exists('image', $contents)) {
                $image = $contents['image'];
                $ext = explode('.', $image);
                $ext = end($ext);
                if ($filesystem->exists($previousFolder . '/static/' . $image)) {
                    $filesystem->copy($previousFolder . '/static/' . $image, $this->appKernel->getProjectDir() . '/public/images/' . $library->getSlug() . '.' . $ext);
                    $library->setImage($library->getSlug() . '.' . $ext);
                    $this->articleRepository->save($library, true);
                }

            }
        }


        $io->success('Maintenant les librairies');
        $previousContentFolder = $previousFolder . '/content';

        $finder = new Finder();
        $finder->files()->name(['dorigny.json','chauderon.json','obsession.json'])->depth('<2')->in($previousContentFolder);

        $existingLibraries = $this->libraryRepository->findAll();
        foreach ($existingLibraries as $library) {
            $this->libraryRepository->remove($library, true);
        }

        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();
            $fileNameWithExtension = $file->getRelativePathname();
            $io->note('Working on ' . $fileNameWithExtension);

            $contents = json_decode(file_get_contents($absoluteFilePath), true);

            $library = new Library();
            $library->setName($contents['title']);
            $library->setAddress($contents['address']??'');
            $library->setSchedule($this->parser->transformMarkdown($contents['timetable']??''));
            $library->setPhone($contents['phone']??'');
            $library->setEmail($contents['mail']??'');
            $library->setBody($this->parser->transformMarkdown($contents['description']??$contents['content']));
            $library->setCanOrderOnOrderPage(true);
            $this->libraryRepository->save($library, true);
        }


        $io->success('Maintenant les pages');


        $previousContentFolder = $previousFolder . '/content/coop';

        $finder = new Finder();
        $finder->files()->name('cooperative.json')->in($previousContentFolder);

        $existingPages = $this->pageRepository->findAll();
        foreach ($existingPages as $page) {
            $this->pageRepository->remove($page, true);
        }

        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();
            $fileNameWithExtension = $file->getRelativePathname();
            $io->note('Working on ' . $fileNameWithExtension);

            $contents = json_decode(file_get_contents($absoluteFilePath), true);

            $page = new Page();
            $page->setTitle($contents['title']);
            $page->setBody($this->parser->transformMarkdown($contents['content']));

            $this->pageRepository->save($page, true);
        }


        return Command::SUCCESS;
    }
}
