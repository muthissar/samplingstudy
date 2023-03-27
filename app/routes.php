<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use App\Mut\DB;
use Slim\Views\PhpRenderer;
use Slim\Psr7\Stream;
use Slim\Psr7\Cookies;
use App\Mut\UserStudy;

function parse_cookie(Request $request){
    $cookie = $request->getCookieParams();
    return json_decode($cookie['exp']);
}
function render(Response $response, string $page, array $args=[]) : Response{
    $renderer = new PhpRenderer('templates');
    $renderer->setLayout('layout.phtml');
    $response = $renderer->render($response, $page, $args);
    return $response;
}

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response = render($response, "landing.phtml");
        return $response;
    });

    $app->get('/userstudy', function (Request $request, Response $response) {

        $userStudy = new UserStudy();
        $exp = $userStudy->getStudy();
        $methods = array_keys($exp['samples']);
        $response = render($response, "UserStudy.phtml", $methods);
        $cookie = new Cookies();
        $cookie->set('exp', json_encode($exp));
        $header = $cookie->toHeaders();
        $response = $response->withAddedHeader('Set-Cookie', $header);
        return $response;
    });

    $app->post('/submit', function (Request $request, Response $response) {
        $exp = parse_cookie($request);
        $user = $exp->user;
        $samples = $exp->samples;
        $userInputs = $request->getParsedBody();
        $request->getAttributes();
        $request->getBody();
        // TODO: assumes that all methods are specified
        $conn = DB::getConnection();
        $queryBuilder = $conn->createQueryBuilder();

        $parsedInput = [];
        foreach($samples as $method=>$sample){
            $parsedInput[$method] = ['user'=>$user, 'sample'=>$sample, 'time'=>'CURRENT_TIME'];
        }
        foreach($userInputs as $userInput=>$value){
            $exploded = explode('-', $userInput);
            $method = $exploded[0];
            $likert = $exploded[1];
            $parsedInput[$method][$likert] = $value;
            
        }
        foreach($parsedInput as $input){
            #TODO: make transaction in order to revert if one is failing...
            $result = $queryBuilder->insert('likert')->values($input)->executeQuery();
        }
        $response = $response->withHeader('Location', 'success')->withStatus(302);
        return $response;
    });

    $app->get('/audio', function (Request $request, Response $response) {
        $method = $request->getQueryParams()['method'];
        $exp = parse_cookie($request);
        $samples = $exp->samples;
        $id = $samples[$method];
        $conn = DB::getConnection();
        $path = $conn->createQueryBuilder()->select('path')->from('samples')->where('id = ?')->setParameter(0, $id)->fetchFirstColumn()[0];
        $fh = fopen($path, 'rb');
        $stream = new Stream($fh);
        #TODO: set headers and choose codec and dissallow that it's loading loading loading.
        return $response
                ->withBody($stream)
                ->withAddedHeader('Content-length', filesize($path))
                ->withHeader('Content-Type', 'audio/mpeg')
                ;
        // $response->withAddedHeader('Content-Type', 'audio/mpeg')
        //     ->withAddedHeader('Content-length', filesize($path))
        //     ->withBody(file_get_contents($path));
        // header('Content-Type: audio/mpeg');
        // header('Content-length: ' . filesize('/path/to/your/file.mp3'));
        // print file_get_contents('/path/to/your/file.mp3');
    });
    // })->setOutputBuffering(false);
    $app->get('/success', function (Request $request, Response $response) {
        $response = render($response, "Success.phtml");
        return $response;
    });
};
