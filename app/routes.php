<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 28/12/2018
 * Time: 23:41
 */

//-- Use this for tests and debug:
require_once 'controllers/HelloController.php';
$app->get('/hello[/{coucou}]', function ($request, $response, $args) {
    return $this->view->render($response, 'hello.html', HelloController::hello($request, $response, $args));
});


require_once 'controllers/IndexController.php';
$app->get('/', function ($request, $response, $args) {
    return $this->view->render($response, 'index.html', IndexController::index());
});


//-- Login and sign-up
require_once 'controllers/LoginController.php';
$app->get('/login/{key}', function ($request, $response, $args) {
    global $connect_force_user;
    if (!isset($connect_force_user)) {
        $connect_force_user = null;
    }
    return $this->view->render($response, 'login.html', LoginController::login($args['key'], $connect_force_user));
});

$app->get('/logout', function ($request, $response, $args) {
    return $this->view->render($response, '', LoginController::logout());
});


require_once 'controllers/SignupController.php';
$app->get('/signup', function ($request, $response, $args) {
    return $this->view->render($response, 'signup.html', SignupController::signup($this));
});
$app->post('/signup', function ($request, $response, $args) {
    return $this->view->render($response, 'signup.html', SignupController::signup($this, $request->getParsedBody()));
});
$app->get('/signup/pending', function ($request, $response, $args) {
    return $this->view->render($response, 'signup_pending.html', SignupController::pending());
});
$app->get('/signup/refused', function ($request, $response, $args) {
    return $this->view->render($response, 'signup_refused.html', SignupController::refused());
});
$app->get('/signup/vb', function ($request, $response, $args) {
    return $this->view->render($response, 'signup_vb.html', SignupController::signupVB());
});
$app->post('/signup/vb', function ($request, $response, $args) {
    return $this->view->render($response, 'signup_vb.html', SignupController::signupVBPost($this, $request->getParsedBody()));
});
$app->get('/inscription', function ($request, $response, $args) {
    return $this->view->render($response, 'signup_explanation.html', SignupController::inscription());
});

//-- Users CRUD
require_once 'controllers/UsersController.php';
// List of all users
$app->get('/users', function ($request, $response, $args) {
    return $this->view->render($response, 'users_list.html', UsersController::all());
});
// Ajax-call for list of users (search)
$app->get('/users/search', function ($request, $response, $args) {
    return $this->view->render($response, 'users_search.html', UsersController::search($request->getQueryParams()));
});
// Export a list of users (list)
$app->get('/users/export', function ($request, $response, $args) {
    UsersController::export($request->getQueryParams());
    return $response->withHeader('Content-Type', 'text/plain; charset=UTF-8');
});
// Candidates review
$app->get('/users/review', function ($request, $response, $args) {
    return $this->view->render($response, 'users_review.html', UsersController::review());
});
$app->post('/users/review/{id:\d+}', function ($request, $response, $args) {
    return $this->view->render($response, '', UsersController::reviewUser($args['id'], $request->getParsedBody()));
});

// User View (GET)
$app->get('/users/{id:\d+}', function ($request, $response, $args) {
    return $this->view->render($response, 'users_view.html', UsersController::view($args['id'], $request->getQueryParams()));
});
// User Change (POST)
$app->post('/users/{id:\d+}', function ($request, $response, $args) {
    return $this->view->render($response, 'users_view.html', UsersController::post($this, $args['id'], $request->getQueryParams(), $request->getParsedBody()));
});

