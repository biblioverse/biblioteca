<?php

namespace App\Service;

use App\Entity\Book;
use Exception;
use Kiwilan\Ebook\Ebook;
use Kiwilan\Ebook\EbookCover;
use Kiwilan\Ebook\Tools\BookAuthor;
use RuntimeException;
use \SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use ZipArchive;

/**
 * @phpstan-type MetadataType array{ title:string, authors: BookAuthor[], main_author: ?BookAuthor, description: ?string, publisher: ?string, publish_date: ?\DateTime, language: ?string, tags: string[], serie:?string, serie_index: ?int, cover: ?EbookCover }
 */
class BookManager
{
    public KernelInterface $appKernel;
    private BookFileSystemManager $fileSystemManager;

    public function __construct(KernelInterface $appKernel, BookFileSystemManager $fileSystemManager)
    {
        $this->appKernel = $appKernel;
        $this->fileSystemManager = $fileSystemManager;
    }



    /**
     * @throws Exception
     */
    public function createBook(SplFileInfo $file):Book
    {
        $book = new Book();

        $extractedMetadata = $this->extractEbookMetadata($file);
        $book->setTitle($extractedMetadata['title']);
        $book->setChecksum($this->fileSystemManager->getFileChecksum($file));
        $book->setMainAuthor('unknown');
        if($extractedMetadata['main_author']!==null) {
            $book->setMainAuthor($extractedMetadata['main_author']->getName()??'unknown');
        }

        foreach ($extractedMetadata['authors'] as $author){
            $book->addAuthor($author->getName()??'unknown');
        }
        $book->setSummary($extractedMetadata['description']);
        if($extractedMetadata['serie']!==null) {
            $book->setSerie($extractedMetadata['serie']);
            $book->setSerieIndex($extractedMetadata['serie_index']);
        }
        $book->setPublisher($extractedMetadata['publisher']);
        $book->setPublishDate($extractedMetadata['publish_date']);
        if(strlen($extractedMetadata['language']??'')===2){
            $book->setLanguage($extractedMetadata['language']);
        }

        $book->setExtension($file->getExtension());
        $book->setTags($extractedMetadata['tags']);

        $book->setBookPath('');
        $book->setBookFilename('');
        $book = $this->updateBookLocation($book,$file);

        /** @var ?EbookCover $cover */
        $cover = $extractedMetadata['cover'];

        if($cover!==null && $cover->getPath()!==null) {

            $coverContent = $cover->getContent();

            $coverFileName = explode('/',$cover->getPath());
            $coverFileName = end($coverFileName);
            $ext = explode('.', $coverFileName);
            $book->setImageExtension(end($ext));

            $coverPath = $this->fileSystemManager->getCalculatedImagePath($book,true);
            $coverFileName = $this->fileSystemManager->getCalculatedImageName($book);

            $filesystem = new Filesystem();
            $filesystem->mkdir($coverPath);

            $coverFile = file_put_contents($coverPath.$coverFileName, $coverContent);

            if ($coverFile !== false) {
                $book->setImagePath($this->fileSystemManager->getCalculatedImagePath($book,false));
                $book->setImageFilename($coverFileName);
            }

        }

        return $book;
    }

    public function updateBookLocation(Book $book, SplFileInfo $file):Book
    {
        $path = $this->fileSystemManager->getFolderName($file);
        if($path!==$book->getBookPath()){
            $book->setBookPath($path);
        }
        if($file->getFilename()!==$book->getBookFilename()){
            $book->setBookFilename($file->getFilename());
        }

        return $book;
    }

    /**
     * @param SplFileInfo $file
     * @return MetadataType
     * @throws Exception
     */
    public function extractEbookMetadata(SplFileInfo $file):array
    {
        try {

            if(!Ebook::isValid($file->getRealPath())){
                throw new RuntimeException('Could not read ebook' . $file->getRealPath());
            }

            $ebook = Ebook::read($file->getRealPath());
            if ($ebook === null) {

                throw new RuntimeException('Could not read ebook');
            }
        }catch (\Exception $e){
            return [
                'title'=>$file->getFilename(),
                'authors'=>[new BookAuthor('unknown')], // BookAuthor[] (`name`: string, `role`: string)
                'main_author'=>new BookAuthor('unknown'), // ?BookAuthor => First BookAuthor (`name`: string, `role`: string)
                'description'=>null, // ?string
                'publisher'=>null, // ?string
                'publish_date'=>null, // ?DateTime
                'language'=>null, // ?string
                'tags'=>[], // string[] => `subject` in EPUB, `keywords` in PDF, `genres` in CBA
                'serie'=>null, // ?string => `calibre:series` in EPUB, `series` in CBA
                'serie_index'=>null, // ?int => `calibre:series_index` in EPUB, `number` in CBA
                'cover'=>null, //  ?EbookCover => cover of book
            ];
        }
        return [
            'title'=>$ebook->getTitle()??$file->getFilename(), // string
            'authors'=>$ebook->getAuthors(), // BookAuthor[] (`name`: string, `role`: string)
            'main_author'=>$ebook->getAuthorMain(), // ?BookAuthor => First BookAuthor (`name`: string, `role`: string)
            'description'=>$ebook->getDescription(), // ?string
            'publisher'=>$ebook->getPublisher(), // ?string
            'publish_date'=>$ebook->getPublishDate(), // ?DateTime
            'language'=>$ebook->getLanguage(), // ?string
            'tags'=>$ebook->getTags(), // string[] => `subject` in EPUB, `keywords` in PDF, `genres` in CBA
            'serie'=>$ebook->getSeries(), // ?string => `calibre:series` in EPUB, `series` in CBA
            'serie_index'=>$ebook->getVolume(), // ?int => `calibre:series_index` in EPUB, `number` in CBA
            'cover'=>$ebook->getCover(), //  ?EbookCover => cover of book
        ];

    }
}