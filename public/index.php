<?php
error_reporting(-1); ini_set('display_errors', 'On');
use DI\Container;
use Hexlet\Code\PostgreSQLCreateTable;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Hexlet\Code\Connection;

require __DIR__ . '/../vendor/autoload.php';

try {
    Connection::get()->connect();
//    echo 'A connection to the PostgreSQL database sever has been established successfully.';
} catch (\PDOException $e) {
    echo $e->getMessage();
}

try {
    // подключение к базе данных PostgreSQL
    $pdo = Connection::get()->connect();
    $tableCreator = new PostgreSQLCreateTable($pdo);

    // создание и запрос таблицы из
    // базы данных
    $tables = $tableCreator->createTables();
} catch (\PDOException $e) {
    echo $e->getMessage();
}


$container = new Container();

AppFactory::setContainer($container);

$container->set('view', function() {
    return Twig::create('templates', ['cache' => false]);
});

$app = AppFactory::create();

//$container->db = function ($c) {
//    $db = $c['settings']['db'];
//
//    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
//    return $pdo;
//};

$app->add(TwigMiddleware::createFromContainer($app));

$app->get('/', function ($request, $response, $args) {
    return $this->get('view')->render($response, 'face.twig', []);
})->setName('face');

$app->run();
