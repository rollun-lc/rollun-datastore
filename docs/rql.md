# Робота з RQL

RQL – проста мова побудови запитів для абстрактних сховищ. 
У цій бібліотеці реалізація RQL від [xiag/rql-parser](https://github.com/xiag-ag/rql-parser). 
Атомарною одиницею запиту є нода (вузол).

Є кілька видів нод: 
- операторів (складають порівнянь)
- групування
- вибірки
- сортування
- нода ліміту

Всі ці типи нод у рядковому поданні поділяються знаком `&`. Побудувати запит можна за допомогою рядка або з допомогою об'єктів.

**Існуючі ноди об'єкти та їх рядкові еквіваленти:**

#### 1. Оператори до роботи з масивами.

#####  `in`. Оператор, який дозволяє визначити, чи збігається значення поля зі значенням у списку.

- Об'єкт: `Xiag\Rql\Parser\Node\Query\ArrayOperator\InNode`. Приклад:

```php
$query = new  InNode('name', ['John', 'Jackson', 'Liam']);
```

- Строкове подання: `in`. Приклад:

```php
$query = 'in(name,(John,Jackson,Liam))';
```

#####  `out`. Оператор який дозволяє визначити, чи не збігається чи значення поля зі значенням у списку (зворотне до `in`).

- Об'єкт: `Xiag\Rql\Parser\Node\Query\ArrayOperator\OutNode`. Приклад:

```php
$query = new  OutNode('name', ['Grayson', 'Lucas']);
```

- Строкове подання: `out`. Приклад:

```php
$query = 'out(name,(Grayson,Lucas))';
```

#### 2. Логічні оператори.

#####  `and`. Оператор, який відображає лише ті записи, коли вся умова є правдою (`true`).

- Об'єкт: `Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode`. Як параметри приймає ноди. приклад:

```php
$query = new  AndNode([
	new  EqNode('name', 'John'),
	new  EqNode('surname', 'Smith')
]);
```

- Строкове подання: `and`. Приклад:

```php
$query = 'and(eq(name,John),eq(surname,Smith))';
```

#####  `or`. Оператор, який відображає лише ті записи, коли хоча б одна з двох умов є правдою (`true`).

- Об'єкт: `Xiag\Rql\Parser\Node\Query\ArrayOperator\OrNode`. Як параметри приймає ноди. Приклад:

```php
$query = new  OrNode([
	new  EqNode('login', 'congrate'),
	new  EqNode('name', 'John')
]);
```

- Строкове подання: `or`. Приклад:

```php
$query = 'or(eq(login,congrate),eq(name,John))';
```

#####  `not`. Оператор служить завдання протилежно заданої умови.

- Об'єкт: `Xiag\Rql\Parser\Node\Query\ArrayOperator\NotNode`. Як параметри приймає ноди. Приклад:

```php
$query = new  NotNode([EqNode('id', '1')]);
```

- Строкове подання: `not`. Приклад:

```php
$query = 'not(eq(id,1))';
```

#### 3. Бінарні оператори.

#####  `eqf`. Оператор служить для порівняння з булевим `false`.

- Об'єкт: `rollun\datastore\Rql\Node\BinaryNode\EqfNode`. Приклад:

```php
$query = new  EqfNode('isActive');
```

- Строкове подання: `eqf`. Приклад:

```php
$query = 'eqf(isActive)';
```

#####  `eqt`. Оператор служить для порівняння з булевим `true`.

- Об'єкт: `rollun\datastore\Rql\Node\BinaryNode\EqtNode`. Приклад:

```php
$query = new  EqtNode('isActive');
```

- Строкове подання: `eqt`. Приклад:

```php
$query = 'eqt(isActive)';
```

#####  `eqn`. Оператор служить для порівняння з `null`.

- Об'єкт: `rollun\datastore\Rql\Node\BinaryNode\EqnNode`. Приклад:

```php
$query = new  EqbNode('name');
```

- Строкове подання: `eqn`. Приклад:

```php
$query = 'eqn(name)';
```

#####  `ie`. Оператор служить для того, щоб визначити чи є значення пустим (рівним `false` або `null`).

- Об'єкт: `rollun\datastore\Rql\Node\BinaryNode\IeNode`. Приклад:

```php
$query = new  IeNode('name');
```

- Строкове подання: `ie`. Приклад:

```php
$query = 'ie(name)';
```

#### 4. Скалярные операторы.

*  `eq`, `ge`, `gt`, `le`, `lt`, `ne` аналогічні операторам `=`, `>=` ,`>` ,`<=` ,`<`, `!=`. Назви операторів

еквівалентні їх рядковим уявленням. Відповідність строкового подання до об'єктів:

- `Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode` для `eq`;

- `Xiag\Rql\Parser\Node\Query\ScalarOperator\GeNode` для `ge`;

- `Xiag\Rql\Parser\Node\Query\ScalarOperator\GtNode` для `gt`;

- `Xiag\Rql\Parser\Node\Query\ScalarOperator\LeNode` для `le`;

- `Xiag\Rql\Parser\Node\Query\ScalarOperator\LtNode` для `lt`;

- `Xiag\Rql\Parser\Node\Query\ScalarOperator\NeNode` для `ne`.

  

Приклад:

```php
//Одне й те саме
$query = 'gt(age,21)';
$query = GtNode('age', '21');
```

  

#### 5. Нода для завдання ліміту (`limit`) та зсуву (`offset`).

- Об'єкт: `Xiag\Rql\Parser\Node\LimitNode`. Перший параметр для завдання ліміту, другий (необов'язковий) для завдання зсуву. Приклад:

