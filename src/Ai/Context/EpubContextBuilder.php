<?php

namespace App\Ai\Context;

use App\Entity\Book;
use App\Service\BookFileSystemManagerInterface;
use Kiwilan\Ebook\Ebook;
use Kiwilan\Ebook\Formats\Epub\EpubModule;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class EpubContextBuilder implements ContextBuildingInteface
{
    public function __construct(private readonly BookFileSystemManagerInterface $bookFileSystemManager, #[Autowire(param: 'AI_CONTEXT_FULL_EPUB')] private readonly bool $enable)
    {
    }

    #[\Override]
    public function isEnabled(): bool
    {
        return $this->enable;
    }

    #[\Override]
    public function getContextForPrompt(Book $book): string
    {
        if ($book->getExtension() !== 'epub') {
            return '';
        }
        $prompt = '';

        $bookFile = $this->bookFileSystemManager->getBookFile($book);

        try {
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
        } catch (\Exception) {
            return '';
        }

        $prompt = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", '', $prompt);

        return preg_replace("/([\n]|[\r])+/", ' ', (string) $prompt) ?? '';
    }
}
