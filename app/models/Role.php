<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 29/12/2018
 * Time: 18:16
 */

require_once __DIR__ . '/LsdActiveRecord.php';

class Role extends LsdActiveRecord
{
    public $table = 'lsd_roles';

    const kVisiteur = 'visiteur';
    const kInvite = 'invite';
    const kScorpion = 'scorpion';
    const kMembre = 'membre';
    const kOfficier = 'officier';
    const kConseiller = 'conseiller';
    const kSecretaire = 'secretaire';
    const kTresorier = 'tresorier';
    const kPresident = 'president';
    const kAdmin = 'admin';
    const kCM = 'cm';
    const kAdherant = 'adherant';

    /**
     * Check the user's role in Discord and create it in the database.
     * This is a convenience to set a user to his proper role without
     * requiring him to apply again.
     * @param $discord_id integer
     */
    static public function importDiscordRole($discord_id)
    {

    }

    /**
     * Is the user a Scorpion?
     * @param $user_id
     * @return mixed The Role if it was found, false otherwise
     */
    static public function isScorpion($user_id)
    {
        return self::hasRole($user_id, self::kScorpion);
    }

    /**
     * Generic Role query method
     * @param $user_id
     * @param $role
     * @return mixed The Role if it was found, false otherwise
     */
    static public function hasRole($user_id, $role)
    {
        $roles = new Role;
        return $roles->equal('user_id', $user_id)->equal('role', $role)->find();
    }
}
