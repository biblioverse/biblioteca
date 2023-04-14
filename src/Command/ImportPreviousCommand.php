<?php

namespace App\Command;

use App\Entity\Article;
use App\Repository\ArticleRepository;
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

    public function __construct(KernelInterface $appKernel, ArticleRepository $articleRepository, MarkdownParserInterface $parser)
    {
        parent::__construct();
        $this->appKernel = $appKernel;
        $this->articleRepository = $articleRepository;
        $this->parser = $parser;
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
        foreach ($existingArticles as $article) {
            $this->articleRepository->remove($article, true);
        }

        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();
            $fileNameWithExtension = $file->getRelativePathname();
            $io->note('Working on ' . $fileNameWithExtension);

            $contents = json_decode(file_get_contents($absoluteFilePath), true);

            $article = new Article();
            $article->setTitle($contents['title']);
            $article->setHeading($contents['heading'] ?? '');
            $article->setBody($this->parser->transformMarkdown($contents['body']));
            $article->setType($contents['type']);
            $article->setPublished(true);
            $article->setCreated(new \DateTime($contents['date']));
            $this->articleRepository->save($article, true);

            if (array_key_exists('image', $contents)) {
                $image = $contents['image'];
                $ext = explode('.', $image);
                $ext = end($ext);
                if ($filesystem->exists($previousFolder . '/static/' . $image)) {
                    $filesystem->copy($previousFolder . '/static/' . $image, $this->appKernel->getProjectDir() . '/public/images/' . $article->getSlug() . '.' . $ext);
                    $article->setImage($article->getSlug() . '.' . $ext);
                    $this->articleRepository->save($article, true);
                }

            }


        }


        return Command::SUCCESS;
    }
}
