# EAVDataStore

## Правила наименования таблиц и полей.

* Все поля таблиц (заголовки таблиц) должны быть именованы в одиночном числе.

### Таблицы сущностей - `entity`

* `sys_entities` - центральная таблица всех сущностей.

* `entity_{name}` - таблица сущности. Где `{name}` - имя сущности.

* Таблица `entity_{name}` обязана иметь поле `id` которое будет сквозным,
так же оно будет `foreign key` для поля id в таблице `sys_entities`."

* Поле `id` в таблицах означает `primary key`.
Для связи с другими таблицами используем наименование поля `{name}_id`, где `{name}`:
  1) имя сущности из таблицы `entity_{name}`.
  2) имя таблицы `sys_entities` - тогда props таблица может быть связана со всеми сущностями."

* Все таблицы сущностей должны называться `entity_{name}`, где `name` - имя сущности.
Оно будет указано в таблице `sys_entities` поле `entity_type`."

* При обращении к ресурсу (сущности) мы указываем только name, без приставки `entity_`

* Поле связи с таблицей `sys_entities` должно называться `id` оно является первичным ключом и сквозным для всех `entity_{name}` таблиц.

### Таблицы параметров - `prop`

* `prop_{nameOfRelationType}` - таблица дополнительных параметров. Где `{nameOfRelationType}` - семантическое имя связи.
Может представлять связь много-к-одному, один-к-одному, многие-ко-многим,
как в случае с таблицами `prop_tag`, `prop_url`, `prop_product-category` соответственно.

* Таблица `prop_{nameOfRelationType}` обязана иметь поле `id`.

* Таблица `prop_{nameOfRelationType}` должна иметь по крайней мере одно поле связи с таблицей сущности.

* В props таблицах, для поля связи с таблицей сущности мы должны указать
приставку к полю `id` с именем этой сущности - `{name}_id`, например `category_id` для владеющей сущности `entity_category`."

* При обращении или при выборе параметра, мы через символ `prop_` указываем только `name` нашей
таблице параметров - `prop_{nameOfRelationType}`.
Пример: `prop_category`. `prop_product-category`"

#### Работа с таблицами параметров - props

##### Получить prop (GET)
что бы получить пропс для определенного entity нужно отправить запрос к
    таблице сущности entity_{name} и в select передать имя prop таблицы через символ prop_.

Пример:
Выберем prop_images для сущности с id = 11

Отправим такой запрос:

`GET /api/v1/product?select(prop_images)&eq(id,11)`

Получим в ответ:

    ```json
    {
        "title": "Edelweiss",
        "price": "200",
        "prop_images": [
            {
               "id": "31",
               "image": "img1.png",
               "product_id": "11",
            }
        ]
    }
    ```

##### Создать prop (POST)
так как prop не может существовать без связи с сущностью, что бы создать пропс
мы должны отредактировать существующую или добавив новую сущность.

Пример:
1)
В случае если мы будем создавать сущность.
Давайте изменим prop_image для сущности product.

Отправим запрос:
    `POST /api/v1/product`
```
{
    "title": "Edelweiss",
    "price": "200",
    "prop_image": [
        {
            "id": "31",
            "image": "img1.png",
            "product_id": "11",
        },
        {
            "image": "img5.png",
            "product_id": "11",
        }
    ]
}
```
Получим в ответ:
```
{
    "title": "Edelweiss",
    "price": "200",
    "prop_image": [
        {
            "id": "31",
            "image": "img1.png",
            "product_id": "11",
        },
        {
            "id": "35",
            "image": "img5.png",
            "product_id": "11",
        }
    ]
}
```
2)
В случае если мы будем обновлять сущность.
Давайте изменим prop_image для сущности product c id = 11.

Отправим запрос:
    `PUT /api/v1/product/11`
```
{
    "title": "Edelweiss",
    "price": "200",
    "prop_image": [
        {
            "id": "31",
            "image": "img1.png",
            "product_id": "11",
        },
        {
            image: img5."png"
            "product_id": "11",
        }
    ]
}
```

Получим в ответ:

```
{
    "title": "Edelweiss",
    "price": "200",
    "prop_image": [
        {
            "id": "31",
            "image": "img1.png",
            "product_id": "11",
        },
        {
            "id": "35",
            "image": "img5.png",
            "product_id": "11",
        }
    ]
}
```

##### Создать prop (PUT)

Так как prop не может существовать без связи с сущностью, что бы отредактировать пропс
мы должны отредактировать существующую сущность.
Обязательное записывание всех prop при put запросе тогда все отсутствующее записи будут удалены.

Давайте изменим prop_image для сущности product c id = 11.

Отправим запрос:
    `PUT /api/v1/product/11`
```
{
    "title": "Edelweiss",
    "price": "200",
    "prop_image": [
        {
            "id": "31",
            "image": "img7.png",
            "product_id": "11",
        },
        {
            "id": "35",
            "image": "img5.png",
            "product_id": "11",
        }
    ]
}
```

Получим в ответ:

```
{
    "title": "Edelweiss",
    "price": "200",
    "prop_image": [
        {
            "id": "31",
            "image": "img7.png",
            "product_id": "11",
        },
        {
            "id": "35",
            "image": "img5.png"
            "product_id": "11",
        }
    ]
}
```

##### Удалить prop (DELETE)

Что бы удалить prop мы должны не указывать его при обновлении сущности.
Давайте удалим prop_image id = 31 для сущности product c id = 11.

Отправим запрос:
    `put /api/v1/product/11`
```
{
    "title": "Edelweiss",
    "price": "200",
    "prop_image": [
        {
            "id": "35",
            "image": "img5.png",
            "product_id": "11",
        }
    ]
}
```

Получим в ответ:

