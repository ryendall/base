<?php
// Application middleware
use im\model\ApiUsers;
// Authentication
$obj = new ApiUsers($container['db']);
$users=$obj->getAllTitles('username','password');
$app->add(new \Tuupola\Middleware\HttpBasicAuthentication([
    "path" => ["/"],
    "ignore" => ["/ping", "/health"],
    "realm" => "Protected",
    "secure" => getenv('IM_ENVIRONMENT') !== 'development',
    "users" => $users
]));
unset($users);
