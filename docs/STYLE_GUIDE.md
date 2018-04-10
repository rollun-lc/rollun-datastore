# DataStore 

Имена констант полей в интерфесе начинает с префикса `FIELD_`, дальше следует название поля.
` const FIELD_{NAME} `

Хорошей практикой является создание интерфейса вашего DataStore. 
Он будет выгялдеть примерно таким образом.

```php
<?php
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;

interface MyDataStore extends DataStoresInterface { 
 
 const FIELD_ID = "id";
 
 const FIELD_NAME = "name";   

 }
```