//-- Sections CRUD
require_once 'controllers/SectionsController.php';
// List of all Sections
$app->get('/sections', function ($request, $response, $args) {
    return $this->view->render($response, 'sections_list.html', SectionsController::all());
});
// Section View (GET)
$app->get('/sections/{tag}', function ($request, $response, $args) {
    return $this->view->render($response, 'sections_edit.html', SectionsController::edit($args['tag']));
});
// Section Change (POST)
$app->post('/sections/{tag}', function ($request, $response, $args) {
    return $this->view->render($response, 'sections_edit.html', SectionsController::post($this, $args['tag'], $request->getParsedBody()));
});
// Section Notes (GET)
$app->get('/sections/{tag}/notes', function ($request, $response, $args) {
    return $this->view->render($response, 'sections_notes.html', SectionsController::notes($this, $args['tag']));
});
$app->post('/sections/{tag}/notes', function ($request, $response, $args) {
    return $this->view->render($response, 'sections_notes.html', SectionsController::notes($this, $args['tag'], $request->getParsedBody()));
});
$app->post('/sections/{tag}/markdown', function ($request, $response, $args) {
    $params = $request->getParsedBody();
    SectionsController::markdown($args['tag'], $params['markdown'] ?: '');
});

//-- Become a paying member (adherent)
require_once 'controllers/AdhererController.php';
$app->get('/adherer', function ($request, $response, $args) {
    return $this->view->render($response, 'adherer.html', AdhererController::adherer());
});
$app->post('/adherer', function ($request, $response, $args) {
    return $this->view->render($response, 'adherer.html', AdhererController::post($request->getParsedBody()));
});
$app->get('/adherer/merci', function ($request, $response, $args) {
    return $this->view->render($response, 'adherer_merci.html', AdhererController::merci($request->getQueryParams()));
});
$app->post('/adherer/merci', function ($request, $response, $args) {
    return $this->view->render($response, 'adherer_merci.html', AdhererController::merci($request->getParsedBody()));
});
$app->get('/adherer/annuler', function ($request, $response, $args) {
    return $this->view->render($response, 'adherer_annuler.html', AdhererController::annuler($request->getQueryParams()));
});
$app->post('/adherer/annuler', function ($request, $response, $args) {
    return $this->view->render($response, 'adherer_annuler.html', AdhererController::annuler($request->getParsedBody()));
});
$app->get('/adherer/ipn', function ($request, $response, $args) {
    AdhererController::ipn($request->getQueryParams(), $request);
});
$app->post('/adherer/ipn', function ($request, $response, $args) {
    AdhererController::ipn($request->getParsedBody(), $request);
});

//-- Payments management
require_once 'controllers/AdhesionController.php';
$app->get('/adhesions', function ($request, $response, $args) {
    return $this->view->render($response, 'adhesions.html', AdhesionController::all('', $request->getQueryParams()));
});
$app->get('/adhesions/{year}', function ($request, $response, $args) {
    return $this->view->render($response, 'adhesions.html', AdhesionController::all($args['year'], $request->getQueryParams()));
});

//-- Invitations
require_once 'controllers/InvitationController.php';
$app->get('/invitations', function ($request, $response, $args) {
    return $this->view->render($response, 'invitations.html', InvitationController::all($request->getQueryParams()));
});

//-- Admin corner
require_once 'controllers/AdminController.php';
$app->get('/admin', function ($request, $response, $args) {
    global $bot_folder;
    return $this->view->render($response, 'admin.html', AdminController::index($bot_folder));
});
$app->get('/admin/updateweb', function ($request, $response, $args) {
    global $update_pid;
    return $this->view->render($response, 'admin_updating.html', AdminController::update($update_pid, 'web'));
});
$app->get('/admin/updatebot', function ($request, $response, $args) {
    global $update_pid;
    return $this->view->render($response, 'admin_updating.html', AdminController::update($update_pid, 'bot'));
});
$app->post('/admin/updatedb', function ($request, $response, $args) {
    return $this->view->render($response, 'admin_updatedb.html', AdminController::updatedb($request->getParsedBody()));
});

//-- Logs
require_once 'controllers/LogController.php';
$app->get('/logs', function ($request, $response, $args) {
    return $this->view->render($response, 'logs.html', LogController::all());
});
// Ajax-call for list of users (search)
$app->get('/logs/search', function ($request, $response, $args) {
    return $this->view->render($response, 'logs_search.html', LogController::search($request->getQueryParams()));
});

//-- Members for Joomla
require_once 'controllers/MembersController.php';
$app->get('/members', function ($request, $response, $args) {
    return $this->view->render($response->withHeader('Access-Control-Allow-Origin', '*'), 'members.html', MembersController::members());
});
