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
    const kSecretaire = 'secretaire';   // Bureau member
    const kTresorier = 'tresorier';     // Bureau member
    const kPresident = 'president';     // Bureau member
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
                $r->role = self::discordToLsdRole($role->name);
                if ($r->role) {
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
    static public function discordToLsdRole($discord_role)
    {
        switch ($discord_role) {
            case 'Scorpion':
                return self::kScorpion;
            case 'Officier':
                return self::kOfficier;
            case 'Admin':
                return self::kAdmin;
        }
        return false;
    }

    /**
     * Is the user a Scorpion?
     * @param $user_id
     * @return bool
     */
    static public function isScorpion($user_id)
    {
        return self::hasRole($user_id, self::kScorpion);
    }

    /**
     * Is the user an Officier?
     * @param $user_id
     * @return bool
     */
    static public function isOfficier($user_id)
    {
        return self::hasRole($user_id, self::kOfficier);
    }

    /**
     * Is the user an Admin?
     * @param $user_id
     * @return bool
     */
    static public function isAdmin($user_id)
    {
        return self::hasRole($user_id, self::kAdmin);
    }

    /**
     * Is the user a Conseiller?
     * @param $user_id
     * @return bool
     */
    static public function isConseiller($user_id)
    {
        return self::hasRole($user_id, self::kConseiller);
    }

    /**
     * Is the user a Bureau member?
     * @param $user_id
     * @return bool
     */
    static public function isBureau($user_id)
    {
        return self::hasAnyRole($user_id, [self::kSecretaire, self::kTresorier, self::kPresident]);
    }

    /**
     * Generic Role query method
     * @param $user_id
     * @param $role
     * @return bool
     */
    static public function hasRole($user_id, $role)
    {
        $roles = new Role;
        return $roles->equal('user_id', $user_id)->equal('role', $role)->find() !== false;
    }

    /**
     * Does the user have at least one role among the ones provided in the list?
     * @param $user_id
     * @param array $roles
     * @return bool
     */
    static public function hasAnyRole($user_id, $roles = [])
    {
        $user_roles = self::getRoles($user_id);
        return count(array_intersect($user_roles, $roles)) > 0;
    }

    /**
     * Return a simple list of the roles of a user
     * @param $user_id
     * @return array
     */
    static public function getRoles($user_id)
    {
        $r = new Role;
        $roles = $r->equal('user_id', $user_id)->findAll();
        $res = [];
        foreach ($roles as $role) {
            $res[] = $role->role;
        }
        return $res;
    }

}
