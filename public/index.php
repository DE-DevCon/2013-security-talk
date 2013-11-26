<?php
$appDir = dirname(__DIR__);
require_once "{$appDir}/vendor/autoload.php";

$app = new \Slim\Slim();

$configure = require "{$appDir}/src/config.php";
$configure($app);

$routes = require "{$appDir}/src/routes.php";
$routes($app);

$app->run();
