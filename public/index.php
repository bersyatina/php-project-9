<?php
error_reporting(-1);
ini_set('display_errors', 'On');

use Hexlet\Code\Handler;
use Slim\Http\Response as Response;
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
session_start();

$container = new \DI\Container();

AppFactory::setContainer($container);

$container->set('view', function () {
    $twig = Twig::create('templates', [
        'cache' => false,
        'debug' => true
    ]);
    $twig->addExtension(new \Twig\Extension\DebugExtension());
    return $twig;
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::create();

$app->add(TwigMiddleware::createFromContainer($app));

$app->get('/', function ($request, $response, $args) {
    $messages = $this->get('flash')->getMessages();

    return $this->get('view')->render($response, 'face.twig', [
        'flash' => $messages,
        'url' => [],
    ]);
})->setName('face');

$app->post('/urls', function ($request, Response $response) use ($app) {

    $connection = Connection::get()->connect();

    $inserter = new PostgreSQLAddData($connection);
    $url = $request->getParsedBody()['url'];
    $result = $inserter->insertUrl($url['name']);

    if (array_key_exists('errors', $result)) {
        $this->get('flash')->addMessage('errors', $result['errors']);

        return $response->withStatus(422)->withRedirect("/");
    }

    $getterObject = new PostgreSQLGetUrls($connection);
    $site = $getterObject->getUrl($result['success']['id']);

    $this->get('flash')->addMessage('success', $result['success']['message']);

    return $response->withRedirect("/urls/{$site['id']}");
})->setName('face');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $getterObject = new PostgreSQLGetUrls(Connection::get()->connect());

    $site = $getterObject->getUrl($args['id']);

    $site['created_at'] = !empty($site['created_at']) ? explode('.', $site['created_at'])[0] : null;

    $checks = Handler::setChecksCreatedTime($getterObject->getChecks($site['id']));

    $messages = $this->get('flash')->getMessages();

    return $this->get('view')->render($response, 'url.twig', [
        'flash' => $messages,
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

    $messages = $this->get('flash')->getMessages();

    return $this->get('view')->render($response, 'urls.twig', [
        'flash' => $messages,
        'sites' => $sitesList
    ]);
})->setName('urls');

$app->post('/urls/{url_id}/checks', function ($request, Response $response, $args) {

    $connection = Connection::get()->connect();
    $inserter = new PostgreSQLAddData($connection);
    $getterObject = new PostgreSQLGetUrls($connection);

    $site = $getterObject->getUrl($args['url_id']);

    $this->get('flash')->addMessage('errors', 'Произошла ошибка при проверке, не удалось подключиться');

    if (Handler::isDomainAvailible($site['name'])) {
        try {
            $clientGuzzle = new GuzzleHttp\Client(['base_uri' => $site['name']]);
            $requestCheck = $clientGuzzle->request('GET') ?? false;
        } catch (\PDOException $e) {
            $this->get('flash')->addMessage('errors', $e->getMessage());
        }

        if (isset($requestCheck)) {
            $responseCheck = $requestCheck->getBody();

            libxml_use_internal_errors(true);
            $doc = new DOMDocument(1.0, 'utf-8');
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
                'h1' => utf8_decode($xpath->evaluate('//h1')[0]->textContent) ?? '',
                'description' => utf8_decode(substr(array_values(array_filter($descArr))[0] ?? '', 0, 255)),
                'title' => utf8_decode($xpath->evaluate('//title')[0]->textContent) ?? '',
            ];

            $inserter->addCheck($pageData);
            $this->get('flash')->addMessage('success', 'Страница успешно проверена');
        }
    }

    return $response->withRedirect("/urls/{$site['id']}");
})->setName('add_check');

$app->run();
