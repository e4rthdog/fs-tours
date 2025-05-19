<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

use Medoo\Medoo;
use Tiagohillebrandt\AeroFetch\AeroFetch;

require __DIR__ . '/../vendor/autoload.php';

$database = new Medoo([
    'type' => 'sqlite',
    'database' => __DIR__ . '/../db/fstours.db'
]);

// Initialize AeroFetch
$aeroFetch = new AeroFetch();

$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Welcome to fs-tours API. Why are you here in this emptiness?");
    return $response;
});

// Get all tours or a specific one by tour-id
$app->get('/tours[/{id}]', function (Request $request, Response $response, $args) use ($database) {
    $id = $args['id'] ?? null;
    
    $conditions = [];
    if ($id) {
        $conditions['tour-id'] = $id;
    }
    
    $tours = $database->select('tours', [
        'tour-id',
        'tour-description'
    ], $conditions);
    
    if ($id && empty($tours)) {
        $response->getBody()->write(json_encode(['error' => 'Tour not found']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }
    
    $result = $id ? ($tours[0] ?? null) : $tours;
    
    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

// Get all tour legs or a specific one by id
$app->get('/legs[/{id}]', function (Request $request, Response $response, $args) use ($database) {
    $id = $args['id'] ?? null;
    
    // Join with tours table to get tour-description
    $legData = $database->select('tour-legs', [
        '[>]tours' => ['tour-id' => 'tour-id']
    ], [
        'tour-legs.id',
        'tour-legs.tour-id',
        'tour-legs.origin',
        'tour-legs.destination',
        'tour-legs.aircraft',
        'tour-legs.route',
        'tour-legs.comments',
        'tour-legs.link1',
        'tour-legs.link2', 
        'tour-legs.link3',
        'tours.tour-description'
    ], $id ? ['tour-legs.id' => $id] : []);
    
    if ($id && empty($legData)) {
        $response->getBody()->write(json_encode(['error' => 'Tour leg not found']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }
    
    $result = $id ? ($legData[0] ?? null) : $legData;
    
    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();