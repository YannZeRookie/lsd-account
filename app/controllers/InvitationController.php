<?php

/**
 * Created by PhpStorm.
 * User: yann
 * Date: 13/06/2020
 * Time: 15:03
 *
 * Invitations list
 */
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/User.php';


class InvitationController
{
    /**
     * Control who can have access
     * @return ActiveRecord|bool
     */
    static protected function checkAccess()
    {
        $cur_user = User::getConnectedUser();
        if (!$cur_user || !$cur_user->canSeeInvitations()) {
            \Slim\Slim::getInstance()->redirect('/');
        }
        return $cur_user;
    }

    static public function all($params)
    {
        $cur_user = self::checkAccess();
        $params = array_merge(['delay' => '', 'mentor' => ''], $params);

        $i = new Invitation;
        if (empty($params['delay'])) {
            $i->gt('expiration', 7);
        }
        if ($params['mentor'] == 'mine') {
            $i->eq('by_discord_id', $cur_user->discord_id);
        }
        $invitations = $i->order('id')->findAll();


        $debug = '';
        return [
            'cur_user' => $cur_user,
            'invitations' => $invitations,
            'delay' => $params['delay'],
            'mentor' => $params['mentor'],
            'debug' => print_r($debug, true),
        ];
    }


}
