<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 29/12/2018
 * Time: 11:32
 */

require_once __DIR__ . '/LsdActiveRecord.php';
require_once __DIR__ . '/Role.php';

class User extends LsdActiveRecord {
    public $table = 'lsd_users';

    /**
     * Build the url of the user's Discord avatar
     * @return string
     */
    public function avatar() {
        if (preg_match('/$a_/', $this->discord_avatar)) {
            return "https://cdn.discordapp.com/avatars/{$this->discord_id}/{$this->discord_avatar}.gif";
        } else {
            return "https://cdn.discordapp.com/avatars/{$this->discord_id}/{$this->discord_avatar}.png";
        }
    }

    /**
     * Is the user a Scorpion?
     */
    public function isScorpion()
    {
        return Role::isScorpion($this->id);
    }
}
