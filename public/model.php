<?php

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$container = require 'config/container.php';
\rollun\dic\InsideConstruct::setContainer($container);

$modelRepository = $container->get('testModelRepository');

$model = new \rollun\repository\Model\SimpleModelExtendedAbstract([
    'id' => 1,
    'field' => 'test',
    'hidden' => 'hello',
]);
$modelRepository->save($model);

$item = $modelRepository->findById(1);
$array = $item->toArray();

exit();