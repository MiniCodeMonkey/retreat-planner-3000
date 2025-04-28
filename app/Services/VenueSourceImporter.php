<?php

namespace App\Services;

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class VenueSourceImporter
{
    protected HttpBrowser $browser;

    protected HttpClientInterface $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::create();
        $this->browser = new HttpBrowser($this->httpClient);
    }

    abstract public function import(): void;
}
