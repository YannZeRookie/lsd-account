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
     * Return the Roles as a sorted list: key => name
     * @param bool $all TRUE if you want to get the non-leveled ranks: kMembre, kCM, kAdherant...
     * @return array
     */
    static public function getRolesTable($main = true, $bureau = false, $others = false)
    {
        $result = [];
        if ($main) {
            $result[self::kVisiteur] = ['role' => self::kVisiteur, 'name' => 'Visiteur', 'level' => self::getRoleLevel(self::kVisiteur)];
            $result[self::kInvite] = ['role' => self::kInvite, 'name' => 'Invité', 'level' => self::getRoleLevel(self::kInvite)];
            $result[self::kScorpion] = ['role' => self::kScorpion, 'name' => 'Scorpion', 'level' => self::getRoleLevel(self::kScorpion)];
            $result[self::kOfficier] = ['role' => self::kOfficier, 'name' => 'Officier', 'level' => self::getRoleLevel(self::kOfficier)];
            $result[self::kConseiller] = ['role' => self::kConseiller, 'name' => 'Conseiller', 'level' => self::getRoleLevel(self::kConseiller)];
            $result[self::kAdmin] = ['role' => self::kAdmin, 'name' => 'Admin', 'level' => self::getRoleLevel(self::kAdmin)];
        }
        if ($bureau) {
            $result[self::kSecretaire] = ['role' => self::kSecretaire, 'name' => 'Secrétaire', 'level' => self::getRoleLevel(self::kSecretaire)];
            $result[self::kTresorier] = ['role' => self::kTresorier, 'name' => 'Trésorier', 'level' => self::getRoleLevel(self::kTresorier)];
            $result[self::kPresident] = ['role' => self::kPresident, 'name' => 'Président', 'level' => self::getRoleLevel(self::kPresident)];
        }
        if ($others) {
            $result[self::kMembre] = ['role' => self::kMembre, 'name' => 'Membre', 'level' => self::getRoleLevel(self::kMembre)];
            $result[self::kCM] = ['role' => self::kCM, 'name' => 'Gestionnaire de Communauté', 'level' => self::getRoleLevel(self::kCM)];
            $result[self::kAdherant] = ['role' => self::kAdherant, 'name' => 'Adhérant', 'level' => self::getRoleLevel(self::kAdherant)];
        }
        return $result;
    }

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
     * We import only roles that make sense (for example, we don't import "Officier" (no section attached) or "Bureau"
     * @param $discord_role string
     * @return string
     */
    static public function discordToLsdRole($discord_role)
    {
        switch ($discord_role) {
            case 'Visiteur':
                return self::kVisiteur;
            case 'Invité':
                return self::kInvite;
            case 'Scorpion':
                return self::kScorpion;
            case 'Conseiller':
                return self::kConseiller;
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
     * Is the user a Gestionnaire de communaute?
     * @param $user_id
     * @return bool
     */
    static public function isCM($user_id)
    {
        return self::hasRole($user_id, self::kCM);
    }

    /**
     * Generic Role query method
     * @param $user_id
     * @param $role
     * @param $data
     * @return bool
     */
    static public function hasRole($user_id, $role, $data = null)
    {
        $roles = new Role;
        if ($data) {
            if ($role == self::kMembre && $data == 'JDM') {
                return self::isScorpion();  // Each Scorpion is automatically member of the JDM Section
            } else {
                return $roles->equal('user_id', $user_id)->equal('role', $role)->equal('data', $data)->find() !== false;
            }
        } else {
            return $roles->equal('user_id', $user_id)->equal('role', $role)->find() !== false;
        }
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

    /**
     * Grant a new Role to this user
     * @param $user_id
     * @param $newRole
     * @param $data optional extra data
     */
    static public function setRole($user_id, $newRole, $data = null)
    {
        if (empty($newRole)) {
            return;
        }
        $role = new Role;
        //-- Do we already have this role?
        if (self::hasRole($user_id, $newRole, $data)) {
            return;
        }
        //-- Officier, Membre and Adherant should have some data
        if (($newRole == self::kOfficier || $newRole == self::kMembre || $newRole == self::kAdherant) && empty($data)) {
            return;
        }

        //-- Make some clean-up and data consistency control in the case of low-level setting
        if ($newRole == self::kVisiteur || $newRole == self::kInvite || $newRole == self::kScorpion) {
            $removal = [self::kVisiteur, self::kInvite, self::kScorpion]; // Because these are exclusive
            self::removeRoles($user_id, $removal);
        }
        //-- Add it
        $role->user_id = $user_id;
        $role->role = $newRole;
        $role->data['data'] = $role->dirty['data'] = $data;
        $role->insert();
    }

    /**
     * Remove some roles from this user
     * Note that this won't do if you need to specify some additional data. See Role::removeRole() for this.
     * @param $user_id
     * @param array $roles
     */
    static public function removeRoles($user_id, $roles = [])
    {
        if (count($roles)) {
            $roles_sql = "'" . implode("', '", $roles) . "'";
            self::execute("DELETE FROM lsd_roles WHERE user_id = ? AND role in ({$roles_sql})", [$user_id]);
        }
    }

    /**
     * Remove a Role from this user
     * @param $user_id
     * @param $role
     * @param null $data
     */
    static public function removeRole($user_id, $role, $data = null)
    {
        if ($data) {
            self::execute("DELETE FROM lsd_roles WHERE user_id = ? AND role = ? AND data = ?", [$user_id, $role, $data]);
        } else {
            self::execute("DELETE FROM lsd_roles WHERE user_id = ? AND role = ? ", [$user_id, $role]);
        }
    }

    /**
     * Get a level integer from a Role. This is used to sort Roles privileges
     * @param $role
     * @return bool|int
     */
    static public function getRoleLevel($role)
    {
        switch ($role) {
            case self::kVisiteur:
                return 1;
            case self::kInvite:
                return 2;
            case self::kScorpion:
                return 3;
            case self::kOfficier:
                return 4;
            case self::kConseiller:
                return 5;
            case self::kAdmin:
                return 90;
        }
        return false;   // This role has no level
    }

    /**
     * Get the highest Role of a user
     * @param $user_id
     * @return mixed|string
     */
    static public function getHighestRole($user_id)
    {
        $user_roles = self::getRoles($user_id);
        $result = self::kVisiteur;
        $result_level = self::getRoleLevel($result);
        foreach ($user_roles as $role) {
            $level = self::getRoleLevel($role);
            if ($level > $result_level) {
                $result = $role;
                $result_level = $level;
            }
        }
        return $result;
    }


    /**
     * Does the user belong to a Section?
     * If yes, return Role::kMembre or Role::kOfficier
     * If no, return false
     * @param $user_id integer user id
     * @param $tag string Section tag
     * @return mixed
     */
    static public function belongsToSection($user_id, $tag)
    {
        $r = new Role;
        $role = $r->equal('user_id', $user_id)->in('role', [self::kMembre, self::kOfficier])->equal('data', $tag)->find();
        if ($tag == 'JDM' && !$role && self::isScorpion($user_id)) {
            return self::kMembre;   // All Scorpions are automatically Membre if they are not already Officier of JDM
        }
        return $role ? $role->role : false;
    }

    /**
     * Set (or reset) membership of a user to a Section
     * Note that $isOfficier takes precedence on $isMembre
     * @param $user_id integer user id
     * @param $tag string Section tag
     * @param $isMembre bool
     * @param $isOfficier bool|null If null, it means 'don't do anything'
     * @param $oldRole string previous role for this Section - if any
     */
    static public function setSectionMembership($user_id, $tag, $isMembre, $isOfficier, $oldRole = null)
    {
        if ($oldRole === null) {
            $oldRole = self::belongsToSection($user_id, $tag);
        }
        if ($isMembre && $isOfficier) {
            $isMembre = false;
        }
        if ($isMembre) {
            if ($oldRole != self::kMembre && $tag != 'JDM') {   // No use to set it for JDM
                $rm = new Role;
                $rm->user_id = $user_id;
                $rm->role = self::kMembre;
                $rm->data['data'] = $rm->dirty['data'] = $tag;  // Hmmm. It was not a good idea to have a column named 'data' in the table. It makes ActiveRecord screw up
                $rm->insert();
            }
        } else {
            if ($oldRole == self::kMembre) {
                self::execute("DELETE FROM lsd_roles WHERE user_id = ? AND role = ? AND data = ? ", [$user_id, self::kMembre, $tag]);
            }
        }
        if ($isOfficier !== null) {
            if ($isOfficier) {
                if ($oldRole != self::kOfficier) {
                    $ro = new Role;
                    $ro->user_id = $user_id;
                    $ro->role = self::kOfficier;
                    $ro->data['data'] = $ro->dirty['data'] = $tag;
                    $ro->insert();
                }
            } else {
                if ($oldRole == self::kOfficier) {
                    self::execute("DELETE FROM lsd_roles WHERE user_id = ? AND role = ? AND data = ? ", [$user_id, self::kOfficier, $tag]);
                }
            }
        }
    }
    
    /**
     * Can a user assign members to Sections?
     * @param $user_id
     * @return bool
     */
    static public function canNameMembres($user_id)
    {
        return Role::hasAnyRole($user_id, [Role::kOfficier, Role::kConseiller, Role::kSecretaire, Role::kTresorier, Role::kPresident, Role::kAdmin]);
    }

    /**
     * Can a user name officers?
     * @param $user_id
     */
    static public function canNameOfficiers($user_id)
    {
        return Role::hasAnyRole($user_id, [Role::kConseiller, Role::kSecretaire, Role::kTresorier, Role::kPresident, Role::kAdmin]);
    }

    /**
     * Can a user name CMs and Adherents
     * @param $user_id
     * @return bool
     */
    static public function canSetOtherRoles($user_id)
    {
        return Role::hasAnyRole($user_id, [Role::kConseiller, Role::kSecretaire, Role::kTresorier, Role::kPresident, Role::kAdmin]);
    }

    /**
     * Is user Adherent for a specific year?
     * @param $user_id
     * @param $year integer in YYYY format
     * @return bool
     */
    static public function isAdherent($user_id, $year)
    {
        return self::hasRole($user_id, self::kAdherant, $year);
    }
}
