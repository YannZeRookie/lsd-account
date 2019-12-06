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
     * magic function to GET the values of current object.
     * Address some fields when they are null
     */
    public function & __get($var)
    {
        $value = parent::__get($var);
        if ($value === null) {
            switch ($var) {
                case 'email':
                case 'testimony':
                case 'vb_username':
                    $value = '';
                    break;
                case 'vb_id':
                    $value = 0;
                    break;
            }
        }
        return $value;
    }


    /**
     * Check the data and return errors if any found.
     * Use before saving to database.
     * @return array List of: 'Human readable field name' => 'Human readable error message'
     */
    public function validate()
    {
        $errors = [];
        if ($this->email && filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['E-mail'] = 'format invalide';
        }
        return $errors;
    }

    /**
     * Grant a new Role to this user
     * @param $newRole
     * @param $extra optional extra data
     */
    public function setRole($newRole, $extra = null)
    {
        Role::setRole($this->id, $newRole, $extra);
    }

    /**
     * Remove a Role from this user
     * @param $role
     * @param null $extra
     */
    public function removeRole($role, $extra = null)
    {
        Role::removeRole($this->id, $role, $extra);
    }

    /**
     * Toggle a Role on or off
     * @param $role
     * @param $turnOn
     * @param null $extra
     */
    public function toggleRole($role, $turnOn, $extra = null)
    {
        if ($turnOn) {
            $this->setRole($role, $extra);
        } else {
            $this->removeRole($role, $extra);
        }
    }

    /**
     * Set a Bureau role
     * @param $newRole
     */
    public function setBureauRole($newRole)
    {
        if ($newRole && $this->hasRole($newRole)) {
            return;
        }
        Role::removeRoles($this->id, [Role::kSecretaire, Role::kTresorier, Role::kPresident]);
        if ($newRole) {
            Role::setRole($this->id, $newRole);
        }
    }

    /**
     * Build the url of the user's Discord avatar
     * @return string
     */
    public function avatar()
    {
        return self::buildAvatar($this->discord_id, $this->discord_avatar);
    }

    /**
     * Build the url of the user's Discord avatar - lower level
     * @param $discord_id
     * @param $discord_avatar
     * @return string
     */
    static public function buildAvatar($discord_id, $discord_avatar)
    {
        if ($discord_avatar) {
            if (preg_match('/$a_/', $discord_avatar)) {
                return "https://cdn.discordapp.com/avatars/{$discord_id}/{$discord_avatar}.gif";
            } else {
                return "https://cdn.discordapp.com/avatars/{$discord_id}/{$discord_avatar}.png";
            }
        } else {
            return '/img/blank_avatar.png';
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
     * Is the user the President?
     * @return bool
     */
    public function isPresident()
    {
        return Role::isPresident($this->id);
    }

    /**
     * Is the user a CM?
     * @return bool
     */
    public function isCM()
    {
        return Role::isCM($this->id);
    }

    /**
     * Has the user this role?
     * @param $role
     * @return bool
     */
    public function hasRole($role)
    {
        return Role::hasRole($this->id, $role);
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
     * Can the user manage Sections?
     * @return mixed
     */
    public function canSeeSections()
    {
        return Role::canSeeSections($this->id);
    }

    /**
     * Can the user see adhesions ?
     * @return mixed
     */
    public function canSeeAdhesions()
    {
        return Role::hasAnyRole($this->id, [Role::kConseiller, Role::kSecretaire, Role::kTresorier, Role::kPresident, Role::kAdmin]);
    }

    /**
     * Can the user see adhesions details?
     * @return mixed
     */
    public function canSeeAdhesionsDetails()
    {
        return Role::hasAnyRole($this->id, [Role::kTresorier, Role::kPresident, Role::kAdmin]);
    }

    /**
     * Can the user see Logs ?
     * @return mixed
     */
    public function canSeeLogs()
    {
        return Role::hasAnyRole($this->id, [Role::kConseiller, Role::kSecretaire, Role::kTresorier, Role::kPresident, Role::kAdmin]);
    }

    /**
     * Get this list of roles as a comma-separated string
     * @return string
     */
    public function roles()
    {
        $roles_names = Role::getRolesNames($this->id);
        unset($roles_names[Role::kMembre]); // No use to keep this one
        return implode(', ', $roles_names);
    }

    /**
     * Does the user belong to a Section?
     * If yes, return Role
     * If no, return false
     * @param $tag string Section tag
     * @return mixed
     */
    public function belongsToSection($tag)
    {
        return Role::belongsToSection($this->id, $tag);
    }

    /**
     * Get the user's sections as a comma-separated list
     * @return string
     */
    public function sections()
    {
        $res = [];
        $s = new Section;
        $sections = $s->equal('archived', 0)->order('`order`')->findAll();
        foreach ($sections as $section) {
            $belongs = $this->belongsToSection($section->tag);
            if ($belongs) {
                if ($belongs->role == Role::kOfficier) {
                    $res[] = $section->tag . '*';
                } elseif ($belongs->role == Role::kMembre) {
                    $res[] = $section->tag;
                }
            }
        }
        return implode(', ', $res);
    }

    /**
     * Set (or reset) membership of a user to a Section
     * Note that $isOfficier takes precedence on $isMembre
     * @param $tag string Section tag
     * @param $isMembre bool
     * @param $isOfficier bool|null If null, it means 'don't do anything'
     * @param $sectionPseudo string Specific Pseudo if any
     */
    public function setSectionMembership($tag, $isMembre, $isOfficier, $sectionPseudo='')
    {
        Role::setSectionMembership($this->id, $tag, $isMembre, $isOfficier, $sectionPseudo);
    }

    /**
     * Get the Section membership of a user. Return the found Role if any
     * @param $tag
     * @return false|Role
     */
    public function getSectionMembership($tag)
    {
        return Role::getSectionMembership($this->id, $tag);
    }

    /**
     * Set the Pseudo for a Section
     * @param $tag string Section tag
     * @param $sectionPseudo string Specific Pseudo
     */
    public function setPseudo($tag, $sectionPseudo)
    {
        $role = Role::findRole($this->id, Role::kMembre, $tag);
        if ($role && $role->extra2 != $sectionPseudo) {
            $old_role = clone $role;
            $role->extra2 = $sectionPseudo ?: null;
            $role->save();
            Log::logChange($this->id, $old_role, $role);
            return;
        }
        $role = Role::findRole($this->id, Role::kOfficier, $tag);
        if ($role && $role->extra2 != $sectionPseudo) {
            $old_role = clone $role;
            $role->extra2 = $sectionPseudo ?: null;
            $role->save();
            Log::logChange($this->id, $old_role, $role);
            return;
        }
    }

    public function RemoveFromAllSections()
    {
        Role::removeRoles($this->id, [Role::kMembre, Role::kOfficier]);
    }

    /**
     * Can the user review candidates?
     * @return bool
     */
    public function canReviewUsers()
    {
        return Role::canReviewUsers($this->id);
    }


    /**
     * Get the VB login names - if there was an original VB account
     * @return string
     */
    public function vb()
    {
        if ($this->vb_id) {
            return '';  // TODO
        } else {
            return '';
        }
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

    public function toJSON()
    {
        $data =  [
            'id' => $this->id,
            'discord_username' => $this->discord_username,
            ];
        return json_encode($data, JSON_NUMERIC_CHECK);
    }

    public function getLastAdhesion()
    {
        $a = new Adhesion();
        return $a->equal('user_id', $this->id)->orderby('id desc')->limit(0,1)->find();
    }

}