<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 26/12/2018
 * Time: 15:55
 */

require_once __DIR__ . '/../models/Section.php';
require_once __DIR__ . '/../models/User.php';

class IndexController
{
    static public function index()
    {
        //-- Do we have a connected user? If not, bail out
        $cur_user = User::getConnectedUser();
        if (!$cur_user) {
            \Slim\Slim::getInstance()->redirect('/login/expired');
        }

        //-- Depending on the type of users, we redirect to one page or another:
        //   - Regular user -> their own page
        //   - Privileged user (officer and above...) -> users list
        if (UsersController::canListUsers($cur_user->id)) {
            \Slim\Slim::getInstance()->redirect('/users');
        }
        \Slim\Slim::getInstance()->redirect('/users/' . $cur_user->id);

        return [];
    }
}
