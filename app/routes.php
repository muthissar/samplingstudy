<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Mut\DB;
use Slim\Views\PhpRenderer;
// NOTE: bootstrap problems https://stackoverflow.com/questions/38202654/use-bootstrap-with-composer
// use Slim\View\Twig;
// use DI\Container;
// use Slim\Factory\AppFactory;
// // skeleton uses twig
// // https://symfony.com/doc/current/form/bootstrap5.html
// // https://twig.symfony.com/

// // Create Container
// $container = new Container();
// AppFactory::setContainer($container);

// // Set view in Container
// $container->set('view', function() {
//     return Twig::create('path/to/templates', ['cache' => 'path/to/cache']);
// });
return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $renderer = new PhpRenderer('templates');
        $args = [];
        $resp2 = $renderer->render($response, "UserStudy.phtml", $args);
        return $resp2;
    });

    $app->post('/submit', function (Request $request, Response $response) {
        $userInputs = $request->getParsedBody();
        return $response;
        // $conn = (new DB())->conn;
        // $request->post
        // $queryBuilder = $conn->createQueryBuilder();
        // $queryBuilder->
        // $response->getBody()->write('Hello world!');
        // return $response;
    });

    // $app->group('/users', function (Group $group) {
    //     $group->get('', ListUsersAction::class);
    //     $group->get('/{id}', ViewUserAction::class);
    // });
};