```
{
    "title": "Edelweiss",
    "price": "200",
    "prop_image": [
        {
            "id": "35",
            "image": "img5.png"
            "product_id": "11",
        }
    ]
}
```


#Примеры работы с Entity DataStore.

##[rollun\datastore\DataStore\Eav\Entity](https://github.com/rollun-com /rollun-datastore/blob/master/src/DataStore/Eav/Entity.php 'Entity')

`Entity` - `dataStore `для работы с `entity` таблицами сущностей.
Являются наследниками `dbTable`.

Таким же образом мы можем использовать агрегатные функции - явно указав таблицу `sysEntities`

Для того что бы могли работать с `entity` `dataStore`, мы можешь запросить ее из контейнера предварительно мы должны добавть
абсрактную фабрику в конфиг, а так же добавив `aliases` для `db`.
Пример
```php
        'services' => [
                'aliases' => [
                    EavAbstractFactory::DB_SERVICE_NAME => getenv('APP_ENV') === 'prod' ? 'db' : 'db',
                ],
                'abstract_factories' => [
                    EavAbstractFactory::class,
                ]
            ],
```

Теперь мы можешь взять из контейнера наш `dataStore`.

Вызвав в нем метод `query` передав в него объект `Query()`
с нужными условиями мы можем произвести запрос который будет эквивалентен `GET` запросу по `http`.
Нужно учесть что `Entity` при `GET` запросе будет возвращать помимо своих полей еще и поля таблицы `sysEntities`.

Что бы нам рбоать с колонками таблцици` sys_entities` во время работы с `entity`  мы должны их указыввать явно
через приставку `SysEntity::TABLE_NAME`.

Пример

```php
//todo: some ...
$eq = new LtNode(SysEntity::TABLE_NAME . '.add_date', new \DateTime()->format('D-m-h'));
//todo: some ...
```

Также мы должны знать что помимо полей самой сущности нам будут возвращены еще поля `sys_entities` для этой записи.

Мы можем так же создавать новые записи в сущности используя метод `create`.
Он будет эквивалентен методу `PUSH` по `http`.
В метод create мы должны передать элемент который мы хотим добавить в таблицу.

Пример
```php
    $createdElement = $entity->create([
        'title' => 'title_test',
        'price' => 100
    ]);
```
Медод create верет созданый элемент (вместе с полем `id`).

Можно так же обновить сущность, либо изменить в ней количесвто prop объектов.
Так же нужно учесть что мы обязаны передавть все prop обьекты для выбраного prop свойства

Пример:

Если для сущности `entity_category` мы заходим иземнить `prop_tag`
(Сущность `entity_category` с `id` = 22 имеет две `prop_tag` связи)

Мы должны передать все имеющееся для `entity_category` `prop_tag` записи, для не измененых модем передть только `id`.

Пример
```php
        $item = [
            "title" => "Flowers_Rose_1",
            "prop_tag" => [
                ["id" => "14"],
                [
                    "id" => "15",
                    "sys_entities_id" => "22"
                    "tag_id" => "32",
                ],
            ]
        ];
```
Используя метод `update` у `entity` мы можем:
1) Обновить поля сущности.
```php
       $updatedEntity = $entityProduct->update([
           'id' => '11',
           'title' => 'Edelweiss',
           'price' => '250'
       ]);
   ```
2) Добавлять новый prop.
```php
    $updatedEntity = $entityProduct->update([
        'id' => '11',
        'title' => 'Edelweiss',
        'price' => '250',
        'prop_linked_url' => [
            ['id' => '11'],
            ['id' => '14', 'url' => 'https://www.google.com.ua/?q=Edelweiss_2', 'alt' => 'Pot5_1'],
        ]
    ]);
```
3) Удалять prop.
```php
    $updatedEntity = $entityProduct->update([
        'id' => '11',
        'title' => 'Edelweiss',
        'price' => '250',
        'prop_linked_url' => [

        ]
    ]);
```
4) Обновлять prop.
```php
    $updatedEntity = $entityProduct->update([
        'id' => '11',
        'title' => 'Edelweiss',
        'price' => '250',
        'prop_linked_url' => [
            ['id' => '11', 'url' => 'https://www.google.com.ua/?q=Edelweiss_edited', 'alt' => 'Pot1_edited'],
        ]
    ]);
```

Что бы удалить элемент мы можем вызвать метод `delete` передав в него `id` элемента который хотим удалить.
эквивалентен методу `DELETE` по `http`.
Возвращает удаленный элемент.

Пример
```php
    $deletedEntity = $entity->delete($id);
```

Так же мы можем удалить все елемены вызвал метод `deleteAll`
Возвращает массив `id` удаленных элементов.

Пример
```php
    $deletedEntitiesId = $entity->deleteAll();
```

# SysEntity DataStore
##[rollun\datastore\DataStore\Eav\SysEntities](https://github.com/rollun-com/rollun-datastore/blob/master/src/DataStore/Eav/SysEntities.php 'SysEntities')

SysEntity позволяет нам напрямую рабоать с таблицей sys_entities.
Так же объект дает нам ряд дополнительных методов и констант для работы с таблицей sys_entities.

Константы
* SysEntity::TABLE_NAME - Имя таблици.
* SysEntity::ENTITY_PREFIX - Префикс для таблц сущностей (entity).
* SysEntity::PROP_PREFIX - Префикс для таблц параметров (prop).
* SysEntity::ID_SUFFIX - Суфикс для полей связи.

# Prop DataStore
##[rollun\datastore\DataStore\Eav\Prop](https://github.com/rollun-com/rollun-datastore/blob/master/src/DataStore/Eav/SysEntities.php 'Prop')

SysEntity позволяет нам напрямую рабоать с prop таблицами.
Так же объект дает нам ряд дополнительных методов для работы с prop таблицами.