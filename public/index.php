<?php
error_reporting(-1); ini_set('display_errors', 'On');
use DI\Container;
use Hexlet\Code\PostgreSQLAddUrl;
use Hexlet\Code\PostgreSQLCreateTable;
use Hexlet\Code\PostgreSQLGetUrls;
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

//try {
//    // подключение к базе данных PostgreSQL
//    $pdo = Connection::get()->connect();
//    $tableCreator = new PostgreSQLCreateTable($pdo);
//
//    // создание и запрос таблицы из
//    // базы данных
//    $tables = $tableCreator->createTables();
//} catch (\PDOException $e) {
//    echo $e->getMessage();
//}

$container = new Container();

AppFactory::setContainer($container);

$container->set('view', function() {
    $twig = Twig::create('templates', [
        'cache' => false,
        'debug' => true
    ]);
    $twig->addExtension(new \Twig\Extension\DebugExtension());
    return $twig;
});

$app = AppFactory::create();

$app->add(TwigMiddleware::createFromContainer($app));

$app->get('/', function ($request, $response, $args) {
    return $this->get('view')->render($response, 'face.twig', [
        'flash' => []
    ]);
})->setName('face');

$app->post('/urls', function ($request, $response, $args) {

    $inserter = new PostgreSQLAddUrl(Connection::get()->connect());
    $messages = $inserter->insertUrl($request->getParsedBody()['url']['name']);

    $messages['success'] ?? flash('Welcome Aboard!');

    return $this->get('view')->render($response, 'face.twig', [
        'flash' => $messages
    ]);
})->setName('face');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $site = new PostgreSQLGetUrls(Connection::get()->connect());

    $site = $site->getUrl($args['id']);

    return $this->get('view')->render($response, 'url.twig', [
        'flash' => [],
        'site' => $site
    ]);
})->setName('url');

$app->get('/urls', function ($request, $response, $args) {

    $sites = new PostgreSQLGetUrls(Connection::get()->connect());

    $sites = $sites->getUrls();

    $sites = array_map(function ($site) {
        $site['created_at'] = !empty($site['created_at']) ? explode('.', $site['created_at'])[0] : null;
        return $site;
    }, $sites);

    return $this->get('view')->render($response, 'urls.twig', [
        'sites' => $sites
    ]);
})->setName('urls');

$app->run();
