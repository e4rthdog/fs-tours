<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

use Medoo\Medoo;
use THSCD\AeroFetch\Services\AirportService;
use THSCD\AeroFetch\Services\AircraftService;

require __DIR__ . '/../vendor/autoload.php';

$database = new Medoo([
    'type' => 'sqlite',
    'database' => __DIR__ . '/../db/fstours.db'
]);

// Helper function to enrich leg data with coordinates and aircraft info
function enrichLegData($legData)
{
    if (empty($legData)) {
        return $legData;
    }

    // Process single leg
    if (isset($legData['origin'])) {
        $originAirport = AirportService::getBy('icaoCode', $legData['origin']);
        $destAirport = AirportService::getBy('icaoCode', $legData['destination']);
        $aircraft = AircraftService::getBy('icaoCode', $legData['aircraft']);

        $originAirportObj = (is_array($originAirport) && !empty($originAirport)) ? reset($originAirport) : null;
        $destAirportObj = (is_array($destAirport) && !empty($destAirport)) ? reset($destAirport) : null;
        $aircraftObj = (is_array($aircraft) && !empty($aircraft)) ? reset($aircraft) : null;

        if (is_object($originAirportObj)) {
            if (!is_null($originAirportObj->latitude) && !is_null($originAirportObj->longitude)) {
                $legData['origin_coords'] = [(float)$originAirportObj->latitude, (float)$originAirportObj->longitude];
            }
            $legData['origin_name'] = $originAirportObj->name ?? null;
        }

        if (is_object($destAirportObj)) {
            if (!is_null($destAirportObj->latitude) && !is_null($destAirportObj->longitude)) {
                $legData['destination_coords'] = [(float)$destAirportObj->latitude, (float)$destAirportObj->longitude];
            }
            $legData['destination_name'] = $destAirportObj->name ?? null;
        }

        if (is_object($aircraftObj) && !is_null($aircraftObj->model)) {
            $legData['aircraft_model'] = $aircraftObj->model;
        }

        return $legData;
    }

    // Process multiple legs
    foreach ($legData as &$leg) {
        $leg = enrichLegData($leg);
    }

    return $legData;
}

$app = AppFactory::create();

// Add CORS headers to all routes
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Handle OPTIONS preflight requests
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Authentication middleware
$authMiddleware = function ($request, $handler) {
    $auth = $request->getHeaderLine('Authorization');
    if (empty($auth) || $auth !== 'Bearer your-secret-token') {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }

    return $handler->handle($request);
};

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Welcome to fs-tours API. Why are you here in this emptiness?");
    return $response;
});

