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

        $debug = '';
        return [
            'cur_user' => $cur_user,
            'debug' => print_r($debug, true),
            'dates' => self::buildDates(),
        ];
    }

    static protected function buildDates()
    {
        $result = [];
        $result['today'] = date('d/m/Y');
        $yesterday = mktime(0,0,0, date('m'), date('d')-1, date('Y'));
        $result['yesterday'] = date('d/m/Y', $yesterday);
        $past7d = mktime(0,0,0, date('m'), date('d')-7, date('Y'));
        $result['past7d'] = date('d/m/Y', $past7d);
        $past30d = mktime(0,0,0, date('m'), date('d')-30, date('Y'));
        $result['past30d'] = date('d/m/Y', $past30d);
        $past6m = mktime(0,0,0, date('m')-6, date('d'), date('Y'));
        $result['past6m'] = date('d/m/Y', $past6m);
        $past12m = mktime(0,0,0, date('m')-12, date('d'), date('Y'));
        $result['past12m'] = date('d/m/Y', $past12m);
        return $result;
    }

    static protected function dateToTS($date)
    {
        $date = preg_replace('/[^\d\/]/', '', $date);
        if (preg_match('/^(\d+)\/(\d+)\/(\d+)$/', $date, $reg)) {
            return mktime(0,0,0, $reg[2], $reg[1], $reg[3]);
        }
        return false;
    }

    /**
     * AJAX search of logs
     * @param array $params
     * @return array
     */
    static public function search($params = [])
    {
        $cur_user = self::checkAccess();
        $params = array_merge(['s_from_date' => '', 's_to_date' => '', 's_subject' => '', 's_target' => '', 's_action' => '', 's_old_values' => '', 's_new_values' => ''], $params);
        $params['s_subject'] = trim($params['s_subject']);
        $params['s_target'] = trim($params['s_target']);

        $l = new Log;
        $l->select('SQL_CALC_FOUND_ROWS lsd_logs.*',
            'ifnull(u.discord_id,0) as u_discord_id', 'u.discord_username as u_discord_username', 'u.discord_avatar as u_discord_avatar',
            'ifnull(t.discord_id,0) as t_discord_id', 't.discord_username as t_discord_username', 't.discord_avatar as t_discord_avatar');

        //-- Analyse the search parameters and build the SQL query
        $from_date = self::dateToTS($params['s_from_date']);
        if ($from_date) {
            $l->greaterthan('created_on', $from_date);
        }
        $to_date = self::dateToTS($params['s_to_date']);
        if ($to_date) {
            $l->lessthan('created_on', $to_date + 24*3600); // Not great: not all days have 24 hours...
        }
        $l->join('lsd_users as u', 'u.id=lsd_logs.user_id', $params['s_subject'] ? 'INNER' : 'LEFT');
        if ($params['s_subject']) {
            $l->addCondition('u.discord_username', 'like', '%' . $params['s_subject'] . '%', 'AND', 'join');
        }
        $l->join('lsd_users as t', 't.id=lsd_logs.target_id', $params['s_target'] ? 'INNER' : 'LEFT');
        if ($params['s_target']) {
            $l->addCondition('t.discord_username', 'like', '%' . $params['s_target'] . '%', 'AND', 'join');
        }
        if ($params['s_action']) {
            $l->eq('action', $params['s_action']);
        }
        if ($params['s_old_values']) {
            $l->like('old_values', '%' . $params['s_old_values'] . '%');
        }
        if ($params['s_new_values']) {
            $l->like('new_values', '%' . $params['s_new_values'] . '%');
        }


        //--- Paging
        $pagination = 20;    // Number of items per page
        $page = intval($params['s_page'] ?? 1);
        $start = ($page-1)*$pagination;

        //-- Search
        $logs = $l->order('id')->limit($start, $pagination)->findAll();
        foreach ($logs as &$log) {
            $log->_u_avatar = User::buildAvatar($log->u_discord_id, $log->u_discord_avatar);
            $log->_t_avatar = User::buildAvatar($log->t_discord_id, $log->t_discord_avatar);
        }

        $total = LsdActiveRecord::rowCount();
        $pages = intdiv($total, $pagination) + ($total % $pagination ? 1 : 0);
        return [
            'cur_user' => $cur_user,
            'logs' => $logs,
            'page' => $page,
            'pages' => $pages,
        ];
    }
}
