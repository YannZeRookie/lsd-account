<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 29/12/2018
 * Time: 11:32
 */

require_once __DIR__ . '/LsdActiveRecord.php';
require_once __DIR__ . '/Role.php';

class User extends LsdActiveRecord
{
    public $table = 'lsd_users';

    /**
     * Build the url of the user's Discord avatar
     * @return string
     */
    public function avatar()
    {
        if (preg_match('/$a_/', $this->discord_avatar)) {
            return "https://cdn.discordapp.com/avatars/{$this->discord_id}/{$this->discord_avatar}.gif";
        } else {
            return "https://cdn.discordapp.com/avatars/{$this->discord_id}/{$this->discord_avatar}.png";
        }
    }

    /**
     * Is the user a Scorpion?
     * @return bool
     */
    public function isScorpion()
    {
        return Role::isScorpion($this->id);
    }

    /**
     * Is the user an Officier?
     * @return bool
     */
    public function isOfficier()
    {
        return Role::isOfficier($this->id);
    }

    /**
     * Is the user an Admin?
     * @return bool
     */
    public function isAdmin()
    {
        return Role::isAdmin($this->id);
    }

    /**
     * Is the user a Conseiller?
     * @return bool
     */
    public function isConseiller()
    {
        return Role::isConseiller($this->id);
    }

    /**
     * Is the user a Bureau member?
     * @return bool
     */
    public function isBureau()
    {
        return Role::isBureau($this->id);
    }

    /**
     * Get the highest Role of this user
     * @return mixed|string
     */
    public function getHighestRole()
    {
        return Role::getHighestRole($this->id);
    }

    /**
     * Get the highest Role level (=an integer) of this user
     * Convenient to compare two users
     * @return mixed|integer
     */
    public function getHighestRoleLevel()
    {
        return Role::getRoleLevel($this->getHighestRole());
    }

    /**
     * Does the user belong to a Section?
     * If yes, return Role::kMembre or Role::kOfficier
     * If no, return false
     * @param $tag string Section tag
     * @return mixed
     */
    public function belongsToSection($tag)
    {
        return Role::userBelongsToSection($this->id, $tag);
    }

    /**
     * Get the currently connected user
     * @return ActiveRecord|bool
     */
    static public function getConnectedUser()
    {
        if (!empty($_SESSION['user_id'])) {
            $users = new User;
            return $users->find($_SESSION['user_id']);
        } else {
            return false;
        }
    }
}
