<?php
return function(\Slim\Slim $app) {
    $needsAuth = function() use($app) {
        $userHandle = $app->getCookie('handle');
        $userHash = $app->getCookie('hash');
        if ($userHandle === null || $userHash === null) {
            $app->flash('error', 'You must login to view this page.');
            $app->redirect('/login');
        } else {
            $result = pg_query_params($app->database, 'SELECT 1 FROM users WHERE handle=$1 AND hash=$2', [$userHandle, $userHash]);
            if (pg_num_rows($result) === 0) {
                $app->flash('error', 'Your login session has expired, please log in again');
                $app->result('/login');
            }
        }
    };

    $app->get('/', $needsAuth, function() use($app) {
        $handle = $app->getCookie('handle');
        $query = pg_query_params($app->database, 'SELECT * FROM tweets WHERE user_handle=$1', [$handle]);
        $tweets = pg_fetch_all($query);
        $app->render('home.html', ['handle' => $app->getCookie('handle'), 'tweets' => $tweets]);
    });

    $app->get('/tweets/:tweetId/edit', $needsAuth, function($tweetId) use($app) {
        $query = pg_query_params($app->database, "SELECT * FROM tweets WHERE id=$1", [$tweetId]);
        $tweet = pg_fetch_assoc($query);
        $app->render('edit-tweet.html', ['tweet' => $tweet]);
    });

    $app->post('/tweets/:tweetId', $needsAuth, function($tweetId) use ($app) {
        $req = $app->request();
        $description = $req->post('description');
        $query = pg_query_params($app->database, "UPDATE tweets SET description=$1 WHERE id=$2", [$description, $tweetId]);
        $app->flash('success', 'Your tweet has been updated!');
        $app->redirect('/');
    });

    $app->get('/login', function() use($app) {
        $app->render('login.html');
    });

    $app->post('/login', function() use($app) {
        $req = $app->request();
        $handle = $req->post('handle');
        $password = $req->post('password');

        $result = pg_query_params($app->database, "SELECT * FROM users WHERE handle=$1 AND password=$2", [$handle, $password]);
        if (pg_num_rows($result) > 0) {
            $hash = md5(json_encode($_SERVER) . $handle . $app->salt);
            pg_query_params($app->database, 'UPDATE users SET hash=$1 WHERE handle=$2', [$hash, $handle]);
            $app->setCookie('handle', $handle);
            $app->setCookie('hash', $hash);
            $app->flash('success', 'Thanks for logging in!');
            $app->redirect('/');
        } else {
            $app->flashNow('error', 'Your username and password failed to match.');
            $app->render('login.html');
        }
    });
};
