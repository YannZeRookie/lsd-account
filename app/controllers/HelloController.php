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
        global $discord_channel_review;

        $cur_user = self::checkAccess();

        // Discord::sendChannelMessage($discord_channel_review, 'Salut à <@381178649480658948>');

        $debug = '';
        return [
            'cur_user' => $cur_user,
            'debug' => print_r($debug, true),
        ];
    }
}
