<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConfigurationController extends AbstractController
{
    #[Route('/configuration', name: 'app_configuration', methods: ['GET'])]
    public function index(ParameterBagInterface $parameterBagInterface): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $documentedParams = [
            'ALLOW_BOOK_RELOCATION' => 'Allow book relocation',
            'BOOK_FOLDER_NAMING_FORMAT' => '{authorFirst}/{author}/{serie}/{title}',
            'BOOK_FILE_NAMING_FORMAT' => '{serie}-{serieIndex}-{title',
            'KOBO_PROXY_ENABLED' => 'Is the Kobo proxy enabled?',
            'KOBO_PROXY_USE_EVERYWHERE' => 'Use the Kobo proxy everywhere.',
            'KOBO_API_URL' => 'Url of the Kobo API. See the kobo instructions for more information',
            'KOBO_IMAGE_API_URL' => 'Url of the Kobo Image API. See the kobo instructions for more information',
            'KEPUBIFY_BIN' => 'Path to kepubify binary',
            'KOBO_READINGSERVICES_URL' => 'Url of the Kobo Reading Services. See the kbo instructions for more information',
            'TYPESENSE_KEY' => 'Typesense API key',
            'TYPESENSE_URL' => 'Typesense URL',
            'OPEN_AI_API_KEY' => 'OpenAI API key. Required if you want to enable completions with chatGpt',
            'OPEN_AI_MODEL' => 'OpenAI model to use',
            'OLLAMA_URL' => 'Url of the Ollama API. do not forget the trailing slash. Example: http://ollama:11434/api/',
            'OLLAMA_MODEL' => 'Ollama model to use',
            'AI_CONTEXT_AMAZON_ENABLED' => 'Do you want to give context from Amazon to AI completions? This will scrape the Amazon website. Use with caution.',
            'AI_CONTEXT_FULL_EPUB' => 'Do you want to give the full epub context to AI completions? This will cause the prompt to be very long and could increase costs when using a paid model.',
            'WIKIPEDIA_API_TOKEN' => 'Wikipedia API token. Required if you want to give context from Wikipedia to AI. You will need to generate a personal api token in wikipedia.org',
        ];
        foreach ($documentedParams as $key => $value) {
            $documentedParams[$key] = [
                'value' => $parameterBagInterface->get($key),
                'description' => $value,
            ];
        }

        return $this->render('configuration/index.html.twig', [
            'documentedParams' => $documentedParams,
        ]);
    }
}
