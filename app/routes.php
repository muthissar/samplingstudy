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

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $renderer = new PhpRenderer('templates');
        $userStudy = new UserStudy();
        $exp = $userStudy->getStudy();
        $methods = array_keys($exp['samples']);
        $response = $renderer->render($response, "UserStudy.phtml", $methods);
        $cookie = new Cookies();
        $cookie->set('exp', json_encode($exp));
        $header = $cookie->toHeaders();
        $response = $response->withAddedHeader('Set-Cookie', $header);
        return $response;
    });

    $app->post('/submit', function (Request $request, Response $response) {
        #$cookie = Cookies::parseHeader($request->getHeaderLine('Cookie'));
        $exp = parse_cookie($request);
        $user = $exp->user;
        $samples = $exp->samples;
        // $cookie = $request->getHeaderLine('Cookie');
        $userInputs = $request->getParsedBody();
        $request->getAttributes();
        $request->getBody();
        // $request->get;
        // TODO: assumes that all methods are specified
        $conn = DB::getConnection();
        $queryBuilder = $conn->createQueryBuilder();

        $parsedInput = [];
        foreach($samples as $method=>$sample){
            $parsedInput[$method] = ['user'=>$user, 'sample'=>$sample];
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
            1+1;
        }
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
                ->withHeader('Content-Type', 'audio/mp3');
    });
    // })->setOutputBuffering(false);

};
