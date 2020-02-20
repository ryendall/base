<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// custom error handler
$container['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        return $response->withStatus(500)
            ->withHeader('Content-Type', 'text/json')
            ->write(json_encode(['error' => $exception->getMessage()]));
    };
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\SendGridHandler('imutual', getenv('SENDGRID_PWD'), 'richard@imutual.co.uk', getenv('ADMIN_ALERT_EMAIL'), getenv('IM_ENVIRONMENT').' API critical error', Monolog\Logger::CRITICAL));
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

$container['LinksController'] = function ($c) {
    return new LinksController($c);
};

// database
$container['db'] = function ($c) {
    $settings = $c->get('settings')['db'];
    $db = new sql_db($settings['host'], $settings['user'], getenv('DB_BACKEND_PASSWORD'), $settings['name']);
    return $db;
};