//
// TOURS CODE
// Get all tours or a specific one by tour-id
$app->get('/tours[/{id}]', function (Request $request, Response $response, $args) use ($database) {
    $id = $args['id'] ?? null;

    $conditions = [];
    if ($id) {
        $conditions['tour_id'] = $id;
    }

    $tours = $database->select('tours', [
        'tour_id',
        'tour_description'
    ], $conditions);

    if ($id && empty($tours)) {
        $response->getBody()->write(json_encode(['error' => 'Tour not found']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    $result = $id ? ($tours[0] ?? null) : $tours;

    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

// Create a new tour
$app->post('/tours', function (Request $request, Response $response, $args) use ($database) {
    $data = $request->getParsedBody();

    // Validate required fields
    if (empty($data['tour_id']) || empty($data['tour_description'])) {
        $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Check if tour with same ID already exists
    $existing = $database->get('tours', ['tour_id'], ['tour_id' => $data['tour_id']]);
    if ($existing) {
        $response->getBody()->write(json_encode(['error' => 'Tour with this ID already exists']));
        return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
    }

    $database->insert('tours', [
        'tour-id' => $data['tour_id'],
        'tour-description' => $data['tour_description']
    ]);

    $response->getBody()->write(json_encode(['success' => true, 'tour_id' => $data['tour_id']]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Update an existing tour
$app->put('/tours/{id}', function (Request $request, Response $response, $args) use ($database) {
    $id = $args['id'];
    $data = $request->getParsedBody();

    // Validate required fields
    if (empty($data['tour_description'])) {
        $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Check if tour exists
    $existing = $database->get('tours', ['tour_id'], ['tour_id' => $id]);
    if (!$existing) {
        $response->getBody()->write(json_encode(['error' => 'Tour not found']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    $database->update('tours', [
        'tour_description' => $data['tour_description']
    ], [
        'tour_id' => $id
    ]);

    $response->getBody()->write(json_encode(['success' => true]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Delete a tour
$app->delete('/tours/{id}', function (Request $request, Response $response, $args) use ($database) {
    $id = $args['id'];

    // Check if tour exists
    $existing = $database->get('tours', ['tour_id'], ['tour_id' => $id]);
    if (!$existing) {
        $response->getBody()->write(json_encode(['error' => 'Tour not found']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    // Check for legs associated with this tour
    $legs = $database->has('tour_legs', ['tour_id' => $id]);
    if ($legs) {
        $response->getBody()->write(json_encode([
            'error' => 'Cannot delete tour with existing legs. Delete the legs first.'
        ]));
        return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
    }

    $database->delete('tours', ['tour_id' => $id]);

    $response->getBody()->write(json_encode(['success' => true]));
    return $response->withHeader('Content-Type', 'application/json');
});


// Get all legs for a specific tour
$app->get('/tours/{id}/legs', function (Request $request, Response $response, $args) use ($database) {
    $tourId = $args['id'];
    $legs = $database->select('tour_legs', [
        '[>]tours' => ['tour_id' => 'tour_id']
    ], [
        'tour_legs.id',
        'tour_legs.tour_id',
        'tour_legs.origin',
        'tour_legs.destination',
        'tour_legs.aircraft',
        'tour_legs.route',
        'tour_legs.comments',
        'tour_legs.flight_date',
        'tour_legs.link1',
        'tour_legs.link2',
        'tour_legs.link3',
        'tours.tour_description'
    ], [
        'tour_legs.tour_id' => $tourId,
        'ORDER' => ['tour_legs.flight_date' => 'ASC', 'tour_legs.id' => 'ASC']
    ]);

    // Assign sequence numbers
    $seq = 1;
    if (is_iterable($legs)) {
        foreach ($legs as &$leg) {
            $leg['sequence'] = $seq++;
        }
    }

    $enrichedLegs = enrichLegData($legs);

    $response->getBody()->write(json_encode($enrichedLegs));
    return $response->withHeader('Content-Type', 'application/json');
});


//
// LEGS CODE
// Get all tour legs or a specific one by id
$app->get('/legs[/{id}]', function (Request $request, Response $response, $args) use ($database) {
    $id = $args['id'] ?? null;
    $legData = $database->select('tour_legs', [
        '[>]tours' => ['tour_id' => 'tour_id']
    ], [
        'tour_legs.id',
        'tour_legs.tour_id',
        'tour_legs.origin',
        'tour_legs.destination',
        'tour_legs.aircraft',
        'tour_legs.route',
        'tour_legs.comments',
        'tour_legs.flight_date',
        'tour_legs.link1',
        'tour_legs.link2',
        'tour_legs.link3',
        'tours.tour_description'
    ], $id ? ['tour_legs.id' => $id] : []);

    if ($id && empty($legData)) {
        $response->getBody()->write(json_encode(['error' => 'Tour leg not found']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    $result = $id ? ($legData[0] ?? null) : $legData;
    $enrichedResult = enrichLegData($result);

    $response->getBody()->write(json_encode($enrichedResult));
    return $response->withHeader('Content-Type', 'application/json');
});

// Add a new tour leg
$app->post('/legs', function (Request $request, Response $response, $args) use ($database) {
    $data = $request->getParsedBody();
    // Ensure origin and destination are uppercase
    if (isset($data['origin'])) {
        $data['origin'] = strtoupper($data['origin']);
    }
    if (isset($data['destination'])) {
        $data['destination'] = strtoupper($data['destination']);
    }

    // Validate required fields
    if (empty($data['tour_id']) || empty($data['origin']) || empty($data['destination'])) {
        $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $database->insert('tour_legs', [
        'tour_id' => $data['tour_id'],
        'origin' => $data['origin'],
        'destination' => $data['destination'],
        'aircraft' => $data['aircraft'] ?? null,
        'route' => $data['route'] ?? null,
        'comments' => $data['comments'] ?? null,
        'flight_date' => $data['flight_date'] ?? null,
        'link1' => $data['link1'] ?? null,
        'link2' => $data['link2'] ?? null,
        'link3' => $data['link3'] ?? null
    ]);

    $response->getBody()->write(json_encode(['success' => true, 'id' => $database->id()]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Update an existing tour leg
$app->put('/legs/{id}', function (Request $request, Response $response, $args) use ($database) {
    $id = $args['id'];
    $data = $request->getParsedBody();
    // Ensure origin and destination are uppercase
    if (isset($data['origin'])) {
        $data['origin'] = strtoupper($data['origin']);
    }
    if (isset($data['destination'])) {
        $data['destination'] = strtoupper($data['destination']);
    }

    // Validate required fields
    if (empty($data['tour_id']) || empty($data['origin']) || empty($data['destination'])) {
        $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Check if leg exists
    $existing = $database->get('tour_legs', ['id'], ['id' => $id]);
    if (!$existing) {
        $response->getBody()->write(json_encode(['error' => 'Tour leg not found']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    $database->update('tour_legs', [
        'tour_id' => $data['tour_id'],
        'origin' => $data['origin'],
        'destination' => $data['destination'],
        'aircraft' => $data['aircraft'] ?? null,
        'route' => $data['route'] ?? null,
        'comments' => $data['comments'] ?? null,
        'flight_date' => $data['flight_date'] ?? null,
        'link1' => $data['link1'] ?? null,
        'link2' => $data['link2'] ?? null,
        'link3' => $data['link3'] ?? null
    ], [
        'id' => $id
    ]);

    $response->getBody()->write(json_encode(['success' => true]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Delete a tour leg by id
$app->delete('/legs/{id}', function (Request $request, Response $response, $args) use ($database) {
    $id = $args['id'];
    // Check if leg exists
    $existing = $database->get('tour_legs', ['id'], ['id' => $id]);
    if (!$existing) {
        $response->getBody()->write(json_encode(['error' => 'Tour leg not found']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }
    $database->delete('tour_legs', ['id' => $id]);
    $response->getBody()->write(json_encode([
        'success' => true,
        'message' => 'Tour leg deleted successfully'
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
