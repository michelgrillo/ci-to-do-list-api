<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->group('api', function ($routes) {
    $routes->get('tasks', 'Api\Task::get');
    $routes->get('tasks/(:num)', 'Api\Task::get/$1');
    $routes->post('tasks', 'Api\Task::create');
    $routes->put('tasks/(:num)', 'Api\Task::update/$1');
    $routes->delete('tasks/(:num)', 'Api\Task::delete/$1');
});