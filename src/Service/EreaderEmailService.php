<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\EreaderEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class EreaderEmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire('%env(SMTP_MAX_FILE_SIZE)%')]
        private readonly int $maxFileSize,
        #[Autowire('%env(SMTP_FROM_EMAIL)%')]
        private readonly string $fromEmail,
        #[Autowire('%env(SMTP_FROM_NAME)%')]
        private readonly string $fromName,
    ) {
    }

    /**
     * Get maximum file size in bytes
     */
    public function getMaxFileSize(): int
    {
        $maxSizeMB = $this->maxFileSize;

        return $maxSizeMB * 1024 * 1024;
    }

    /**
     * Validate file size
     */
    public function validateFileSize(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $fileSize = filesize($filePath);
        if ($fileSize === false) {
            return false;
        }

        return $fileSize <= $this->getMaxFileSize();
    }

    public function sendBook(Book $book, EreaderEmail $ereaderEmail, string $bookFilePath): void
    {
        if (!$this->validateFileSize($bookFilePath)) {
            $maxSizeMB = $this->maxFileSize;
            throw new \RuntimeException(sprintf('File size exceeds maximum allowed size of %d MB', $maxSizeMB));
        }

        $fromEmail = $this->fromEmail;
        $fromName = $this->fromName;

        $bookTitle = $book->getTitle();
        $authors = $book->getAuthors();
        $authorString = $authors === [] ? 'Unknown Author' : implode(', ', $authors);
        $filename = basename($book->getBookFilename());

        $email = (new Email())
            ->from(new Address($fromEmail, $fromName))
            ->to($ereaderEmail->getEmail() ?? '')
            ->subject(sprintf('Book: %s by %s', $bookTitle, $authorString))
            ->text(sprintf('Please find attached the book "%s" by %s.', $bookTitle, $authorString))
            ->attachFromPath($bookFilePath, $filename);

        $this->mailer->send($email);
    }
}
