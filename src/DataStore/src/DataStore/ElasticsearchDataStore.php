<?php
declare(strict_types=1);

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Xiag\Rql\Parser\Query;

class ElasticsearchDataStore extends DataStoreAbstract
{
    public function __construct(
        private readonly Client $client,
        private readonly string $index,
        private readonly string $identifier = self::DEF_ID,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function read($id)
    {
        $this->checkIdentifierType($id);

        try {
            $response = $this->client->get([
                'index' => $this->index,
                'id' => (string) $id,
            ]);
        } catch (Missing404Exception) {
            $this->logger->info('ElasticsearchDataStore read: document not found', [
                'index' => $this->index,
                'id' => (string) $id,
            ]);
            return null;
        }

        if (!is_array($response)) {
            return null;
        }

        $record = $response['_source'] ?? null;

        if (!is_array($record)) {
            return null;
        }

        if (!array_key_exists($this->identifier, $record)) {
            $record[$this->identifier] = $id;
        }

        return $record;
    }

    public function create($itemData, $rewriteIfExist = false)
    {
        throw new DataStoreException('Method don\'t support.');
    }

    public function update($itemData, $createIfAbsent = false)
    {
        throw new DataStoreException('Method don\'t support.');
    }

    public function delete($id)
    {
        throw new DataStoreException('Method don\'t support.');
    }

    public function query(Query $query)
    {
        throw new DataStoreException('Method don\'t support.');
    }
}
