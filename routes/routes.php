<?php
/** @var Bramus\Router\Router $router */
global $di;

// Define routes here
use App\Controllers\FacilityController;
use App\Models\FacilityModel;

/**
 * Helper function to create a controller instance with the associated model.
 *
 * @param string $controllerClass The fully qualified class name of the controller.
 * @return mixed The controller instance with the associated model.
 */
function createController($controllerClass)
{
    global $di; // Access the global DI container
    $db = $di->get('db'); // Get the DB connection from the DI container
    $model = new FacilityModel($db);
    return new $controllerClass($model);
}

$router->get('/test', App\Controllers\IndexController::class . '@test');
$router->get('/', App\Controllers\IndexController::class . '@test');

$router->post('/facility', function () use ($di) {
    $controller = new FacilityController(new FacilityModel($di->get('db')));
    $controller->create();
});

$router->get('/facility/{id}', function($id) {
    global $di;
    $controller = new FacilityController(new FacilityModel($di->get('db')));
    return $controller->read($id);
});

$router->put('/facility/{id}', function($id) {
    global $di;
    $controller = new FacilityController(new FacilityModel($di->get('db')));
    return $controller->update((int)$id);
});

$router->delete('/facility/{id}', function($id) {
    global $di;
    $controller = new FacilityController(new FacilityModel($di->get('db')));
    return $controller->delete($id);
});

$router->get('/facilities', function() {
    global $di;
    $controller = new FacilityController(new FacilityModel($di->get('db')));
    return $controller->readAll();
});

$router->get('/search', function() {
    global $di;
    $controller = new FacilityController(new FacilityModel($di->get('db')));
    return $controller->search();
});
