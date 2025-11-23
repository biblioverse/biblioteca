<?php

namespace App\Tests\Service;

use App\Entity\Book;
use App\Entity\EreaderEmail;
use App\Entity\User;
use App\Service\EreaderEmailService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EreaderEmailServiceTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private EreaderEmailService $service;
    private string $tempFile;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->service = new EreaderEmailService(
            $this->mailer,
            25, // maxFileSize in MB
            'noreply@biblioteca.test',
            'Biblioteca Test'
        );

        // Create a temporary test file
        $this->tempFile = sys_get_temp_dir().'/test_book_'.uniqid().'.epub';
        file_put_contents($this->tempFile, 'Test book content');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testGetMaxFileSize(): void
    {
        $maxSize = $this->service->getMaxFileSize();
        self::assertEquals(25 * 1024 * 1024, $maxSize);
    }

    public function testValidateFileSizeWithValidFile(): void
    {
        $result = $this->service->validateFileSize($this->tempFile);
        self::assertTrue($result);
    }

    public function testValidateFileSizeWithNonExistentFile(): void
    {
        $result = $this->service->validateFileSize('/path/to/nonexistent/file.epub');
        self::assertFalse($result);
    }

    public function testValidateFileSizeWithLargeFile(): void
    {
        // Create a file larger than 25MB
        $largeFile = sys_get_temp_dir().'/large_book_'.uniqid().'.epub';
        $largeContent = str_repeat('x', 26 * 1024 * 1024); // 26MB
        file_put_contents($largeFile, $largeContent);

        $result = $this->service->validateFileSize($largeFile);
        self::assertFalse($result);

        unlink($largeFile);
    }

    public function testSendBookSuccessfully(): void
    {
        $book = $this->createBook();
        $ereaderEmail = $this->createEreaderEmail();

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(fn (Email $email) => $email->getTo()[0]->getAddress() === $ereaderEmail->getEmail()
                && $email->getFrom()[0]->getAddress() === 'noreply@biblioteca.test'
                && $email->getFrom()[0]->getName() === 'Biblioteca Test'
                && str_contains((string) $email->getSubject(), 'Test Book')
                && str_contains((string) $email->getSubject(), 'Test Author')));

        $this->service->sendBook($book, $ereaderEmail, $this->tempFile);
    }

    public function testSendBookWithFileSizeExceedingLimit(): void
    {
        $book = $this->createBook();
        $ereaderEmail = $this->createEreaderEmail();

        // Create a file larger than 25MB
        $largeFile = sys_get_temp_dir().'/large_book_'.uniqid().'.epub';
        $largeContent = str_repeat('x', 26 * 1024 * 1024); // 26MB
        file_put_contents($largeFile, $largeContent);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File size exceeds maximum allowed size of 25 MB');

        try {
            $this->service->sendBook($book, $ereaderEmail, $largeFile);
        } finally {
            unlink($largeFile);
        }
    }

    public function testSendBookWithNonExistentFile(): void
    {
        $book = $this->createBook();
        $ereaderEmail = $this->createEreaderEmail();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File size exceeds maximum allowed size');

        $this->service->sendBook($book, $ereaderEmail, '/path/to/nonexistent/file.epub');
    }

    public function testSendBookWithMailerException(): void
    {
        $book = $this->createBook();
        $ereaderEmail = $this->createEreaderEmail();

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->willThrowException(new TransportException('SMTP connection failed'));

        $this->expectException(\RuntimeException::class);

        $this->service->sendBook($book, $ereaderEmail, $this->tempFile);
    }

    public function testSendBookWithMultipleAuthors(): void
    {
        $book = $this->createBook();
        $book->setAuthors(['Author One', 'Author Two', 'Author Three']);
        $ereaderEmail = $this->createEreaderEmail();

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(fn (Email $email) => str_contains((string) $email->getSubject(), 'Author One, Author Two, Author Three')));

        $this->service->sendBook($book, $ereaderEmail, $this->tempFile);
    }

    public function testSendBookWithNoAuthors(): void
    {
        $book = $this->createBook();
        $book->setAuthors([]);
        $ereaderEmail = $this->createEreaderEmail();

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(fn (Email $email) => str_contains((string) $email->getSubject(), 'Unknown Author')));

        $this->service->sendBook($book, $ereaderEmail, $this->tempFile);
    }

    private function createBook(): Book
    {
        $book = new Book();
        $book->setTitle('Test Book');
        $book->setAuthors(['Test Author']);
        $book->setBookFilename('test-book.epub');
        $book->setExtension('epub');
        $book->setChecksum('test-checksum');
        $book->setBookPath('');

        return $book;
    }

    private function createEreaderEmail(): EreaderEmail
    {
        $user = new User();
        $user->setUsername('test@example.com');
        $user->setPassword('hashed-password');
        $user->setRoles(['ROLE_USER']);

        $ereaderEmail = new EreaderEmail();
        $ereaderEmail->setName('My Kindle');
        $ereaderEmail->setEmail('kindle@example.com');
        $ereaderEmail->setUser($user);

        return $ereaderEmail;
    }
}
