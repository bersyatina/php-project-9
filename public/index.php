<?php
error_reporting(-1); ini_set('display_errors', 'On');
use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
AppFactory::setContainer($container);

$container->set('view', function() {
    return Twig::create('templates', ['cache' => false]);
});

$app = AppFactory::create();

$app->add(TwigMiddleware::createFromContainer($app));

$app->get('/', function ($request, $response, $args) {
    return $this->get('view')->render($response, 'face.twig', []);
})->setName('profile');

$app->run();
