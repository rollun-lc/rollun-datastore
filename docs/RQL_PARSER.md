#Rql
##rollun/datastore/Rql/Query

Query наследник Xiag\Rql\Parser\Query.  
Позволяет инициализировать обект с помощью rql строки.  
Все символы которые вы хотите что бы были переведены в rawurlencode нужно екранировать символо - \.  
Так же, есль ряд символов которые будут сконвертированы по умеолчанию и не требуют екранирования.  
###Список символов которые не обязательно енкодировать:  
* '@'
* '$'
* ' '

##rollun/datastore/Rql/RqlParser
Объект RqlParser позволяет енкодировать и декодировать rql строку в query объект и обратно.  
Статический метод rqlDecode принимает на вход rql строку и возвращает Query объект.  
    Может принимать не rawurlencoded строку, но тогда спец-символы в строке должны быть екранированы.  
Статический метод rqlEncode принимает на вход Query объект и возвращает rql строку.

## GroubBy

Нода для групировок 

пример использование 
* `groupby(id)`

* `groupby(id,fString)`

* `groupby(id,fString,fFloat)`

* `select(id,fString)&groupby(id,fString)`

* `and(gt(fFloat,99.003),lt(fFloat,101.003))&select(fFloat)&groupby(fFloat)`
