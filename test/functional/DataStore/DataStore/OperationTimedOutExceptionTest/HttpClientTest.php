<?php

namespace rollun\test\functional\DataStore\DataStore\OperationTimedOutExceptionTest;

use Laminas\Http\Client;
use Laminas\Http\Request;
use rollun\datastore\DataStore\HttpClient;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;

class HttpClientTest extends BaseTest
{
    protected function getDataStore(): DataStoreInterface
    {
        // Original Laminas http client but always throw timeout exception like in Laminas\Http\Client\Adapter\Curl
        $client = new class extends Client {
            public function send(?Request $request = null)
            {
                throw new Client\Adapter\Exception\TimeoutException(
                    'Read timed out',
                    Client\Adapter\Exception\TimeoutException::READ_TIMEOUT
                );
            }
        };

        return new HttpClient(
            $client,
            'http://localhost/dataStore'
        );
    }
}
