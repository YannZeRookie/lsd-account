<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 26/12/2018
 * Time: 23:46
 */


class HelloController
{
    static protected function checkAccess()
    {
        //-- Check rights
        $cur_user = User::getConnectedUser();
        return $cur_user;
    }

    static public function hello() {
        $cur_user = self::checkAccess();
        $debug = '';
        return [
            'cur_user' => $cur_user,
            'debug' => print_r($debug, true),
        ];
    }
}
