<?php

/**
 * Created by PhpStorm.
 * User: yann
 * Date: 21/03/2019
 * Time: 18:55
 *
 * CRUD of users
 */
class UsersController
{
    /**
     * View all (or a selection) users
     * @return array
     */
    static public function all()
    {
        //-- Check rights: the connected user can see the list of users only if he is an Officer, a Conseiller, a CM, a Bureau member or an Admin
        $cur_user = User::getConnectedUser();
        if (!$cur_user || !Role::hasAnyRole($cur_user->id, [Role::kOfficier, Role::kConseiller, Role::kSecretaire, Role::kTresorier, Role::kPresident, Role::kAdmin, Role::kCM])) {
            \Slim\Slim::getInstance()->redirect('/');
        }
        //--
        $u = new User;
        $users = $u->findAll();
        $debug = '';
        return [
            'users' => $users,
            'debug' => print_r($debug, true),
        ];
    }

    /**
     * Check if we can edit the user $id. If not, redirect to /
     * Also fills in a number of information about roles and rights.
     * @param $id                   Target user to edit
     * @return ActiveRecord|bool    Current connected user
     */
    static public function canEditUser($id)
    {
        $cur_user = User::getConnectedUser();
        if (!$cur_user || !(Role::hasAnyRole($cur_user->id, [Role::kOfficier, Role::kConseiller, Role::kSecretaire, Role::kTresorier, Role::kPresident, Role::kAdmin, Role::kCM]) || $cur_user->id == $id)) {
            \Slim\Slim::getInstance()->redirect('/');
        }
        $cur_user->_highest_role = Role::getHighestRole($cur_user->id);
        $cur_user->_highest_level = Role::getRoleLevel($cur_user->_highest_role);
        $cur_user->_canNameMembres = Role::canNameMembres($cur_user->id);
        $cur_user->_canNameOfficiers = Role::canNameOfficiers($cur_user->id);
        $cur_user->_canSetOtherRoles = Role::canSetOtherRoles($cur_user->id);
        return $cur_user;
    }

    /**
     * Get the target user or redirect to / is not found
     * Also fills in a number of information about roles and rights.
     * @param $id
     * @return User
     */
    static public function getTargetUser($id)
    {
        $u = new User;
        $user = $u->find($id);
        if (!$user) {
            \Slim\Slim::getInstance()->redirect('/');
        }
        $user->_highest_role = Role::getHighestRole($user->id);
        $user->_highest_level = Role::getRoleLevel($user->_highest_role);
        $years = self::buildYears();
        $user->_adherent_ly = Role::isAdherent($user->id, $years['last']);
        $user->_adherent_cy = Role::isAdherent($user->id, $years['current']);
        $user->_adherent_ny = Role::isAdherent($user->id, $years['next']);
        $user->_cm = Role::isCM($user->id);
        return $user;
    }

    /**
     * How/can the connected user change the e-mail?
     * @param $cur_user
     * @param $user
     * @return bool|string
     */
    static public function canEditEmail($cur_user, $user)
    {
        if ($cur_user->id == $user->id || $cur_user->isAdmin()) {
            return 'full';
        } elseif ($cur_user->isConseiller()) {
            return 'checkbox';
        } else {
            return false;
        }
    }

    /**
     * Build the Roles table, with some additional permission info
     * @param $cur_user
     * @param $user
     * @return array
     */
    static protected function buildRolesTable($cur_user, $user)
    {
        $canChangeRoles = ($cur_user->_highest_level > $user->_highest_level);  // You can change roles only for people under yourself
        $roles_table = Role::getRolesTable(true, false, false);
        foreach ($roles_table as $role => $role_data) {
            $roles_table[$role]['disabled'] = !$canChangeRoles || ($role_data['level'] >= $cur_user->_highest_level) || $role == Role::kOfficier;   // Officiers are set through the Section table
        }
        return $roles_table;
    }

    static protected function buildBureauTable($user)
    {
        $roles_table = Role::getRolesTable(false, true, false);
        foreach ($roles_table as $role => $role_data) {
            $roles_table[$role]['selected'] = $user->hasRole($role);
        }
        return $roles_table;
    }

