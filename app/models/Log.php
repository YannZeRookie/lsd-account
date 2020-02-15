<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 20/11/2019
 * Time: 21:45
 */

require_once __DIR__ . '/LsdActiveRecord.php';
require_once __DIR__ . '/Role.php';
require_once __DIR__ . '/User.php';

class Log extends LsdActiveRecord
{
    public $table = 'lsd_logs';
    const kCreation = 'creation';
    const kVBImport = 'vbimport';
    const kAddition = 'addition';
    const kDeletion = 'deletion';
    const kChange = 'change';
    const kAdhesion = 'adhesion';
    const kCommented = 'commented';
    const kDegrade = 'degrade';
    const kExport = 'export';

    public function __construct()
    {
        $this->created_on = time();
        $this->user_id = 0;
        $this->target_id = 0;
        $this->action = '';
        $this->old_values = '';
        $this->new_values = '';
        parent::__construct();
    }

    /**
     * @param integer $user_id The user who is making the change
     * @param integer $target_id The user who is being changed
     * @param string $action
     * @param Role|null $old_role
     * @param Role|null $new_role
     */
    public function set($user_id, $target_id, $action, $old_role, $new_role)
    {
        $this->created_on = time();
        $this->user_id = $user_id;
        $this->target_id = $target_id;
        $this->action = $action;
        $this->old_values = $old_role ? $old_role->toJSON() : '';
        $this->new_values = $new_role ? $new_role->toJSON() : '';
    }

    /**
     * Log the creation of a new User
     * @param $user
     */
    static public function logNewUser($user)
    {
        $l = new Log();
        $l->created_on = time();
        $l->user_id = $user->id;
        $l->target_id = $user->id;
        $l->action = self::kCreation;
        $l->old_values = '';
        $l->new_values = $user->toJSON();
        $l->insert();
    }

    /**
     * Log the import of VB properties
     * @param $user
     */
    static public function logVBImport($user)
    {
        $l = new Log();
        $l->created_on = time();
        $l->user_id = $user->id;
        $l->target_id = $user->id;
        $l->action = self::kVBImport;
        $l->old_values = '';
        $l->new_values = '{"vb_id":' . intval($user->vb_id) . '}';
        $l->insert();
    }

    /**
     * Log a Role addition
     * @param integer $target_id The user who is being changed
     * @param Role $new_role
     */
    static public function logAddition($target_id, Role $new_role)
    {
        $u = User::getConnectedUser();
        $user_id = $u ? $u->id : 0;
        $l = new Log();
        $l->set($user_id, $target_id, self::kAddition, null, $new_role);
        $l->insert();
    }

    /**
     * Log a Role deletion
     * @param integer $target_id The user who is being changed
     * @param Role|bool $old_role
     */
    static public function logDeletion($target_id, $old_role)
    {
        if (!$old_role) return;
        $u = User::getConnectedUser();
        $user_id = $u ? $u->id : 0;
        $l = new Log();
        $l->set($user_id, $target_id, self::kDeletion, $old_role, null);
        $l->insert();
    }

    /**
     * Log a Role change
     * @param integer $target_id The user who is being changed
     * @param Role $old_role
     * @param Role $new_role
     */
    static public function logChange($target_id, Role $old_role, Role $new_role)
    {
        $u = User::getConnectedUser();
        $user_id = $u ? $u->id : 0;
        $l = new Log();
        $l->set($user_id, $target_id, self::kChange, $old_role, $new_role);
        $l->insert();
    }

    /**
     * Log an Adhesion
     * @param integer $target_id The user who paid
     * @param $year
     * @param $amount
     */
    static public function logAdhesion($target_id, $year, $amount)
    {
        $l = new Log();
        $l->created_on = time();
        $l->user_id = $target_id;
        $l->target_id = $target_id;
        $l->action = self::kAdhesion;
        $l->old_values = '';
        $l->new_values = '{"year":' . intval($year) . ',"amount":' . $amount .  '}';
        $l->insert();
    }

    /**
     * Log a comment change
     * @param $user_id
     * @param integer $target_id The user whose comment wad changed
     * @param $old_length
     * @param $new_length
     */
    static public function logCommented($user_id, $target_id, $old_length, $new_length)
    {
        $l = new Log();
        $l->created_on = time();
        $l->user_id = $user_id;
        $l->target_id = $target_id;
        $l->action = self::kCommented;
        $l->old_values = '{"length":' . intval($old_length) . '}';
        $l->new_values = '{"length":' . intval($new_length) . '}';
        $l->insert();
    }

    /**
     * Log the degradation of officiers when archiving a Section
     * @param $tag
     */
    static public function logDegradeOfficiers($tag)
    {
        $l = new Log();
        $l->created_on = time();
        $u = User::getConnectedUser();
        $l->user_id = $u ? $u->id : 0;;
        $l->target_id = 0;
        $l->action = self::kDegrade;
        $l->old_values = '{"section": "' . $tag . '"}';
        $l->new_values = '{"section": "' . $tag . '"}';
        $l->insert();
    }

    static public function logExport($params)
    {
        $l = new Log();
        $l->created_on = time();
        $u = User::getConnectedUser();
        $l->user_id = $u ? $u->id : 0;;
        $l->target_id = 0;
        $l->action = self::kExport;
        $l->old_values = '';
        $l->new_values = json_encode($params);
        $l->insert();

    }
}
