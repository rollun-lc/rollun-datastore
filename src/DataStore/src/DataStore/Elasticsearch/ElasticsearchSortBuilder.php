<?php

declare(strict_types=1);

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Elasticsearch;

use rollun\datastore\DataStore\DataStoreException;
use Xiag\Rql\Parser\Node\SortNode;

/**
 * Builds Elasticsearch sort array from RQL SortNode.
 *
 * This class is responsible for:
 * - Converting SortNode to Elasticsearch sort array
 * - Adding tie-breaker (_id) for stable pagination with search_after
 * - Handling sort direction (ASC/DESC)
 *
 * Analogous to SqlQueryBuilder::setSelectOrder()
 */
final class ElasticsearchSortBuilder
{
    public function __construct(
        private readonly string $identifier = 'id'
    ) {
    }

    /**
     * Build Elasticsearch sort array from RQL SortNode.
     *
     * @param SortNode|null $sortNode
     * @return array[] Array of sort clauses in Elasticsearch format
     * @throws DataStoreException
     */
    public function buildSort(?SortNode $sortNode): array
    {
        if ($sortNode === null) {
            return [];
        }

        $sort = [];

        foreach ($sortNode->getFields() as $field => $direction) {
            if (!is_string($field) || $field === '') {
                continue;
            }

            $direction = (int) $direction;
            if ($direction !== SortNode::SORT_ASC && $direction !== SortNode::SORT_DESC) {
                throw new DataStoreException('Invalid sort direction: ' . $direction);
            }

            $sort[] = [
                $field => $direction === SortNode::SORT_ASC ? 'asc' : 'desc',
            ];
        }

        return $sort;
    }

    /**
     * Append tie-breaker to sort array for stable pagination.
     *
     * Elasticsearch search_after requires stable sort order.
     * We add identifier field and _id as tie-breakers if not already present.
     *
     * @param array[] $sort
     * @return array[]
     */
    public function appendSortTieBreaker(array $sort): array
    {
        // If no sort specified and identifier is not _id, add identifier as primary sort
        if ($sort === [] && $this->identifier !== '_id') {
            $sort[] = [$this->identifier => 'asc'];
        }

        // Check if _id is already in sort
        foreach ($sort as $sortPart) {
            if (isset($sortPart['_id'])) {
                return $sort;
            }
        }

        // Add _id as final tie-breaker
        $sort[] = ['_id' => 'asc'];

        return $sort;
    }

    /**
     * Build complete sort array with tie-breaker.
     *
     * @param SortNode|null $sortNode
     * @return array[]
     * @throws DataStoreException
     */
    public function buildSortWithTieBreaker(?SortNode $sortNode): array
    {
        $sort = $this->buildSort($sortNode);
        return $this->appendSortTieBreaker($sort);
    }
}
