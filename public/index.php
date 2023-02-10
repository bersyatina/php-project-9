<?php
error_reporting(-1); ini_set('display_errors', 'On');

use Hexlet\Code\Handler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
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

try {
    // подключение к базе данных PostgreSQL
    $pdo = Connection::get()->connect();
    $tableCreator = new PostgreSQLCreateTable($pdo);

    // создание и запрос таблицы из
    // базы данных
    $tables = $tableCreator->createTableUrls();
    $tables = $tableCreator->createTableUrlChecks();
} catch (\PDOException $e) {
    echo $e->getMessage();
}

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

$app->post('/urls', function ($request, $response) use ($app) {

    $inserter = new PostgreSQLAddData(Connection::get()->connect());
    $result = $inserter->insertUrl($request->getParsedBody()['url']['name']);

    $site = new PostgreSQLGetUrls(Connection::get()->connect());
    $site = $site->getUrl($result['success']['id']);

    return $this->get('view')->render($response, 'url.twig', [
        'flash' => $result,
        'site' => $site,
        'checks' => [],
    ]);
})->setName('face');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $getterObject = new PostgreSQLGetUrls(Connection::get()->connect());

    $site = $getterObject->getUrl($args['id']);
    $checks = $getterObject->getChecks($args['id']);

    return $this->get('view')->render($response, 'url.twig', [
        'flash' => [],
        'site' => $site,
        'checks' => $checks,
    ]);
})->setName('url');

$app->get('/urls', function ($request, $response, $args) {

    $sites = new PostgreSQLGetUrls(Connection::get()->connect());
    $sitesList = $sites->getUrls();

    $sitesList = array_map(function ($site) use ($sites) {
        $site['created_at'] = !empty($site['created_at']) ? explode('.', $site['created_at'])[0] : null;

        $lastCheck = $sites->getLastCheck($site['id']);

        $site['last_check'] = !empty($lastCheck) ? explode('.', $lastCheck['created_at'])[0] : null;
        $site['check_code'] = $lastCheck['status_code'] ?? null;
        return $site;
    }, $sitesList);

    return $this->get('view')->render($response, 'urls.twig', [
        'sites' => $sitesList
    ]);
})->setName('urls');

$app->post('/urls/{url_id}/checks', function ($request, $response, $args) {

    $inserter = new PostgreSQLAddData(Connection::get()->connect());

    $getterObject = new PostgreSQLGetUrls(Connection::get()->connect());
    $site = $getterObject->getUrl($args['url_id']);

    if (Handler::isDomainAvailible($site['name'])) {
        try {
            $clientGuzzle = new GuzzleHttp\Client(['base_uri' => $site['name']]);
            $requestCheck = $clientGuzzle->request('GET') ?? false;
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }

        if (isset($requestCheck)) {
            $responseCheck = $requestCheck->getBody();

            libxml_use_internal_errors(true);
            $doc = new DOMDocument();
            $doc->loadHTML($responseCheck);
            $xpath = new DOMXPath($doc);

            $descriptions = $xpath->evaluate('//meta');
            $descArr = [];
            foreach ($descriptions as $description) {
                $name = $description->getAttribute("name");
                $descArr[] = str_contains($name, 'description') ? $description->getAttribute("content") : '';
            }

            $pageData = [
                'url_id' => $args['url_id'],
                'status_code' => $requestCheck->getStatusCode() ?? null,
                'h1' => $xpath->evaluate('//h1')[0]->textContent ?? '',
                'description' => array_values(array_filter($descArr))[0] ?? '',
                'title' => $xpath->evaluate('//title')[0]->textContent ?? '',
            ];

            $inserter->addCheck($pageData);
        }
    }

    $checks = $getterObject->getChecks($site['id']);

    return $this->get('view')->render($response, 'url.twig', [
        'flash' => [],
        'site' => $site,
        'checks' => $checks,
    ]);

})->setName('add_check');

$app->run();
