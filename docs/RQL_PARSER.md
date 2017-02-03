# DataStore Rql

##rollun/datastore/Rql/RqlQuery

RqlQuery наследник [`Xiag\Rql\Parser\Query`](https://github.com/xiag-ag/rql-parser).  

Данный обьект расширяет оригинал добавля ноду групировки, 
а так же позволяет инициализировать обьект спомощью rql выражения.

Для того что бы инициализировать обьект, достаточно в конструкторе передать rql выражение.
Пример:  

```php
    $query = new RqlQuery('eq(a,1)&select(a,b)');
```

Так же доступна нода `GroupBy`

## rollun\datastore\Rql\Node\Groupby
Нода позволяет делать групировки в запросе.

## rollun\datastore\Rql\Node\AggregateSelectNode
Нода которая перекрывает 

## rollun\datastore\Rql\Node\AggregateFunctionNode


Позволяет инициализировать обект с помощью rql строки.
##rollun/datastore/Rql/RqlParser
Объект RqlParser позволяет енкодировать и декодировать rql строку в query объект и обратно.  
Статический метод rqlDecode принимает на вход rql строку и возвращает Query объект.  
    Может принимать не rawurlencoded строку, но тогда спец-символы в строке должны быть екранированы.  
Статический метод rqlEncode принимает на вход Query объект и возвращает rql строку.
