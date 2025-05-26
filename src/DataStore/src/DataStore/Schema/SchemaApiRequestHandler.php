<?php

namespace rollun\datastore\DataStore\Schema;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SchemaApiRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private SchemasRepositoryInterface $schemas,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $dataStoreName = $request->getAttribute('resourceName');

        $schema = $this->schemas->findSchema($dataStoreName);

        if ($schema === null) {
            return new JsonResponse([
                'messages' => [
                    [
                        'level' => 'error',
                        'text' => 'Schema not found',
                        'type' => 'NOT_FOUND',
                    ],
                ],
            ]);
        }

        return new JsonResponse([
            'data' => $schema,
        ]);
    }
}
