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
                    $value = '';
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
        if ($this->discord_avatar) {
            if (preg_match('/$a_/', $this->discord_avatar)) {
                return "https://cdn.discordapp.com/avatars/{$this->discord_id}/{$this->discord_avatar}.gif";
            } else {
                return "https://cdn.discordapp.com/avatars/{$this->discord_id}/{$this->discord_avatar}.png";
            }
        } else {
            return '';
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
    public function canManageSections()
    {
        return Role::canManageSections($this->id);
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
     * If yes, return Role::kMembre or Role::kOfficier
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
            if ($belongs == Role::kMembre) {
                $res[] = $section->tag;
            } elseif ($belongs == Role::kOfficier) {
                $res[] = $section->tag . '*';
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
     * @param $oldRole string previous role for this Section - if any
     */
    public function setSectionMembership($tag, $isMembre, $isOfficier, $oldRole = null)
    {
        Role::setSectionMembership($this->id, $tag, $isMembre, $isOfficier, $oldRole);
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
}
