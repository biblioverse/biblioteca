<?php

namespace App\Ai\Context;

use App\Entity\Book;
use App\Service\BookFileSystemManagerInterface;
use Kiwilan\Ebook\Ebook;
use Kiwilan\Ebook\Formats\Epub\EpubModule;

class EpubContextBuilder implements ContextBuildingInteface
{
    public function __construct(private readonly BookFileSystemManagerInterface $bookFileSystemManager)
    {
    }

    #[\Override]
    public function isEnabled(): bool
    {
        return false;
    }

    #[\Override]
    public function getContextForPrompt(Book $book): string
    {
        if ($book->getExtension() !== 'epub') {
            return '';
        }
        $prompt = '';

        $bookFile = $this->bookFileSystemManager->getBookFile($book);

        $ebook = Ebook::read($bookFile->getPathname());

        if (!$ebook instanceof Ebook) {
            return '';
        }

        $epub = $ebook->getParser()?->getEpub();

        if (!$epub instanceof EpubModule) {
            return '';
        }

        $htmlArray = $epub->getHtml();

        foreach ($htmlArray as $html) {
            $text = $html->getBody();
            $prompt .= strip_tags($text ?? '');
        }

        return $prompt;
    }
}
