<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 29/12/2018
 * Time: 18:39
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Role.php';

class SignupController
{
    static public function signup()
    {
        //-- Do we have a connected user? If not, bail out
        if ($_SESSION['user_id']) {
            $users = new User;
            $user = $users->find($_SESSION['user_id']);
        }
        if (!$user) {
            \Slim\Slim::getInstance()->redirect('/login/expired');
        }

        //-- If the user is already a Scorpion, we have nothing to do here: go to the main page
        if ($user->isScorpion()) {
            \Slim\Slim::getInstance()->redirect('/');
        }

        return [
            'session' => print_r($_SESSION,true),
            'user' => $user,
            'user_data' => print_r($user, true),
        ];
    }
}
