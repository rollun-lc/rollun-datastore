# zaboy-rest

# Запуск тестов

Установите переменную окружения `'APP_ENV' = "dev"`;

Перед тем как запускать тесты, создайте файл `test.local.php` в `config/autoload`
и добавьте туда настройки для `httpDataStore` изменив localhost в параметре url так что бы по нему можно было получить доступ к веб-приложению.

Пример:

	return [
	  "dataStore" => [
	      'testHttpClient' => [
	          'class' => 'zaboy\rest\DataStore\HttpClient',
	          'tableName' => 'test_res_http',
	          'url' => 'http://localhost/api/rest/test_res_http',
	          'options' => ['timeout' => 30]
	      ],
	  ]
	];

Скопируйте `index.php`и .htaccess из библиотеки в паблик директорию проекта.

Запустите скрипт `composer lib-install`, он создаст таблицы в базе.
 