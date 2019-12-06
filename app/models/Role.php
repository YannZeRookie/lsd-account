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
    const kAdherent = 'adherent';

    function __toString()
    {
        return '' . print_r($this, true);    // For debug
    }

    public function & __get($var)
    {
        switch ($var) {
            case 'extra':
            case 'extra2':
                $result = isset($this->data[$var]) ? $this->data[$var] : '';    // Otherwise Twig will screw up if fed with null
                return $result;
            default:
                return parent::__get($var);
        }
    }

    /**
     * Return the Roles as a sorted list: key => name
     * @param bool $all TRUE if you want to get the non-leveled ranks: kMembre, kCM, kAdherent...
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
            $result[self::kAdherent] = ['role' => self::kAdherent, 'name' => 'Adhérent', 'level' => self::getRoleLevel(self::kAdherent)];
        }
        return $result;
    }

    /**
     * Return the basic role of a user, i.e. *either* self::kVisiteur, self::kInvite or self::kScorpion
     * Note these are exclusive
     * @param $user_id
     * @return Role|bool
     */
    static public function getBasicRole($user_id)
    {
        $r = new Role;
        return $r->equal('user_id', $user_id)->in('role', [self::kVisiteur, self::kInvite, self::kScorpion])->find();
    }

    /**
     * If this a basic role?
     * @param string $role
     * @return bool
     */
    static public function isBasicRole($role)
    {
        return $role == self::kVisiteur || $role == self::kInvite || $role == self::kScorpion;
    }

    /**
     * Get the list of Officier roles (if any)
     * @param $user_id
     * @return Role|bool
     */
    static public function getOfficierRoles($user_id)
    {
        $r = new Role;
        return $r->equal('user_id', $user_id)->equal('role', self::kOfficier)->findAll();

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
                $new_role = self::discordToLsdRole($role->name);
                if ($new_role && $new_role != self::kMembre && $new_role != self::kOfficier) {  // we cannot import kMembre and kOfficier because we don't know the Section
                    self::setRole($user_id, $new_role);
                }
            }
        }
    }

    /**
     * Convert a Discord role into a LSD role
     * We import only roles that make sense (for example, we don't import "Officier" (no section attached) or "Bureau"
     * @param string $discord_role
     * @return bool|string
     */
    static public function discordToLsdRole($discord_role)
    {
        switch ($discord_role) {
            case 'Invité':
                return self::kInvite;
            case 'Scorpion':
                return self::kScorpion;
            case 'Officier':
                return self::kOfficier;
            case 'Conseiller':
                return self::kConseiller;
            case 'Admin':
                return self::kAdmin;
        }
        return false;
    }

    /**
     * Convert a Role into a Discord role
     * @return bool|string
     */
    static public function lsdToDiscordRole($role, $extra=null)
    {
        switch($role) {
            case self::kInvite:
                return 'Invité';
            case self::kScorpion:
                return 'Scorpion';
            case self::kOfficier:
                return 'Officier';
            case self::kConseiller:
                return 'Conseiller';
            case self::kSecretaire:
            case self::kTresorier:
            case self::kPresident:
                return 'Bureau';
            case self::kAdmin:
                return 'Admin';
            case self::kMembre:
            case self::kOfficier:
                // Special roles for specific Sections
                if ($extra && $extra == 'DU') {
                    return 'Dual-Universe';
                }
                break;
        }
        return false;
    }

    /**
     * Convert a list of VB roles for a user
     * @param array $vb_groups
     * @param $user_id
     */
    static public function importVBRoles(Array $vb_groups, $user_id)
    {
        foreach ($vb_groups as $vb_group) {
            switch ($vb_group) {
                case '17': // Scorpions
                    self::setRole($user_id, Role::kScorpion);
                    break;
                case '19': // Conseiller
                    self::setRole($user_id, Role::kConseiller);
                    break;
                case '30': // Gestionnaire de communauté
                    self::setRole($user_id, Role::kCM);
                    break;
                default:
                    // Look in Sections
                    $s = Section::findByVBGroup($vb_group);
                    if ($s) {
                        self::setSectionMembership($user_id, $s['tag'], $s['membre'], $s['officier']);
                    }
            }
        }
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
     * Is the user the President?
     * @param $user_id
     * @return bool
     */
    static public function isPresident($user_id)
    {
        return self::hasAnyRole($user_id, [self::kPresident]);
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
     * Generic Role test method
     * @param $user_id
     * @param $role
     * @param $extra
     * @return bool
     */
    static public function hasRole($user_id, $role, $extra = null)
    {
        return self::findRole($user_id, $role, $extra) !== false;
    }

    /**
     * Generic Role query method
     * @param $user_id
     * @param $role string
     * @param $extra
     * @return Role|false
     */
    static public function findRole($user_id, $role, $extra = null)
    {
        $roles = new Role;
        if ($extra) {
            return $roles->equal('user_id', $user_id)->equal('role', $role)->equal('extra', $extra)->find();
        } else {
            return $roles->equal('user_id', $user_id)->equal('role', $role)->find();
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
     * Return a simple list of the roles names of a user
     * @param $user_id
     * @return array of Role Names
     */
    static public function getRolesNames($user_id)
    {
        $res = [];
        $rr = new Role;
        $roles = $rr->equal('user_id', $user_id)->findAll();
        $roles_table = self::getRolesTable(true, true, true);
        foreach ($roles as $role) {
            $r = ($role->role == self::kAdherent) ? (self::kAdherent . '_' . $role->extra) : $role->role;
            $res[$r] = $roles_table[$role->role]['name'] . (($role->role == self::kAdherent) ? ' ' . $role->extra : '');    // This will also de-duplicate the list
        }
        return $res;
    }

    /**
     * Grant a new Role to this user
     * @param $user_id integer
     * @param $newRole string
     * @param $extra string optional extra data (like the Section tag)
     * @param $extra2 string optional other extra data (like the Section pseudo)
     */
    static public function setRole($user_id, $newRole, $extra = null, $extra2 = null)
    {
        if (empty($newRole)) {
            return;
        }
        $role = new Role;
        //-- Do we already have this role?
        $prev_role = self::findRole($user_id, $newRole, $extra);
        if ($prev_role !== false) {
            // Just update the extra2 field if it changed
            if ($prev_role->extra2 != $extra2) {
                $old_role = clone $prev_role;
                $prev_role->extra2 = $extra2;
                $prev_role->save();
                Log::logChange($user_id, $old_role, $prev_role);
            }
            return;
        }
        //-- Officier, Membre and Adherent should have some data
        if (($newRole == self::kOfficier || $newRole == self::kMembre || $newRole == self::kAdherent) && empty($extra)) {
            return;
        }

        //-- Make some clean-up and data consistency control in the case of low-level setting
        if ($newRole == self::kVisiteur || $newRole == self::kInvite || $newRole == self::kScorpion) {
            $removal = [self::kVisiteur, self::kInvite, self::kScorpion]; // Because these are exclusive
            self::removeRoles($user_id, $removal);
            //-- If we set the user to Scorpion, make him part of the JDM section automatically
            if ($newRole == self::kScorpion) {
                self::setRole($user_id, self::kMembre, 'JDM');
            } else {
                self::removeRole($user_id, self::kMembre, 'JDM');
                self::removeRole($user_id, self::kOfficier, 'JDM');
            }
        }
        if ($newRole == self::kOfficier || $newRole == self::kMembre) {
            self::removeRole($user_id, ($newRole == self::kOfficier ? self::kMembre : self::kOfficier), $extra);    // Because self::kOfficier and self::kMembre are exclusive
        }

        //-- Add it
        $role->user_id = $user_id;
        $role->role = $newRole;
        $role->extra = $extra;
        $role->extra2 = $extra2;
        $role->insert();
        Log::logAddition($user_id, $role);
    }

    /**
     * Remove some roles from this user
     * Note that this won't do if you need to specify some additional data. See Role::removeRole() for this.
     * @param $user_id
     * @param array [string] $roles
     */
    static public function removeRoles($user_id, $roles = [])
    {
        if (count($roles)) {
            $r = new Role;
            $r_list = $r->equal('user_id', $user_id)->in('role', $roles)->findAll();
            foreach ($r_list as $old_role) {
                Log::logDeletion($user_id, $old_role);
            }
            $roles_sql = "'" . implode("', '", $roles) . "'";
            self::execute("DELETE FROM lsd_roles WHERE user_id = ? AND role in ({$roles_sql})", [$user_id]);
        }
    }

    /**
     * Remove a Role from this user
     * @param $user_id
     * @param $role
     * @param null $extra
     */
    static public function removeRole($user_id, $role, $extra = null)
    {
        $old_role = self::findRole($user_id, $role, $extra);
        Log::logDeletion($user_id, $old_role);
        if ($extra) {
            self::execute("DELETE FROM lsd_roles WHERE user_id = ? AND role = ? AND extra = ?", [$user_id, $role, $extra]);
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
     * If yes, return Role
     * If no, return false
     * @param $user_id integer user id
     * @param $tag string Section tag
     * @return mixed
     */
    static public function belongsToSection($user_id, $tag)
    {
        $r = new Role;
        $role = $r->equal('user_id', $user_id)->in('role', [self::kMembre, self::kOfficier])->equal('extra', $tag)->find();
        return $role ? $role : false;
    }

    /**
     * Set (or reset) membership of a user to a Section
     * Note that $isOfficier takes precedence on $isMembre
     * @param $user_id integer user id
     * @param $tag string Section tag
     * @param $isMembre bool
     * @param $isOfficier bool|null If null, it means 'don't do anything'
     * @param $sectionPseudo string Specific Pseudo if any
     */
    static public function setSectionMembership($user_id, $tag, $isMembre, $isOfficier, $sectionPseudo='')
    {
        if ($isOfficier === null && self::hasRole($user_id, self::kOfficier, $tag))
        {
            return; // Can't touch this person: not enough privileges
        }
        if ($isMembre || $isOfficier) {
            self::setRole($user_id, ($isOfficier ? self::kOfficier : self::kMembre), $tag, empty($sectionPseudo) ? null : $sectionPseudo);
        } else {
            if (!($tag == 'JDM' && self::isScorpion($user_id))) {
                self::removeRole($user_id, self::kMembre, $tag);
                self::removeRole($user_id, self::kOfficier, $tag);
            }
        }
    }

    /**
     * Get the Section membership of a user. Return the found Role if any
     * @param $user_id
     * @param $tag
     * @return false|Role
     */
    static public function getSectionMembership($user_id, $tag)
    {
        $role = self::findRole($user_id, self::kOfficier, $tag);
        if (!$role) {
            $role = self::findRole($user_id, self::kMembre, $tag);
        }
        return $role;
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
     * Can a user see Sections?
     * @param $user_id
     * @return bool
     */
    static public function canSeeSections($user_id)
    {
        return Role::isScorpion($user_id);
    }

    /**
     * Can a user review candidates?
     * @param $user_id
     * @return bool
     */
    static public function canReviewUsers($user_id)
    {
        return Role::hasAnyRole($user_id, [Role::kOfficier, Role::kConseiller, Role::kSecretaire, Role::kTresorier, Role::kPresident, Role::kAdmin, Role::kCM]);
    }

    /**
     * Is user Adherent for a specific year?
     * @param $user_id
     * @param $year integer in YYYY format
     * @return bool
     */
    static public function isAdherent($user_id, $year)
    {
        return self::hasRole($user_id, self::kAdherent, $year);
    }

    /**
     * Convert the Role into a JSON string
     * @return string
     */
    public function toJSON()
    {
        $data = ['role' => $this->role];
        if (!empty($this->data['extra'])) {
            $data['extra'] = $this->data['extra'];
        }
        if (!empty($this->data['extra2'])) {
            $data['extra2'] = $this->data['extra2'];
        }
        return json_encode($data, JSON_NUMERIC_CHECK);
    }
}
