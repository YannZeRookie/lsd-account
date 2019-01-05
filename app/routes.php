<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 28/12/2018
 * Time: 23:41
 */

require_once 'controllers/IndexController.php';
$app->get('/', function () use ($app) {
    $app->render('index.html', IndexController::get());
});

require_once 'controllers/LoginController.php';
$app->get('/login/:key', function ($key) use ($app) {
    $app->render('login.html', LoginController::login($key));
});

require_once 'controllers/SignupController.php';
$app->get('/signup', function () use ($app) {
    $app->render('signup.html', SignupController::signup());
});

