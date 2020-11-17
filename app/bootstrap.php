<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 26/12/2018
 * Time: 11:28
 */

# Locale
setlocale(LC_TIME, 'fr_FR.utf8');

# Load config
require_once __DIR__ . '/../config/config.php';

# Connect to database
ActiveRecord::setDb(new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_database};charset=utf8", $db_user, $db_pass));

# Discord API
require_once __DIR__ . '/libs/Discord.php';
Discord::init($discord_api, $discord_bot_token, $discord_guild_id);

# Activate PHP sessions
session_start();

# Slim: see http://www.slimframework.com/docs/v3/
# Create app
$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => $development,
    ],
]);

# Get extensions container
$container = $app->getContainer();

# Initialize Flash messages
# See https://www.slimframework.com/docs/v3/features/flash.html
# See https://github.com/kanellov/slim-twig-flash
$container['flash'] = function () {
    return new \Slim\Flash\Messages();
};

# Initialize Twig
# See https://www.slimframework.com/docs/v3/features/templates.html#the-slimtwig-view-component
# See https://twig.symfony.com/doc/2.x/api.html#environment-options
# https://packagist.org/packages/slim/twig-view#2.5.1
# https://github.com/slimphp/Twig-View
$container['view'] = function ($container) {
    global $twig_debug, $twig_auto_reload;
    $view = new \Slim\Views\Twig(__DIR__ . '/views', [
        'cache' => __DIR__ . '/../cache',
        'debug' => $twig_debug,
        'auto_reload' => $twig_auto_reload,
    ]);
    // Instantiate and add Slim specific extension
    $router = $container->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
    $view->addExtension(new Knlv\Slim\Views\TwigMessages($container->get('flash')));   // Flash messages see https://github.com/kanellov/slim-twig-flash
    if ($twig_debug) {
        $view->addExtension(new \Twig\Extension\DebugExtension());
    }
    return $view;
};


# Add my own CONVENIENT redirect function
function redirectTo($uri, $status = 302)
{
    header('Location: ' . $uri);
    if ($status != 302) {
        http_response_code($status);
    }
    exit();
}


# Load our routes
require_once __DIR__ . '/routes.php';

# Run app
$app->run();
