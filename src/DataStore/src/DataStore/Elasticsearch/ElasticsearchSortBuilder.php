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
 * - Adding configurable tie-breaker field for stable pagination with search_after
 * - Handling sort direction (ASC/DESC)
 *
 * Analogous to SqlQueryBuilder::setSelectOrder()
 */
final class ElasticsearchSortBuilder
{
    public function __construct(
        private readonly string $identifier = 'id',
        private readonly string $tieBreakerField = '_id'
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
     * We add identifier field and configurable tie-breaker field if not already present.
     *
     * @param array[] $sort
     * @return array[]
     */
    public function appendSortTieBreaker(array $sort): array
    {
        // If no sort specified and identifier is not tie-breaker, add identifier as primary sort
        if ($sort === [] && $this->identifier !== $this->tieBreakerField) {
            $sort[] = [$this->identifier => 'asc'];
        }

        // Check if tie-breaker is already in sort
        foreach ($sort as $sortPart) {
            if (isset($sortPart[$this->tieBreakerField])) {
                return $sort;
            }
        }

        // Add tie-breaker as final sort field
        $sort[] = [$this->tieBreakerField => 'asc'];

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
