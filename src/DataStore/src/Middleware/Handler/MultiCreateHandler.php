<?php
declare(strict_types=1);

namespace rollun\datastore\Middleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;
use Zend\Diactoros\Response;

/**
 * Class MultiCreateHandler
 *
 * @author    Roman Ratsun <r.ratsun.rollun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
class MultiCreateHandler extends AbstractHandler
{
    /**
     * @inheritDoc
     */
    public function canHandle(ServerRequestInterface $request): bool
    {
        // exit if wrong request method
        if ($request->getMethod() !== "POST") {
            return false;
        }

//        // exit if IsMultiCreate disabled
//        if (!in_array($request->getHeaderLine('IsMultiCreate'), ['true', '1'])) {
//            return false;
//        }

        // get rows
        $rows = $request->getParsedBody();

        // exit if wrong body
        if (!isset($rows) || !is_array($rows) || !isset($rows[0]) || !is_array($rows[0])) {
            return false;
        }

        foreach ($rows as $row) {
            $canHandle = isset($row)
                && is_array($row)
                && array_reduce(
                    array_keys($row),
                    function ($carry, $item) {
                        return $carry && is_string($item);
                    },
                    true
                );

            // exit if wrong body row
            if (!$canHandle) {
                return false;
            }
        }

        return $this->isRqlQueryEmpty($request);
    }

    /**
     * @inheritDoc
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        // get rows
        $rows = $request->getParsedBody();

        if ($this->dataStore instanceof DataStoreInterface) {
            $result = $this->dataStore->multiCreate($rows);
        } else {
            $result = [];
            foreach ($rows as $row) {
                try {
                    $result[] = $this->dataStore->create($row);
                } catch (DataStoreException $exception) {
                    //Ignore result...
                }
            }
            $result = array_column($result, $this->dataStore->getIdentifier());
        }

        $response = new Response();
        $response = $response->withStatus(201);
        $response = $response->withHeader('Location', $request->getUri()->getPath());
        $response = $response->withBody($this->createStream($result));

        return $response;
    }
}
