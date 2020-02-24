<?php
use Slim\Http\Request;
use Slim\Http\Response;

// Routes
// Library group
$app->group('/commodity', function () use ($app) {

    $app->get('/items', CommodityController::class . ':list');
    $app->post('/items', CommodityController::class . ':create');
    $app->get('/items/{id}', CommodityController::class . ':read');
    $app->put('/items/{id}', CommodityController::class . ':update');
    $app->delete('/items/{id}', CommodityController::class . ':delete');

    $app->get('/classes', CommodityClassController::class . ':list');
    $app->post('/classes', CommodityClassController::class . ':create');
    $app->get('/classes/{id}', CommodityClassController::class . ':read');
    $app->put('/classes/{id}', CommodityClassController::class . ':update');
    $app->delete('/classes/{id}', CommodityClassController::class . ':delete');

    $app->get('/segments', SegmentController::class . ':list');
    $app->post('/segments', SegmentController::class . ':create');
    $app->get('/segments/{id}', SegmentController::class . ':read');
    $app->put('/segments/{id}', SegmentController::class . ':update');
    $app->delete('/segments/{id}', SegmentController::class . ':delete');

    $app->get('/families', FamilyController::class . ':list');
    $app->post('/families', FamilyController::class . ':create');
    $app->get('/families/{id}', FamilyController::class . ':read');
    $app->put('/families/{id}', FamilyController::class . ':update');
    $app->delete('/families/{id}', FamilyController::class . ':delete');
});


// healthchecks
$app->get('/ping', ApiController::class . ':ping');
$app->get('/health', ApiController::class . ':health');
$app->get('/test', ApiController::class . ':test');

$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});
