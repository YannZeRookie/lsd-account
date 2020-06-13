<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 01/05/2020
 * Time: 16:18
 */

require_once __DIR__ . '/LsdActiveRecord.php';

class Invitation extends LsdActiveRecord {
    public $table = 'lsd_invitations';

    /**
     * magic function to GET the values of current object.
     * Address some fields when they are null
     */
    public function & __get($var)
    {
        $value = parent::__get($var);
        if ($value === null) {
            switch ($var) {
                case 'user_id':
                    $value = 0;
                    break;
                default:
                    $value = '';
            }
        }
        return $value;
    }

    /**
     * Search for an invitation for a user
     * @param $user_id
     * @return mixed
     */
    static public function findInvitation($user_id)
    {
        $i = new Invitation;
        return $i->equal('user_id', $user_id)->find();
    }

    /**
     * Create an invitation for a user
     *
     * @param integer $target_id Target user
     * @param integer $cur_id Creating user
     * @param integer $expiration days
     */
    static public function create($target_id, $cur_id, $expiration)
    {
        self::execute("INSERT INTO lsd_invitations (created_on, expiration, user_id, discord_id, discord_username, by_discord_id, by_discord_username)
          SELECT unix_timestamp(), ?, tu.id, tu.discord_id, tu.discord_username, cu.discord_id, cu.discord_username
          FROM lsd_users as tu
          INNER JOIN lsd_users as cu ON cu.id=?
          WHERE tu.id=?
          ", [$expiration, $cur_id, $target_id]);
    }

    /**
     * Delete invitations for a user
     * @param $user_id
     */
    static public function clear($user_id)
    {
        self::execute("DELETE i FROM lsd_invitations as i 
                        INNER JOIN lsd_users as u ON u.id = ? AND u.discord_id=i.discord_id
                        ", [$user_id]);
    }

    /**
     * Compute the invitation time out
     *
     * @return integer EPOC time when the invitation will expire
     */
    public function getTimeOut()
    {
        return $this->created_on + $this->expiration * 24 * 3600;
    }

}
