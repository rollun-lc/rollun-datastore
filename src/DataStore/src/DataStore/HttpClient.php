<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use rollun\datastore\DataStore\ConditionBuilder\RqlConditionBuilder;
use rollun\utils\Json\Serializer;
use Xiag\Rql\Parser\Query;
use rollun\datastore\Rql\RqlParser;
use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Http\Response;

/**
 * Class HttpClient
 * @package rollun\datastore\DataStore
 */
class HttpClient extends DataStoreAbstract
{
    /**
     * @var string 'http://example.org'
     */
    protected $url;

    /**
     * @var string 'mylogin'
     * @see https://en.wikipedia.org/wiki/Basic_access_authentication
     */
    protected $login;

    /**
     * @var string 'kjfgn&56Ykjfnd'
     * @see https://en.wikipedia.org/wiki/Basic_access_authentication
     */
    protected $password;

    /**
     * @var Client
     */
    protected $client;

    /**
     * Supported keys:
     * - maxredirects
     * - useragent
     * - adapter
     * - timeout
     * - curloptions
     *
     * @var array
     */
    protected $options = [];

    /**
     * HttpClient constructor.
     * @param Client $client
     * @param $url
     * @param null $options
     */
    public function __construct(Client $client, $url, $options = null)
    {
        $this->client = $client;
        $this->url = rtrim(trim($url), '/');

        if (is_array($options)) {
            if (isset($options['login']) && isset($options['password'])) {
                $this->login = $options['login'];
                $this->password = $options['password'];
            }

            $supportedKeys = ['maxredirects', 'useragent', 'adapter', 'timeout', 'curloptions'];

            $this->options = array_intersect_key($options, array_flip($supportedKeys));
        }

        $this->conditionBuilder = new RqlConditionBuilder();
    }

