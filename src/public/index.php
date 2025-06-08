<?php

declare(strict_types=1);

use AhkBin\Routes;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response as Response;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(false, false, false);

$app->get('/', function (Request $request, Response $response) {
	$params = $request->getQueryParams();

	if (isset($params['r']))
		return Routes::viewPasteRaw($request, $response);

	if (isset($params['e']))
		return Routes::viewPasteEdit($request, $response);

	if (isset($params['p']))
		return Routes::viewPaste($request, $response);

	return Routes::index($request, $response);
});

$app->post('/', [Routes::class, 'makePaste']);

$app->delete('/', [Routes::class, 'deletePaste']);

$app->run();
