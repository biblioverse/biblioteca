<?php

namespace App\Service;

use Andante\PageFilterFormBundle\PageFilterFormTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WikipediaAPICaller
{
    use PageFilterFormTrait;

    private string $token='';
    private string $language='en';

    public function __construct(private HttpClientInterface $client)
    {
    }

    public function setLanguage(string $language){
        $this->language = $language;
    }


    public function getToken(): string
    {
        return '';
    }

    public function getHeaders(): array
    {
        if($this->token === '') {
            $this->token = $this->getToken();
        }
        return [
            'Accept' => 'application/json',
            'User-Agent' => 'books/1.0 (sergio@mendolia.dev)',
            'Authorization'=> 'Bearer '.$this->token,
        ];
    }

    public function getPage(string $path, array $params): array
    {
        $url = 'https://api.wikimedia.org/core/v1/wikipedia/'.$this->language.$path.'?'.http_build_query($params);
        $response = $this->client->request('GET', $url, [
            'headers' => $this->getHeaders(),
        ]);

        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
