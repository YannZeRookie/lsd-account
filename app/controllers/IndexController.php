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
        //   - Not a Scorpion -> inscription or pending or refused page if already submitted
        //   - Regular user -> their own page
        //   - Privileged user (officer and above...) -> users list
        if (!$cur_user->isScorpion()) {
            if ($cur_user->submited_on == 0) {
                \Slim\Slim::getInstance()->redirect('/signup'); // Enter the submission flow
            } elseif ($cur_user->reviewed_on == 0) {
                \Slim\Slim::getInstance()->redirect('/signup/pending'); // Submitted but not reviewed yet
            } else {
                // Submitted, reviewed and not a Scorpion? Then it means a refusal
                \Slim\Slim::getInstance()->redirect('/signup/refused');
            }
        }
        elseif (UsersController::canListUsers($cur_user->id)) {
            \Slim\Slim::getInstance()->redirect('/users');
        }
        \Slim\Slim::getInstance()->redirect('/users/' . $cur_user->id);

        return [];
    }
}
