<?php

namespace NNV\YQL;

use GuzzleHttp\Client;

class YQL
{
    private $guzzleClient;

    private $apiKey;

    public function __construct()
    {
        $this->guzzleClient = new Client(array(
            'base_uri' => 'https://query.yahooapis.com/v1/public/',
            'timeout' => 30
        ));
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function execute($queryBuilder)
    {
        $query = urlencode($queryBuilder->getQuery());
        $url = sprintf('https://query.yahooapis.com/v1/public/yql?q=%s&format=json&api_key=dkdkdkdkd', $query);

        $response = $this->guzzleClient->request('GET', sprintf('yql?q=%s&format=json', $query));

        return $response->getBody()->getContents();
    }
}
