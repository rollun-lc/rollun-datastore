<?php

namespace rollun\test\functional\DataStore\DataStore\ConnectionExceptionTest;

use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Exception\RuntimeException;
use Laminas\Http\Request;
use rollun\datastore\DataStore\HttpClient;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;

class HttpClientTest extends BaseTest
{
    protected function getDataStore(): DataStoreInterface
    {
        // Original Laminas http client but always throw connection exception like in Laminas\Http\Client\Adapter\Curl
        $client = new class extends Client
        {
            public function send(?Request $request = null)
            {
                throw new RuntimeException('Unable to Connect to ' . 'localhost' . ':' . '80');
            }
        };

        return new HttpClient(
            $client,
            'http://localhost/dataStore'
        );
    }
}