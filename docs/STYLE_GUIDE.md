# DataStore 

Имена констант полей в интерфесе начинает с префикса `FIELD_`, дальше следует название поля -
` const FIELD_{NAME} `

Хорошей практикой является создание интерфейса вашего DataStore. 
Он будет выгялдеть примерно таким образом.

```php
<?php
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;

interface UserDataStore extends DataStoresInterface {
 
 const FIELD_ID = "id";
 
 const FIELD_NAME = "name";   

 const FIELD_SURNAME = "surname";

 }
```

Таким образом, в Вашем коде теперь будет более явное обращение к данным.
```php
<?php

$user = $userDataStore->read($id);
$name = $user[UserDataStore::FIELD_NAME];
//DO SOME WITH NAME...
$surname = $user[UserDataStore::FIELD_SURNAME];
//DO SOME WITH SURNAME...

```