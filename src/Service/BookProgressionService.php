<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\BookInteraction;
use App\Entity\User;
use App\Enum\ReadStatus;
use Doctrine\ORM\EntityManagerInterface;
use Kiwilan\Ebook\Ebook;
use Psr\Log\LoggerInterface;

class BookProgressionService
{
    public function __construct(
        private readonly BookFileSystemManagerInterface $fileSystemManager,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return float|null Progression between 0 and 1 or null if the total number of pages of a book is unknown
     */
    public function getProgression(Book $book, User $user): ?float
    {
        // Read from book entity (null > 0 is falsy)
        $readPages = $book->getLastInteraction($user)?->getReadPages();
        $nbPages = $this->processPageNumber($book);
        if ($nbPages === null || $nbPages === 0 || $readPages === null) {
            return null;
        }

        return $readPages / $nbPages;
    }

    /**
     * @param float|null $progress Percentage between 0 and 1
     * @return $this
     */
    public function setProgression(Book $book, User $user, ?float $progress): self
    {
        if ($progress === null) {
            $interaction = $book->getLastInteraction($user);
            if (!$interaction instanceof BookInteraction) {
                $interaction = new BookInteraction();
                $interaction->setBook($book);
                $interaction->setUser($user);
                $book->addBookInteraction($interaction);
                $this->em->persist($interaction);
            }
            $interaction->setReadPages(null);
            $interaction->setReadStatus(ReadStatus::NotStarted);

            return $this;
        }

        if ($progress < 0 || $progress > 1) {
            throw new \InvalidArgumentException('Progress must be between 0 and 1');
        }
        $nbPages = $this->processPageNumber($book);
        if ($nbPages === null) {
            return $this;
        }

        $readPages = $nbPages * $progress;
        $interaction = $book->getLastInteraction($user);
        if (!$interaction instanceof BookInteraction) {
            $interaction = new BookInteraction();
            $interaction->setBook($book);
            $interaction->setUser($user);
            $this->em->persist($interaction);
            $book->addBookInteraction($interaction);
        }
        $interaction->setReadPages((int) $readPages);
        if ($progress >= 1.0) {
            $interaction->setReadStatus(ReadStatus::Finished);
        } else {
            $interaction->setReadStatus(ReadStatus::Started);
        }

        return $this;
    }

    public function flush(): self
    {
        $this->em->flush();

        return $this;
    }

    public function processPageNumber(Book $book, bool $force = false): ?int
    {
        // Read from book entity (null > 0 is falsy)
        if ($book->getPageNumber() > 0 && $force === false) {
            return $book->getPageNumber();
        }

        // Read from the file
        try {
            $file = $this->fileSystemManager->getBookFile($book);
            $ebook = Ebook::read($file->getRealPath());
            if ($ebook instanceof Ebook) {
                $count = $ebook->getPagesCount();
                $book->setPageNumber($count);

                return $count;
            }

            return null;
        } catch (\Exception $e) {
            $this->logger->error('Error counting book pages', ['exception' => $e]);

            return null;
        }
    }
}
