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
    static public function get()
    {
        $section = new Section;
        $sections = $section->findAll();

        //-- Do we have a connected user? If not, bail out
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $users = new User;
            $user = $users->find($_SESSION['user_id']);
        }
        if (!$user) {
            \Slim\Slim::getInstance()->redirect('/login/expired');
        }

        $isScorpion = $user->isScorpion();

        return [
            'sections' => $sections,
            'session' => print_r($_SESSION,true),
            'user' => $user,
            'user_data' => print_r($user, true),
        ];
    }
}