    static protected function buildSectionsTable($user)
    {
        $s = new Section;
        $sections = $s->equal('archived', 0)->order('`order`')->findAll();
        foreach ($sections as &$section) {
            $section->_belong = $user->belongsToSection($section->tag);
        }
        return $sections;
    }

    /**
     * Build the last, current and next years
     */
    static protected function buildYears()
    {
        $current_year = intval(date('Y'));
        return ['last' => $current_year - 1, 'current' => $current_year, 'next' => $current_year + 1];
    }

    /**
     * View a specific user
     * @param $id
     * @return array
     */
    static public function view($id)
    {
        //-- Check rights: the connected user can see the list of users only if he is an Officer, a Conseiller, a CM, a Bureau member or an Admin,
        //   or if he is looking at its own record
        $cur_user = self::canEditUser($id);
        //-- Get edited user
        $user = self::getTargetUser($id);

        $debug = '';
        return [
            'user' => $user,
            'cur_user' => $cur_user,
            'can_change_email' => self::canEditEmail($cur_user, $user),
            'roles_table' => self::buildRolesTable($cur_user, $user),
            'bureau_table' => self::buildBureauTable($user),
            'sections' => self::buildSectionsTable($user),
            'year' => self::buildYears(),
            'errors' => null,
            'debug' => print_r($debug, true),
        ];
    }

    static public function post($id, $params = [])
    {
        $cur_user = self::canEditUser($id);
        $user = self::getTargetUser($id);

        //-- E-mail
        switch (self::canEditEmail($cur_user, $user)) {
            case 'full':
                $user->email = trim($params['email']);
                break;
            case 'checkbox':
                if (empty($params['newsletter'])) {
                    $user->email = '';  // If can go only one way. Once cleared, it's gone!
                }
                break;
        }
        //-- Done with modifications on the user, check and save
        $errors = $user->validate();
        if (count($errors) == 0) {
            $user->save();
        }

        //-- Roles
        if ($cur_user->_highest_level > $user->_highest_level) {  // You can change roles only for people under yourself
            if ($user->isConseiller() && $params['role'] == Role::kScorpion) { // Are we degrading a Conseiller?
                $user->removeRole(Role::kConseiller);   // I feel sad that I could not find a cleaner way to do this
            }
            $user->setRole($params['role'], null);
        }

        //-- Bureau
        if ($cur_user->isAdmin()) {
            $user->setBureauRole($params['bureau']);
        }

        //-- Sections (Membre or Officier)
        if ($cur_user->_canNameMembres || $cur_user->_canNameOfficiers) {
            $sections = self::buildSectionsTable($user);
            foreach ($sections as $section) {
                $user->setSectionMembership($section->tag, isset($params[$section->tag . '_M']),
                    $cur_user->_canNameOfficiers ? isset($params[$section->tag . '_O']) : null,
                    $section->_belong);
            }
        }

        //-- Other Roles: Adherant, CM...
        if ($cur_user->_canSetOtherRoles) {
            $user->toggleRole(Role::kCM, $params['cm']);
            $years = self::buildYears();
            $user->toggleRole(Role::kAdherant, $params['adherent_ly'], $years['last']);
            $user->toggleRole(Role::kAdherant, $params['adherent_cy'], $years['current']);
            $user->toggleRole(Role::kAdherant, $params['adherent_ny'], $years['next']);

        }

        //-- Now that we have made all kind of changes, reload the user for the UI
        $user = self::getTargetUser($id);

        $debug = '';
        return [
            'user' => $user,
            'cur_user' => $cur_user,
            'can_change_email' => self::canEditEmail($cur_user, $user),
            'roles_table' => self::buildRolesTable($cur_user, $user),
            'bureau_table' => self::buildBureauTable($user),
            'sections' => self::buildSectionsTable($user),
            'year' => $years,
            'errors' => $errors,
            'debug' => print_r($debug, true),
        ];

    }
}
