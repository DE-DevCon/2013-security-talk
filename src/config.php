<?php
return function($app) {
    $app->config('debug', false);

    $app->config('templates.path', __DIR__ . '/templates');
    $view = $app->view(new \Slim\Views\Twig());
    $view->parserExtensions = [new \Slim\Views\TwigExtension()];

    $app->add(new \Slim\Middleware\SessionCookie());

    $app->container->singleton('database', function() {
        $databaseUrl = getenv('DATABASE_URL');
        if (!$databaseUrl) {
            throw new Exception('Missing DATABASE_URL environment variable');
        }

        $database = pg_connect($databaseUrl);

        if (!$database) {
            throw new Exception('Failed to connect to database');
        }

        return $database;
    });

    $app->container->singleton('salt', function() {
        $salt = getenv('SALT');
        if (!$salt) {
            throw new Exception('Missing SALT environment variable');
        }

        return $salt;
    });
};
