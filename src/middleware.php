<?php
// Application middleware

// Authentication
$users=[];
$sql = 'SELECT * FROM api_users';
$result = $container['db']->sql_query($sql);
while ( $row=$container['db']->sql_fetchrow($result) ) {
    $users[$row['username']] = $row['password'];
}

$app->add(new \Tuupola\Middleware\HttpBasicAuthentication([
    "path" => ["/"],
    "ignore" => ["/ping", "/health", "/bank/notify", "/bank/oauthCode"],
    "realm" => "Protected",
    "secure" => getenv('IM_ENVIRONMENT') == 'production',
    "users" => $users
]));
unset($users);
