# Table Gateway
С использованием TableGatewayAbstractFactory мы может создавать TableGateway давая ему особые настройки, 
такие как MultiInsertSql и DbAdapter.  
Пример:
```php
    'test_res_tablle' => [
            'sql' => 'zaboy\rest\TableGateway\DbSql\MultiInsertSql',
            'adapter' => 'db'
        ],
```
    где в параметре `adapter` вы указываете имя DbAdapter.
    