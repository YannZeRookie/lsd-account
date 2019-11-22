<?php

/**
 * Created by PhpStorm.
 * User: yann
 * Date: 20/11/2019
 * Time: 21:32
 *
 * Log of role changes
 */
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/User.php';


class LogController
{
    static protected function checkAccess()
    {
        //-- Check rights: the connected user can pay only if he/she is a Bureau member or admin
        $cur_user = User::getConnectedUser();
        if (!$cur_user || !$cur_user->canSeeLogs()) {
            \Slim\Slim::getInstance()->redirect('/');
        }
        return $cur_user;
    }


    static public function all()
    {
        $cur_user = self::checkAccess();


        $l = new Log;
        $l->select('lsd_logs.*', 
            'ifnull(u.discord_id,0) as u_discord_id', 'u.discord_username as u_discord_username', 'u.discord_avatar as u_discord_avatar',
            'ifnull(t.discord_id,0) as t_discord_id', 't.discord_username as t_discord_username', 't.discord_avatar as t_discord_avatar');
        $l->join('lsd_users as u', 'u.id=lsd_logs.user_id');
        $l->join('lsd_users as t', 't.id=lsd_logs.target_id');
        $logs = $l->orderby('id')->findAll();

        foreach ($logs as &$log) {
            $log->_u_avatar = User::buildAvatar($log->u_discord_id, $log->u_discord_avatar);
            $log->_t_avatar = User::buildAvatar($log->t_discord_id, $log->t_discord_avatar);
        }

        $debug = '';
        return [
            'cur_user' => $cur_user,
            'debug' => print_r($debug, true),
            'logs' => $logs,
        ];
    }


}
