<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

use Medoo\Medoo;

require __DIR__ . '/../vendor/autoload.php';

$database = new Medoo([
    'type' => 'sqlite',
    'database' => __DIR__ . '/../db/fstours.db'
]);

$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!!");
    return $response;
});

$app->run();