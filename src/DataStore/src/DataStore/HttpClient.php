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

        $this->conditionBuilder = new RqlConditionBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function read($id)
    {
        $this->checkIdentifierType($id);
        $client = $this->initHttpClient(Request::METHOD_GET, null, $id);
        $response = $client->send();

        if ($response->isOk()) {
            $result = Serializer::jsonUnserialize($response->getBody());
        } else {
            throw new DataStoreException($this->getClientExceptionMessage($response, "Can't read item with id = $id."));
        }

        return $result;
    }

    /**
     * Create http client
     *
     * @param string $method ('GET', 'HEAD', 'POST', 'PUT', 'DELETE')
     * @param Query|null $query
     * @param null $id
     * @param bool $ifMatch
     * @return Client
     */
    protected function initHttpClient($method, Query $query = null, $id = null, $ifMatch = false)
    {
        $url = !$id ? $this->url : $this->url . '/' . $this->encodeString($id);

        if (isset($query)) {
            $rqlString = RqlParser::rqlEncode($query);
            $url = $url . '?' . $rqlString;
        }

        $httpClient = clone $this->client;
        $httpClient->setUri($url);
        $httpClient->setOptions($this->options);

        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';
        $headers['APP_ENV'] = constant('APP_ENV');

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
        $client = $this->initHttpClient(Request::METHOD_GET, $query);
        $response = $client->send();

        if ($response->isOk()) {
            $result = Serializer::jsonUnserialize($response->getBody());
        } else {
            throw new DataStoreException(
                $this->getClientExceptionMessage(
                    $response,
                    "Can't execute query = " . RqlParser::rqlEncode($query) . "."
                )
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function create($itemData, $rewriteIfExist = false)
    {
        $identifier = $this->getIdentifier();

        if (isset($itemData[$identifier])) {
            $id = $itemData[$identifier];
            $this->checkIdentifierType($id);
        } else {
            trigger_error("Getting last id using db is deprecate", E_USER_DEPRECATED);
            $id = null;
        }

        $client = $this->initHttpClient(Request::METHOD_POST, null, $id, $rewriteIfExist);
        $json = Serializer::jsonSerialize($itemData);
        $client->setRawBody($json);
        $response = $client->send();

        if ($response->isSuccess()) {
            $result = Serializer::jsonUnserialize($response->getBody());
        } else {
            throw new DataStoreException(
                $this->getClientExceptionMessage(
                    $response,
                    "Can't create item with id = " . (is_null($id) ? 'null' : $id) . "."
                )
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function update($itemData, $createIfAbsent = false)
    {
        $identifier = $this->getIdentifier();

        if (!isset($itemData[$identifier])) {
            throw new DataStoreException('Item must has primary key');
        }

        $id = $itemData[$identifier];
        $this->checkIdentifierType($id);

        $client = $this->initHttpClient(Request::METHOD_PUT, null, $id, $createIfAbsent);
        $client->setRawBody(Serializer::jsonSerialize($itemData));
        $response = $client->send();

        if ($response->isSuccess()) {
            $result = Serializer::jsonUnserialize($response->getBody());
        } else {
            throw new DataStoreException(
                $this->getClientExceptionMessage(
                    $response,
                    "Can't update item with id = $id."
                )
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $this->checkIdentifierType($id);
        $client = $this->initHttpClient(Request::METHOD_DELETE, null, $id);
        $response = $client->send();

        if ($response->isSuccess()) {
            $result = !empty($response->getBody()) ? Serializer::jsonUnserialize($response->getBody()) : null;
        } else {
            throw new DataStoreException(
                $this->getClientExceptionMessage(
                    $response,
                    "Can't delete item with id = $id."
                )
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return parent::count();
    }

    /**
     * @param Response $response
     * @param $message
     * @return string
     */
    protected function getClientExceptionMessage(Response $response, $message)
    {
        $body = '';

        if (strlen($response->getBody()) < 255) {
            $body = " Body: {$response->getReasonPhrase()}";
        }

        return $message . " Status: {$response->getStatusCode()}." . " ReasonPhrase: {$response->getReasonPhrase()}." . $body;
    }
}
