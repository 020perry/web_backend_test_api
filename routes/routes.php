<?php

/** @var Bramus\Router\Router $router */

// Define routes here
$router->get('/test', App\Controllers\IndexController::class . '@test');
$router->get('/', App\Controllers\IndexController::class . '@test');
$router->post('/facility', App\Controllers\FacilityController::class . '@create');
$router->get('/facility/{id}', App\Controllers\FacilityController::class . '@read');
$router->put('/facility/{id}', App\Controllers\FacilityController::class . '@update');
$router->delete('/facility/{id}', App\Controllers\FacilityController::class . '@delete');
$router->get('/facilities', App\Controllers\FacilityController::class . '@readAll');
$router->post('/facilities/search', App\Controllers\FacilityController::class . '@search');
