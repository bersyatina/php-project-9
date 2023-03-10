<?php

use GuzzleHttp\Exception\ClientException;
use Hexlet\Code\Handler;
use Slim\Http\Response as Response;
use Hexlet\Code\PostgreSQLAddData;
use Hexlet\Code\PostgreSQLGetUrls;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Hexlet\Code\Connection;

require __DIR__ . '/../vendor/autoload.php';

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

$container->set('db', function () {
    return Connection::get()->connect();
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

$app->post('/urls', function ($request, Response $response) {
    try {
        $inserter = new PostgreSQLAddData($this->get('db'));
    } catch (\PDOException $e) {
        Handler::createTables();
        $inserter = new PostgreSQLAddData($this->get('db'));
    }

    $url = $request->getParsedBody()['url'];
    $result = $inserter->insertUrl($url['name']);

    if (array_key_exists('errors', $result)) {
        return $this->get('view')->render($response->withStatus(422), 'face.twig', [
            'flash' => $result,
            'url' => $url
        ]);
    }

    $getterObject = new PostgreSQLGetUrls($this->get('db'));
    $site = $getterObject->getUrl($result['success']['id']);

    if (is_array($site)) {
        $this->get('flash')->addMessage('success', $result['success']['message']);

        return $response->withRedirect("/urls/{$site['id']}");
    }
})->setName('urls.post');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $getterObject = new PostgreSQLGetUrls($this->get('db'));

    $site = $getterObject->getUrl($args['id']);

    if (is_array($site)) {
        $site['created_at'] = !empty($site['created_at']) ? explode('.', $site['created_at'])[0] : null;

        $checks = Handler::setChecksCreatedTime($getterObject->getChecks($site['id']));

        $messages = $this->get('flash')->getMessages();

        return $this->get('view')->render($response, 'url.twig', [
            'flash' => $messages,
            'site' => $site,
            'checks' => $checks,
        ]);
    }
})->setName('url');

$app->get('/urls', function ($request, $response, $args) {

    $sites = new PostgreSQLGetUrls($this->get('db'));
    $sitesList = $sites->getUrls();

    $sitesList = array_map(function ($site) use ($sites) {
        $site['created_at'] = !empty($site['created_at']) ? explode('.', $site['created_at'])[0] : null;

        $lastCheck = $sites->getLastCheck($site['id']);

        $site['last_check'] = !empty($lastCheck['created_at']) ? explode('.', $lastCheck['created_at'])[0] : null;
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

    $inserter = new PostgreSQLAddData($this->get('db'));
    $getterObject = new PostgreSQLGetUrls($this->get('db'));

    $site = $getterObject->getUrl($args['url_id']);

    $this->get('flash')->addMessage('errors', 'Произошла ошибка при проверке, не удалось подключиться');

    if (is_array($site) && Handler::isDomainAvailible($site['name'])) {
        try {
            $clientGuzzle = new GuzzleHttp\Client(['base_uri' => $site['name']]);
            $requestCheck = $clientGuzzle->request('GET');
        } catch (GuzzleHttp\Exception\ConnectException $e) {
            $this->get('flash')->addMessage('errors', $e->getMessage());
            return $response->withRedirect("/urls/{$site['id']}");
        }

        $responseCheck = $requestCheck->getBody();

        libxml_use_internal_errors(true);
        $doc = new DOMDocument('1.0', 'utf-8');
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
            'status_code' => $requestCheck->getStatusCode(),
            'h1' => $xpath->evaluate('//h1')[0] ? $xpath->evaluate('//h1')[0]->textContent : '',
            'description' => substr(array_values(array_filter($descArr))[0] ?? '', 0, 255),
            'title' => $xpath->evaluate('//title')[0] ? $xpath->evaluate('//title')[0]->textContent : '',
        ];

        $inserter->addCheck($pageData);
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');

    }

    $id = is_array($site) ? $site['id'] : false;

    return $response->withRedirect("/urls/{$id}");
})->setName('add_check');

$app->run();
