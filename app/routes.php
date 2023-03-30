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
use Doctrine\DBAL\Connection;

function parse_cookie(Request $request){
    $cookie = $request->getCookieParams();
    return json_decode($cookie['exp']);
}
function render(Response $response, string $page, array $args=[]) : Response{
    $renderer = new PhpRenderer(__DIR__.'/../templates');
    $renderer->setLayout('layout.phtml');
    $response = $renderer->render($response, $page, $args);
    return $response;
}

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/favicon.ico', function (Request $request, Response $response) {
        $file = __DIR__.'/../public/favicon.ico';
        $response = $response->withHeader('Content-Description', 'File Transfer')
       ->withHeader('Content-Type', 'image/x-icon')
       ->withHeader('Content-Disposition', 'attachment;filename="'.basename($file).'"')
       ->withHeader('Expires', '0')
       ->withHeader('Cache-Control', 'must-revalidate')
       ->withHeader('Pragma', 'public')
       ->withHeader('Content-Length', filesize($file)); 
        readfile($file);
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $cookie = $request->getCookieParams();
        if(array_key_exists('success', $cookie)){
            return $response = $response->withHeader('Location', 'success')->withStatus(302);
        }
        $response = render($response, "landing.phtml");
        return $response;
    });

    $app->get('/userstudy', function (Request $request, Response $response, $args) {
        $cookie = $request->getCookieParams();
        if(array_key_exists('success', $cookie)){
            return $response = $response->withHeader('Location', 'success')->withStatus(302);
        }
        if(array_key_exists('exp', $cookie)){
            $exp = json_decode($cookie['exp'], true );
        }
        else{
            $userStudy = new UserStudy();
            // NOTE: get expertise
            $parsedQuery = $request->getQueryParams();
            // $parsedBody = $request->getParsedBody();
            if (array_key_exists('expertise_id',  $parsedQuery)){
                $expertiseId = $parsedQuery['expertise_id'];
            }
            else{
                $expertiseId = null;
            }
            $exp = $userStudy->getStudy($expertiseId);
        }
        $response = render($response, "ListenerForm.phtml", $exp['samples']);
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

        $parsedInput = [];
        foreach($samples as $sample){
            // $parsedInput[$method] = 
            array_push($parsedInput, [
                'user'=>$user,
                'sample'=>$sample->id,
                'time'=>'"'.(new DateTime())->format(DateTime::ISO8601).'"'
            ]);
        }
        foreach($userInputs as $userInput=>$value){
            $exploded = explode('-', $userInput);
            $local_sample_id = $exploded[0];
            $likert = $exploded[1];
            $parsedInput[$local_sample_id][$likert] = $value;
            
        }
        $success = $conn->transactional(function(Connection $conn) use ($parsedInput): string  {
            $queryBuilder = $conn->createQueryBuilder();
            foreach($parsedInput as $input){
                #TODO: make transaction in order to revert if one is failing...
                $result = $queryBuilder->insert('likert')->values($input)->executeQuery();
                // $result->fetchFirstColumn();
            }
            return 'success';
        });
        if($success == 'success'){
            $cookie = new Cookies();
            $cookie->set('success', "true");
            $header = $cookie->toHeaders();
            $response = $response->withAddedHeader('Set-Cookie', $header);
            $response = $response->withHeader('Location', 'success')->withStatus(302);
        }
        return $response;
    });

    $app->get('/audio', function (Request $request, Response $response) {
        $local_sample_id = $request->getQueryParams()['local_sample_id'];
        $exp = parse_cookie($request);
        $samples = $exp->samples;
        $id = $samples[$local_sample_id]->id;
        $conn = DB::getConnection();
        $path = $conn->createQueryBuilder()->select('path')->from('samples')->where('id = ?')->setParameter(0, $id)->fetchFirstColumn()[0];
        $fh = fopen($path, 'rb');
        $stream = new Stream($fh);
        #TODO: set headers and choose codec and dissallow that it's loading loading loading.
        return $response
                ->withBody($stream)
                ->withAddedHeader('Content-length', filesize($path))
                ->withHeader('Content-Type', 'audio/ogg')
                ->withAddedHeader('Content-Disposition', 'inline;')
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
