<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 26/12/2018
 * Time: 23:46
 */

require_once __DIR__ . '/../libs/Discord.php';

class HelloController
{
    static public function hello() {
        //$res = Discord::discord_get_user_roles(381178649480658948); // [LSD] YannZeRookie
        //$res = Discord::discord_get_user_roles(200327857816207360); // Golgho
        $res = Discord::discord_get_roles();
        return [
            'debug' => print_r($res, true),
            'status' => Discord::$status,
        ];
    }
}
