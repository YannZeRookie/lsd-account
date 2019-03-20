<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 29/12/2018
 * Time: 18:16
 */

require_once __DIR__ . '/LsdActiveRecord.php';
require_once __DIR__ . '/../libs/Discord.php';

class Role extends LsdActiveRecord
{
    public $table = 'lsd_roles';

    const kVisiteur = 'visiteur';
    const kInvite = 'invite';
    const kScorpion = 'scorpion';
    const kMembre = 'membre';           // Member of a Section
    const kOfficier = 'officier';       // Officer of a Section
    const kConseiller = 'conseiller';
    const kSecretaire = 'secretaire';
    const kTresorier = 'tresorier';
    const kPresident = 'president';
    const kAdmin = 'admin';
    const kCM = 'cm';
    const kAdherant = 'adherant';

    /**
     * Check the user's roles in Discord and create them in the database.
     * This is a convenience to set a user to his proper roles without
     * requiring him to apply again.
     * Note that any previous roles will be erased and replaced by the ones from Discord
     * @param $discord_id integer
     */
    static public function importDiscordRole($user_id, $discord_id)
    {
        $user_info = Discord::discord_get_user_roles($discord_id);
        if ($user_info) {
            // Clear all previous roles for this user, if any
            self::execute("DELETE FROM lsd_roles WHERE user_id = ?", [$user_id]);
            // Now import the roles that make sense
            foreach ($user_info->roles as $role) {
                $r = new Role;
                $r->user_id = $user_id;
                $r->role = self::discord_to_LSD_role($role->name);
                if($r->role) {
                    $r->insert();
                }
            }
        }
    }

    /**
     * Convert a Discord role into a LSD role
     * We import only roles that make sense (for example, we don't import "Invité" or "Bureau"
     * @param $discord_role string
     * @return string
     */
    static public function discord_to_LSD_role($discord_role)
    {
        switch ($discord_role) {
            case 'Scorpion':    return self::kScorpion;
            case 'Officier':    return self::kOfficier;
            case 'Admin':       return self::kAdmin;
        }
        return false;
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
