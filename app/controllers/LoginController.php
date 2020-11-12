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
require_once __DIR__ . '/../models/Log.php';

class LoginController
{
    const LOGIN_TTL = 600;

    static public function login($key, $connect_force_user = null)
    {
        $created_new_user = false;
        $login = new Login;
        //-- Clear old connection keys
        Login::execute('DELETE FROM lsd_login WHERE created_on < ?', [time() - self::LOGIN_TTL]);

        $users = new User;
        if ($connect_force_user)    {
            $user = $users->find($connect_force_user);
            if (!$user) {
                return ['error' => 'Utilisateur forcé non trouvé : ' . $connect_force_user . '. Vérifiez votre base de données.'];
            }
            $_SESSION['user_id'] = $user->id;
            redirectTo('/');
        } else {
            //-- Find the connection key
            $key = preg_replace('/\W/', '', $key);  // anti-poisoning
            $login_key = $login->equal('login_key', $key)->find();
            if (!$login_key) {
                return ['error' => 'Votre clé de connexion est invalide ou a expiré.
            Re-demandez au LSD-Bot de vous créer un nouveau lien de connexion en tapant !connexion dans Discord.'];
            }
            //-- Find the user using his Discord ID
            $user = $users->equal('discord_id', $login_key->discord_id)->find();
        }

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
                Log::logNewUser($user);
                $created_new_user = true;
                Role::importDiscordRole($user->id, $login_key->discord_id);    // Import user's role from Discord
            }
        }

        if (!$user) {
            return ['error' => 'Impossible de trouver ou de créer un utilisateur pour le Discord ID=' . $login_key->discord_id];
        }

        //-- User is found, let's create a session
        $_SESSION['user_id'] = $user->id;

        //-- Update the Discord info if needed
        $user->discord_username = Discord::discord_get_user_nickname($login_key->discord_id) ?: $login_key->discord_username;
        $user->discord_discriminator = $login_key->discord_discriminator;
        $user->discord_avatar = $login_key->discord_avatar ?: '';
        $user->update();

        //-- Go to the main page if we have a Scorpion. Otherwise go to sign-up
        if ($user) {
            if ($user->isScorpion()) {
                if ($created_new_user) {
                    // Go through the VB sign-up/linking
                    redirectTo('/signup/vb');
                } else {
                    redirectTo('/');
                }
            } else {
                redirectTo('/signup');
            }
        }
        return [
            'login_key' => $login_key,
            'login_key_data' => print_r($login_key->data, true),
            'session' => print_r($_SESSION, true),
            'user' => print_r($user, true),
        ];
    }

    static public function logout()
    {
        $_SESSION['user_id'] = null;
        redirectTo('/');
    }

}
