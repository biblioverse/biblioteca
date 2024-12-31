<?php

namespace App\Ai\Context;

use App\Entity\Book;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @codeCoverageIgnore
 */
class WikipediaContextBuilder implements ContextBuildingInteface
{
    private string $language = 'en';

    public function __construct(private readonly HttpClientInterface $client, #[Autowire(param: 'WIKIPEDIA_API_TOKEN')] private readonly ?string $token, private readonly CacheInterface $wikipediaPool, private readonly SluggerInterface $slugger)
    {
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    public function getHeaders(): array
    {
        if ($this->token === null) {
            throw new \Exception('No token provided');
        }

        return [
            'Accept' => 'application/json',
            'User-Agent' => 'books/1.0 (biblioteca@example.com)',
            'Authorization' => 'Bearer '.$this->token,
        ];
    }

    protected function query(string $path, array $params): array
    {
        $url = 'https://api.wikimedia.org/core/v1/wikipedia/'.$this->language.$path.'?'.http_build_query($params);
        $response = $this->client->request('GET', $url, [
            'headers' => $this->getHeaders(),
        ]);

        $decoded = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new \Exception('Invalid response from wikipedia');
        }

        return $decoded;
    }

    protected function search(string $query, int $limit = 5): array
    {
        return $this->wikipediaPool->get('search_'.$this->language.$this->slugger->slug($query), function () use ($query, $limit) {
            $results = $this->query('/search/page', ['q' => $query, 'limit' => $limit]);

            $pages = $results['pages'];
            $return = [];
            foreach ($pages as $page) {
                $key = $page['key'];
                $return[] = $key;
            }

            return array_unique($return);
        });
    }

    protected function getPage(string $key): array
    {
        return $this->wikipediaPool->get($this->language.$this->slugger->slug($key), fn () => $this->query('/page/'.$key, []));
    }

    #[\Override]
    public function isEnabled(): bool
    {
        return $this->token !== null;
    }

    #[\Override]
    public function getContextForPrompt(Book $book): string
    {
        $pages = [];

        $prompt = '';

        foreach ($book->getAuthors() as $author) {
            $pages = array_merge($pages, $this->search($author.' '.$book->getSerie().' '.$book->getTitle()));
            $pages = array_merge($pages, $this->search($author.' '.$book->getSerie()));
            if (count($pages) < 5) {
                $pages = array_merge($pages, $this->search($author.' '.$book->getSerie()));
            }
        }
        foreach ($pages as $searchresult) {
            $page = $this->getPage($searchresult);
            $prompt .= '

The following block of text is a summary for '.$page['title'].' 
'.$page['source'].' 

';
        }

        return $prompt;
    }
}