```php
$query = new LimitNode(5, 10);
```

- Строкове подання: `limit`. Приклад:

```php
$query = 'limit(5,10)';
```

#### 6. Нода для завдання функції, що агрегує. Доступні функції: `count`, `max`, `min`, `sum`, `avg`. 

Використовується тільки у поєднанні з `AggregateSelectNode`. Об'єкт: `rollun\datastore\Rql\Node\AggregateFunctionNode`.

#### 7. Нода для завдання вибірки (поля, які потрібно вважати). Якщо нода не задана, за замовчуванням буде раховано всі поля.

#####  `SelectNode`.

- Об'єкт: `Xiag\Rql\Parser\Node\SelectNode`.

Приклад:

```php
$query = new SelectNode(['id', 'name']);
```

- Строкове подання: `select`. Приклад:

```php
$query = 'select(id,name)';
```

#####  `AggregateSelectNode`. 

Точно така ж нода як і `SelectNode`, за винятком того що як поле може
приймати агрегуючу ноду.

- Об'єкт: `rollun\datastore\Rql\Node\AggregateSelectNode`. Приклад:

```php
$query = new AggregateSelectNode([
	'id',
	new AggregateFunctionNode('count', 'name)
]);
```

- Аналогічне строкове подання. Приклад:

```php
$query = 'select(id,count(name))';
```

#### 8. Нода для задания сортировки.

- Об'єкт: `Xiag\Rql\Parser\Node\SortNode`. Приймає масив, де ключ – поле, значення - `1`(asc) або `-1`(desc)

Приклад:

```php
$query = new SortNode(['id' => 1, 'name' => -1]);
```

- Строкове подання: `sort`. Приклад:

```php
$query = 'sort(+id,-name)'; // or 'sort(id,-name)'
```

#### 9. Нода для задания группировки.

- Об'єкт: `rollun\datastore\Rql\Node\GroupByNode`. Приймає масив полів для угруповання.

```php
$query = new  GroupbyNode(['name']);
```

- Строкове подання: `ie`. Приклад:

```php
$query = 'groupby(name)';
```

Щоб задати тип використовується структура типу `{type}:{value}`.

# Приклади:  
Нижче наведено дві функціонально ідентичні програми

```php
<?php

use rollun\datastore\DataStore\Memory;
use rollun\datastore\Rql\RqlQuery;

$dataStore = new  Memory(['id', 'name', 'age']);

// You can use string
$rql = new  RqlQuery('and(ge(id,1),or(not(eqn(name)),not(eqn(surname))))&limit(1)&select(email,password)');
$dataStore->query($rql);
```

```php
<?php
use rollun\datastore\DataStore\Memory;
use rollun\datastore\Rql\RqlQuery;
use Xiag\Rql\Parser\Node  as XiagNode;
use rollun\datastore\Rql\Node  as RollunNode;

$dataStore = new  Memory(['id', 'name', 'age']);

$rql = new  RqlQuery(
	new XiagNode\Query\LogicOperator\AndNode([
		new XiagNode\Query\ScalarOperator\GeNode('eq', 1),
		new XiagNode\Query\LogicOperator\OrNode([
			new XiagNode\Query\LogicOperator\NotNode([
				new RollunNode\BinaryNode\EqnNode('name')
			]),
			new XiagNode\Query\LogicOperator\NotNode([
				new RollunNode\BinaryNode\EqnNode('surname')
			])
		])
	])
);

$rql->setLimit(new XiagNode\LimitNode(1));
$rql->setSelect(new XiagNode\SelectNode(['email', 'password']));

$dataStore->query($rql);
```


# Корисні посилання
- https://packagist.org/packages/xiag/rql-parser