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

# Load Twig Views
require_once __DIR__ . '/libs/TwigView.php';

# Connect to database
ActiveRecord::setDb(new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_database}", $db_user, $db_pass));

# Discord API
require_once __DIR__ . '/libs/Discord.php';
Discord::init($discord_api, $discord_bot_token, $discord_guild_id);

# Create app
$app = new \Slim\Slim([
    'mode' => ($development ? 'development' : 'production'),
    'view' => new TwigView(__DIR__ . '/views', ['cache' => __DIR__ . '/../cache', 'debug' => $twig_debug, 'auto_reload' => $twig_auto_reload]),
    'templates.path'    => __DIR__ . '/views',
    'debug'             => $slim_debug,
    'cookies.encrypt'   => true,
]);

# Initialize Session Management
$app->add(new \Slim\Middleware\SessionCookie(array(
    'expires' => '1 day',
    'path' => '/',
    'domain' => null,
    'secure' => false,
    'httponly' => false,
    'name' => 'lsd_session',
    'secret' => 'ivfusq5WrgHuM7ycr4YX\sw@ar74NKLPKekPvG9Sy5DYB',
    'cipher' => MCRYPT_RIJNDAEL_256,
    'cipher_mode' => MCRYPT_MODE_CBC
)));

# Load our routes
require_once __DIR__ . '/routes.php';

# Run app
$app->run();
