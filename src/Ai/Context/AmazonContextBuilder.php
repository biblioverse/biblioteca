<?php

namespace App\Ai\Context;

use App\Entity\AiModel;
use App\Entity\Book;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AmazonContextBuilder implements ContextBuildingInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly CacheInterface $contextPool,
        private readonly SluggerInterface $slugger,
    ) {
    }

    private function getHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:133.0) Gecko/20100101 Firefox/133.0',
            'Accept-language' => 'en-US,en;q=0.9',
        ];
    }

    protected function query(string $path, array $params): array
    {
        $url = 'https://www.amazon.com/'.$path.'?'.http_build_query($params);
        $response = $this->client->request('GET', $url, [
            'headers' => $this->getHeaders(),
        ]);

        return ['content' => $response->getContent()];
    }

    protected function search(string $query, int $limit = 5): array
    {
        return $this->contextPool->get('amz_s_'.$this->slugger->slug($query), function () use ($query) {
            $results = $this->query('/s', ['k' => $query]);

            $crawler = new Crawler($results['content'], 'https://www.amazon.com/');
            $return = [];

            foreach ($crawler->filter('div[data-cy="title-recipe"]>a')->links() as $link) {
                if (!str_contains($link->getUri(), '/sspa/click') && str_starts_with($link->getUri(), 'https://www.amazon.com')) {
                    $return[] = $link->getUri();
                }
            }

            return array_unique($return);
        });
    }

    protected function getPage(string $key): array
    {
        return $this->contextPool->get('amz_p_'.$this->slugger->slug($key), function () use ($key) {
            $rel = str_replace('https://www.amazon.com', '', $key);

            $results = $this->query($rel, []);

            $crawler = new Crawler($results['content'], 'https://www.amazon.com/');

            $descDiv = $crawler->filter('#bookDescription_feature_div');
            $descr = '';
            if ($descDiv->count() > 0) {
                $descr = $crawler->filter('#bookDescription_feature_div')->html();
            }
            $titleDiv = $crawler->filter('#productTitle');
            $title = '';
            if ($titleDiv->count() > 0) {
                $title = $crawler->filter('#productTitle')->html();
            }

            $descr = strip_tags($descr);
            $title = strip_tags($title);
            $descr = ltrim($descr, "\n");
            $descr = trim($descr);
            $title = ltrim($title);

            return ['source' => $descr, 'title' => $title];
        });
    }

    #[\Override]
    public function isEnabled(AiModel $aiModel, ?Book $book = null): bool
    {
        return $aiModel->isUseAmazonContext();
    }

    #[\Override]
    public function getContextForPrompt(Book $book): string
    {
        $prompt = '';
        $pages = [];
        foreach ($book->getAuthors() as $author) {
            $pages = array_merge($pages, $this->search($author.' '.$book->getSerie().' '.$book->getTitle()));
            if ($book->getSerie() !== null) {
                $pages = array_merge($pages, $this->search($book->getSerie()));
            }
            $pages = array_merge($pages, $this->search($author.' '.$book->getTitle()));
        }
        foreach ($pages as $searchresult) {
            $page = $this->getPage($searchresult);
            if ($page['source'] !== '') {
                $prompt .= '
The following block of text is a summary for '.$page['title'].':
'.$page['source'].' 

';
            }
        }

        return $prompt;
    }
}
