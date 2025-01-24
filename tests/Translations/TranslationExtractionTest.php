<?php

namespace App\Tests\Translations;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslationExtractionTest extends KernelTestCase
{
    private TranslatorInterface $translator;
    /** @var array<string> */
    private array $locales = ['en', 'fr'];

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $translator = static::getContainer()->get('translator');
        self::assertInstanceOf(TranslatorInterface::class, $translator);
        $this->translator = $translator;
    }

    public function testAllTranslationsAreExtracted(): void
    {
        $domains = ['messages']; // Add all domains you use

        foreach ($this->locales as $locale) {
            foreach ($domains as $domain) {
                $translator = $this->translator;
                self::assertInstanceOf(Translator::class, $translator);
                $catalogue = $translator->getCatalogue($locale);
                $messages = $catalogue->all($domain);

                self::assertNotEmpty($messages, "No translations found for locale '$locale' in domain '$domain'");

                foreach ($messages as $key => $translation) {
                    self::assertNotEquals($key, $translation, "Translation for key '$key' in locale '$locale' and domain '$domain' is not translated");
                }
            }
        }
    }

    public function testNoMissingTranslations(): void
    {
        foreach ($this->locales as $locale) {
            $output = shell_exec('php bin/console debug:translation '.$locale.' --only-missing');
            if (!is_string($output)) {
                throw new \RuntimeException('The output of the debug:translation command is not a string');
            }
            self::assertStringNotContainsString('missing', $output, "There are missing translations:\n$output");
        }
    }
}
