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
        $end_point = '/users/@me';
        $res = Discord::api_get($end_point);
        return [
            'debug' => "GET $end_point\n" . print_r($res, true),
            'status' => Discord::$status,
        ];
    }
}
