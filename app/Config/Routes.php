<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('database', 'Home::database');
$routes->get('ttx', 'Home::ttx');


$routes->group('bot', ['namespace' => 'App\Controllers'], function ($routes) {
    $routes->match(['get', 'post'], '/', 'Bot::botApi', ["as" => "bot"]);
    $routes->match(['get', 'post'], 'mail', 'Mail::mail_cron', ["as" => "mail_cron"]);
});
