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

    static public function hello($request, $response, $args) {
        global $discord_channel_review;

        $cur_user = self::checkAccess();

        // Discord::sendChannelMessage($discord_channel_review, 'Salut à <@381178649480658948>');

        $debug = $args;
        return [
            'cur_user' => print_r($cur_user, true),
            'debug' => print_r($debug, true),
        ];
    }
}
