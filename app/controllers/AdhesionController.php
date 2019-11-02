<?php

/**
 * Created by PhpStorm.
 * User: yann
 * Date: 02/11/2019
 * Time: 21:35
 *
 * Management of payments
 */
require_once __DIR__ . '/../models/Adhesion.php';
require_once __DIR__ . '/../models/Transaction.php';


class AdhesionController
{
    static protected function checkAccess()
    {
        //-- Check rights: the connected user can pay only if he/she is a Bureau member or admin
        $cur_user = User::getConnectedUser();
        if (!$cur_user || !$cur_user->canSeeAdhesions()) {
            \Slim\Slim::getInstance()->redirect('/');
        }
        return $cur_user;
    }


    static public function all($year = '', $params = [])
    {
        $cur_user = self::checkAccess();

        $first_year = 2019;
        $cur_year = date("Y");
        $year = intval($year);
        if (empty($year)) {
            $year = $cur_year;
        }
        $from = mktime(0, 0, 0, 1, 1, $year);
        $to = mktime(23, 59, 59, 12, 31, $year);

        $a = new Adhesion();
        $where = "lsd_adhesions.created_on between $from and $to";
        if (!isset($params['showall']) || !$params['showall']) {
            $where .= " AND t.txn_id is not null";
        }

        $a->select('lsd_adhesions.*', 't.txn_id', 't.ipn_status', 't.payment_date',
            't.first_name as tx_first_name', 't.last_name as tx_last_name',
            't.payer_email', 't.residence_country',
            'u.discord_username', 'u.discord_id', 'u.discord_discriminator', 'u.discord_avatar'
            )->where($where)
            ->join('lsd_transactions as t', 't.adhesion_id=lsd_adhesions.id', 'LEFT')
            ->join('lsd_users as u', 'u.id=lsd_adhesions.user_id', 'LEFT')
            ->orderby('lsd_adhesions.id');
        $adhesions = $a->findAll();
        $u = new User;
        foreach ($adhesions as &$ad)
        {
            $u->discord_id = $ad->discord_id;
            $u->discord_id = $ad->discord_id;
            $u->discord_discriminator = $ad->discord_discriminator;
            $u->discord_avatar = $ad->discord_avatar;
            $ad->_avatar = $u->avatar();
            $ad->_amount_fr = number_format($ad->amount, 2, ',', ' ');
            $ad->txn_id = $ad->txn_id ?? '';
            $ad->ipn_status = $ad->ipn_status ?? '';
            $ad->payer_email = $ad->payer_email ?? '';
            $ad->residence_country = $ad->residence_country ?? '';
        }

        $debug = '';
        return [
            'cur_user' => $cur_user,
            'debug' => print_r($debug, true),
            'adhesions' => $adhesions,
            'limited' => $cur_user->isConseiller(),
            'first_year' => $first_year,
            'cur_year' => $cur_year,
            'year' => $year,
            'showall' => $params['showall'] ?? false,
        ];
    }


}
