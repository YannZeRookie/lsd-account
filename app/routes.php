<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 28/12/2018
 * Time: 23:41
 */

require_once 'controllers/IndexController.php';
$app->get('/', function () use ($app) {
    $app->render('index.html', IndexController::index());
});

//-- Login and sign-up
require_once 'controllers/LoginController.php';
$app->get('/login/:key', function ($key) use ($app) {
    $app->render('login.html', LoginController::login($key));
});

require_once 'controllers/SignupController.php';
$app->get('/signup', function () use ($app) {
    $app->render('signup.html', SignupController::signup());
});

//-- Users CRUD
require_once 'controllers/UsersController.php';
// List of all users
$app->get('/users', function () use ($app) {
    $app->render('users_list.html', UsersController::all());
});
// Ajax-call for list of users (search)
$app->get('/users/search', function () use ($app) {
    $app->render('users_search.html', UsersController::search($app->request()->get()));
});
// User View (GET)
$app->get('/users/:id', function ($id) use ($app) {
    $app->render('users_view.html', UsersController::view($id));
});
// User Change (POST)
$app->post('/users/:id', function ($id) use ($app) {
    $app->render('users_view.html', UsersController::post($id, $app->request()->post()));
});

//-- Sections CRUD
require_once 'controllers/SectionsController.php';
// List of all Sections
$app->get('/sections', function () use ($app) {
    $app->render('sections_list.html', SectionsController::all());
});
// Section View (GET)
$app->get('/sections/:tag', function ($tag) use ($app) {
    $app->render('sections_edit.html', SectionsController::edit($tag));
});
// Section Change (POST)
$app->post('/sections/:tag', function ($tag) use ($app) {
    $app->render('sections_edit.html', SectionsController::post($tag, $app->request()->post()));
});


//-- Use this for tests and debug:
require_once 'controllers/HelloController.php';
$app->get('/hello', function () use ($app) {
    $app->render('hello.html', HelloController::hello());
});

