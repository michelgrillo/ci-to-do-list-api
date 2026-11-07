<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->group('api', function ($routes) {
    $routes->get('tasks', 'Task::index');
    $routes->get('tasks/(:num)', 'Task::show/$1');
    $routes->get('tasks/new', 'Task::new');
    $routes->post('tasks', 'Task::create');
    $routes->get('tasks/edit/(:num)', 'Task::edit/$1');
    $routes->put('tasks/(:num)', 'Task::update/$1');
    $routes->delete('tasks/(:num)', 'Task::delete/$1');
});