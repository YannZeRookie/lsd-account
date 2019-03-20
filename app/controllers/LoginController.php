<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 28/12/2018
 * Time: 22:18
 */

require_once __DIR__ . '/../models/Login.php';
require_once __DIR__ . '/../models/VBUser.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Role.php';

class LoginController
{
    const LOGIN_TTL = 600;

    static public function login($key)
    {
        $login = new Login;
        //-- Clear old connection keys
        Login::execute('DELETE FROM lsd_login WHERE created_on < ?', [time() - self::LOGIN_TTL]);

        //-- Find the connection key
        $login_key = $login->equal('login_key', $key)->find();
        if (!$login_key) {
            return ['error' => 'Votre clé de connexion est invalide ou a expiré.
            Re-demandez au LSD-Bot de vous créer un nouveau lien de connexion en tapant !connexion dans Discord.'];
        }
        //-- Find the user using his Discord ID
        $users = new User;
        $user = $users->equal('discord_id', $login_key->discord_id)->find();

        //-- If he's not found, create a User record
        if (!$user) {
            $user = new User;
            $user->created_on = time();
            $user->discord_id = $login_key->discord_id;
            $user->discord_username = $login_key->discord_username;
            $user->discord_discriminator = $login_key->discord_discriminator;
            $user->discord_avatar = $login_key->discord_avatar;
            $user = $user->insert();

            if ($user) {
                Role::importDiscordRole($user->id, $login_key->discord_id);    // Import user's role from Discord
            }
        }

        if (!$user) {
            return ['error' => 'Impossible de trouver ou de créer un utilisateur pour le Discord ID=' . $login_key->discord_id];
        }

        //-- User is found, let's create a session
        $_SESSION['user_id'] = $user->id;

        //-- Update the Discord info if needed
        $user->discord_username = $login_key->discord_username;
        $user->discord_discriminator = $login_key->discord_discriminator;
        $user->discord_avatar = $login_key->discord_avatar;
        $user->update();

        //-- Go to the main page if we have a Scorpion. Otherwise go to sign-up
        if ($user) {
            if ($user->isScorpion()) {
                \Slim\Slim::getInstance()->redirect('/');
            } else {
                \Slim\Slim::getInstance()->redirect('/signup');
            }
        }
        return [
            'login_key' => $login_key,
            'login_key_data' => print_r($login_key->data, true),
            'session' => print_r($_SESSION, true),
            'user' => print_r($user, true),
        ];
    }

    static protected function checkVBcredentials($vb_user, $vb_username, $vb_pwd)
    {
        //-- Check user
        $vb_users = new VBUser;
        $vb_user = $vb_users->equal('username', $vb_username)->find();
        if (!$vb_user) {
            return false;
        }

        //-- Check password
        $salt = 'D5bMc5EY7CIiLIyK28PyaJ4mfZiOGq3J';
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $pwd = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $salt, $vb_pwd, MCRYPT_MODE_ECB, $iv));
        $pwd_md5 = md5($pwd);
        return password_verify($pwd_md5, $vb_user->token);
    }
}
