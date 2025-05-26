## Старт

Для початку додайте в routes.php:

```php
$app->route(
        '/api/datastores/{resourceName}/schema',
        \rollun\datastore\DataStore\Schema\SchemaApiRequestHandler::class,
        ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
        \rollun\datastore\DataStore\Schema\SchemaApiRequestHandler::class,
    );
```

Далі зробіть конфігурацію для датасторів:

```php
<?php

use  \rollun\datastore\DataStore\Schema\ArraySchemaRepositoryFactory;

return [
    ArraySchemaRepositoryFactory::SCHEMAS => [
        // name of data-store =>  JSON schema
        'Customers' => [
            'type' => 'array',
            'items' => [
                'type' => 'object'
                'properties' => [
                    'id' => ['type' => 'string', 'format' => 'uuid'],
                    'raw' => ['type' => ['string', 'null'], 'format' => 'json'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                ]       
            ]  
        ]   
    ]
]
```