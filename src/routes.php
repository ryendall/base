<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes
$app->get('/links/trace', LinksController::class . ':traceRedirects');
$app->get('/links/final', LinksController::class . ':getFinalUrl');
$app->post('/bank/notify/{id}', BankController::class . ':notify');
$app->get('/bank/oauthCode/{id}', BankController::class . ':oauthCode');
$app->put('/bank/transactions/{id}', BankController::class . ':fetchTransactions');
$app->get('/bank/transaction/{id}/{uuid}', BankController::class . ':getTransaction');
$app->post('/payment/account', PaymentController::class . ':addAccount');
$app->post('/payment/payUser', PaymentController::class . ':payUser');
$app->put('/payment/send/{id}', PaymentController::class . ':sendPayment');
$app->get('/payment/transaction/{id}', PaymentController::class . ':getTransaction');

// healthchecks
$app->get('/ping', ApiController::class . ':ping');
//$app->get('/health', BankController::class . ':checkAccount');
$app->get('/health', ApiController::class . ':health');
$app->get('/test', ApiController::class . ':test');

// AutoSSL passthru
$app->get('/.well-known/acme-challenge/{filename}', function($req, $res, $args) {
    $file = '.well-known/acme-challenge/'.$args['filename'];
    if  (!file_exists($file) ) throw new Exception('Not found',404);
    $response = $res->withHeader('Content-Description', 'File Transfer')
   ->withHeader('Content-Type', 'application/octet-stream')
   ->withHeader('Content-Disposition', 'attachment;filename="'.basename($file).'"')
   ->withHeader('Expires', '0')
   ->withHeader('Cache-Control', 'must-revalidate')
   ->withHeader('Pragma', 'public')
   ->withHeader('Content-Length', filesize($file));
    readfile($file);
    return $response;
});


$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});
