<?php
error_reporting(-1); ini_set('display_errors', 'On');
use DI\Container;
use Hexlet\Code\PostgreSQLAddData;
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
//    $tables = $tableCreator->createTableUrls();
//    $tables = $tableCreator->createTableUrlChecks();
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

$app->post('/urls', function ($request, $response) {

    $inserter = new PostgreSQLAddData(Connection::get()->connect());
    $messages = $inserter->insertUrl($request->getParsedBody()['url']['name']);

    $messages['success'] ?? flash('Welcome Aboard!');

    return $this->get('view')->render($response, 'face.twig', [
        'flash' => $messages
    ]);
})->setName('face');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $site = new PostgreSQLGetUrls(Connection::get()->connect());
    $site = $site->getUrl($args['id']);

    $checks = new PostgreSQLGetUrls(Connection::get()->connect());
    $checks = $checks->getChecks($args['id']);

    return $this->get('view')->render($response, 'url.twig', [
        'flash' => [],
        'site' => $site,
        'checks' => $checks,
    ]);
})->setName('url');

$app->get('/urls', function ($request, $response, $args) {

    $sites = new PostgreSQLGetUrls(Connection::get()->connect());
    $sites = $sites->getUrls();

    $sites = array_map(function ($site) use ($sites) {
        $site['created_at'] = !empty($site['created_at']) ? explode('.', $site['created_at'])[0] : null;

        $sites = new PostgreSQLGetUrls(Connection::get()->connect());
        $lastCheck = $sites->getLastCheck($site['id']);

        $site['last_check'] = !empty($lastCheck) ? explode('.', $lastCheck['created_at'])[0] : null;
        return $site;
    }, $sites);

    return $this->get('view')->render($response, 'urls.twig', [
        'sites' => $sites
    ]);
})->setName('urls');

$app->post('/urls/{url_id}/checks', function ($request, $response, $args) {

    $inserter = new PostgreSQLAddData(Connection::get()->connect());
    $messages = $inserter->addCheck($args['url_id']);

    $messages['success'] ?? flash('Welcome Aboard!');

    $site = new PostgreSQLGetUrls(Connection::get()->connect());
    $site = $site->getUrl($args['url_id']);

    $checks = new PostgreSQLGetUrls(Connection::get()->connect());
    $checks = $checks->getChecks($args['url_id']);
// не возврат представления а редирект
    return $this->get('view')->render($response, 'url.twig', [
        'flash' => $messages,
        'site' => $site,
        'checks' => $checks,
    ]);
})->setName('add_check');

$app->run();