    public function getIdentifier()
    {
        $client = $this->initHttpClient(Request::METHOD_HEAD, $this->url);
        $response = $client->send();
        if ($response->isSuccess() && $response->getHeaders()->has('X_DATASTORE_IDENTIFIER')) {
            return $response->getHeaders()->get('X_DATASTORE_IDENTIFIER')->getFieldValue();
        }
        return parent::getIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function read($id)
    {
        $this->checkIdentifierType($id);
        $uri = $this->createUri(null, $id);
        $client = $this->initHttpClient(Request::METHOD_GET, $uri);
        $response = $client->send();

        if ($response->isOk()) {
            $result = Serializer::jsonUnserialize($response->getBody());
        } else {
            $responseMessage = $this->createResponseMessage($uri, Request::METHOD_GET, $response);
            throw new DataStoreException("Can't read item {$responseMessage}");
        }

        return $result;
    }

    /**
     * Create http client
     *
     * @param string $method ('GET', 'HEAD', 'POST', 'PUT', 'DELETE')
     * @param string $uri
     * @param bool $ifMatch
     * @return Client
     */
    protected function initHttpClient(string $method, string $uri, $ifMatch = false)
    {
        $httpClient = clone $this->client;
        $httpClient->setUri($uri);
        $httpClient->setOptions($this->options);

        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';

        if ($ifMatch) {
            $headers['If-Match'] = '*';
        }

        $httpClient->setHeaders($headers);

        if (isset($this->login) && isset($this->password)) {
            $httpClient->setAuth($this->login, $this->password);
        }

        $httpClient->setMethod($method);

        return $httpClient;
    }

    protected function createUri(Query $query = null, $id = null)
    {
        $uri = $this->url;

        if (isset($id)) {
            $uri = $this->url . '/' . $this->encodeString($id);
        }

        if (isset($query)) {
            $rqlString = RqlParser::rqlEncode($query);
            $uri = $uri . '?' . $rqlString;
        }

        return $uri;
    }

    /**
     * @param $value
     * @return string
     */
    protected function encodeString($value)
    {
        return strtr(rawurlencode($value), ['-' => '%2D', '_' => '%5F', '.' => '%2E', '~' => '%7E',]);
    }

    /**
     * {@inheritdoc}
     */
    public function query(Query $query)
    {
        $uri = $this->createUri($query);
        $client = $this->initHttpClient(Request::METHOD_GET, $uri);
        $response = $client->send();

        if ($response->isOk()) {
            $result = Serializer::jsonUnserialize($response->getBody());
        } else {
            $responseMessage = $this->createResponseMessage($uri, Request::METHOD_GET, $response);
            throw new DataStoreException("Can't fetch items by query {$responseMessage}");
        }

        return empty($result) ? [] : $result;
    }

    /**
     * {@inheritdoc}
     */
    public function create($itemData, $rewriteIfExist = false)
    {
        if ($rewriteIfExist) {
            trigger_error("Option 'rewriteIfExist' is no more use", E_USER_DEPRECATED);
        }

        $client = $this->initHttpClient(Request::METHOD_POST, $this->url, $rewriteIfExist);
        $json = Serializer::jsonSerialize($itemData);
        $client->setRawBody($json);
        $response = $client->send();

        if ($response->isSuccess()) {
            $result = Serializer::jsonUnserialize($response->getBody());
        } else {
            $responseMessage = $this->createResponseMessage($this->url, Request::METHOD_POST, $response);
            throw new DataStoreException("Can't create item {$responseMessage}");
        }

        return $result;
    }

    /**
     * @inheritDoc
     *
     * @throws DataStoreException
     * @throws \rollun\utils\Json\Exception
     */
    public function multiCreate($records)
    {
        if (!isset($records[0]) || !is_array($records[0])) {
            throw new DataStoreException('Collection of arrays expected');
        }

        $client = $this->initHttpClient(Request::METHOD_HEAD, $this->url);
        $response = $client->send();
        if ($response->isSuccess() && $response->getHeaders()->get('X_MULTI_CREATE')) {
            $client = $this->initHttpClient(Request::METHOD_POST, $this->url);
            $client->setRawBody(Serializer::jsonSerialize($records));
            $response = $client->send();
            if ($response->isSuccess()) {
                $result = Serializer::jsonUnserialize($response->getBody());
            } else {
                $responseMessage = $this->createResponseMessage($this->url, Request::METHOD_POST, $response);
                throw new DataStoreException("Can't create items {$responseMessage}");
            }
        } else {
            $client = $this->initHttpClient(Request::METHOD_POST, $this->url);
            $result = [];
            foreach ($records as $record) {
                $client->setRawBody(Serializer::jsonSerialize($record));
                $response = $client->send();
                $result[] = Serializer::jsonUnserialize($response->getBody());
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function update($itemData, $createIfAbsent = false)
    {
        if ($createIfAbsent) {
            trigger_error("Option 'createIfAbsent' is no more use.", E_USER_DEPRECATED);
        }

        $identifier = $this->getIdentifier();

        if (!isset($itemData[$identifier])) {
            throw new DataStoreException('Item must has primary key');
        }

        $id = $itemData[$identifier];
        $this->checkIdentifierType($id);
        $uri = $this->createUri(null, $itemData[$identifier]);
        unset($itemData[$identifier]);

        $client = $this->initHttpClient(Request::METHOD_PUT, $uri, $createIfAbsent);
        $client->setRawBody(Serializer::jsonSerialize($itemData));
        $response = $client->send();

        if ($response->isSuccess()) {
            $result = Serializer::jsonUnserialize($response->getBody());
        } else {
            $responseMessage = $this->createResponseMessage($uri, Request::METHOD_PUT, $response);
            throw new DataStoreException("Can't update item {$responseMessage}");
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $this->checkIdentifierType($id);
        $uri = $this->createUri(null, $id);
        $client = $this->initHttpClient(Request::METHOD_DELETE, $uri);
        $response = $client->send();

        if ($response->isSuccess()) {
            $result = !empty($response->getBody()) ? Serializer::jsonUnserialize($response->getBody()) : null;
        } else {
            $responseMessage = $this->createResponseMessage($uri, Request::METHOD_DELETE, $response);
            throw new DataStoreException("Can't delete item {$responseMessage}");
        }

        return $result;
    }

    protected function createResponseMessage($uri, $method, Response $response)
    {
        $messages = [
            $method,
            $uri,
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            $response->getBody(),
        ];

        switch ($response->getStatusCode()) {
            case 301:
            case 302:
            case 307:
            case 308:
                $location = $response->getHeaders()
                    ->get('Location')
                    ->getFieldValue();
                $messages[] = "New location is '{$location}'";
                break;
        }

        return trim(implode(' ', $messages));
    }
}
