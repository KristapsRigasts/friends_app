<?php


use App\Redirect;
use App\View;

require_once 'vendor/autoload.php';

session_start();

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {

    $r->addRoute('GET', '/', ['App\Controllers\UsersController', 'home']);
    $r->addRoute('GET', '/users', ['App\Controllers\UsersController', 'index']);
    $r->addRoute('GET', '/users/{id:\d+}', ['App\Controllers\UsersController', 'show']);

    $r->addRoute('POST', '/users', ['App\Controllers\UsersController', 'store']);
    $r->addRoute('GET', '/users/register', ['App\Controllers\UsersController', 'register']);

    $r->addRoute('GET', '/users/login', ['App\Controllers\UsersController', 'logIn']);
    $r->addRoute('POST', '/users/login', ['App\Controllers\UsersController', 'validateLogIn']);
    $r->addRoute('GET', '/users/logout', ['App\Controllers\UsersController', 'logOut']);


    //articles
    $r->addRoute('GET', '/articles', ['App\Controllers\ArticlesController', 'index']);
    $r->addRoute('GET', '/articles/{id:\d+}', ['App\Controllers\ArticlesController', 'show']);

    $r->addRoute('POST', '/articles', ['App\Controllers\ArticlesController', 'store']);
    $r->addRoute('GET', '/articles/create', ['App\Controllers\ArticlesController', 'create']);

    $r->addRoute('POST', '/articles/{id:\d+}/delete', ['App\Controllers\ArticlesController', 'delete']);

    $r->addRoute('GET', '/articles/{id:\d+}/edit', ['App\Controllers\ArticlesController', 'edit']);
    $r->addRoute('POST', '/articles/{id:\d+}', ['App\Controllers\ArticlesController', 'update']);

});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        // ... call $handler with $vars

$controller = $handler[0];
$method = $handler[1];

/** @var View $response */
$response = (new $controller)->$method($vars);

        $loader = new \Twig\Loader\FilesystemLoader('app/Views');
        $twig = new \Twig\Environment($loader);

        if($response instanceof View) {
            echo $twig->render($response->getPath() . '.html', $response->getVariables());
        }

        if ($response instanceof Redirect)
        {
            header('Location: ' . $response->getLocation());
            exit;
        }

        break;
}