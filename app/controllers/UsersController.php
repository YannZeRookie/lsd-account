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
     * View a specific user
     * @param $id
     * @return array
     */
    static public function view($id)
    {
        //-- Check rights: the connected user can see the list of users only if he is an Officer, a Conseiller, a CM, a Bureau member or an Admin,
        //   or if he is looking at its own record
        $cur_user = User::getConnectedUser();
        if (!$cur_user || !(Role::hasAnyRole($cur_user->id, [Role::kOfficier, Role::kConseiller, Role::kSecretaire, Role::kTresorier, Role::kPresident, Role::kAdmin, Role::kCM]) || $cur_user->id == $id)) {
            \Slim\Slim::getInstance()->redirect('/');
        }
        $cur_user_highest_level = Role::getRoleLevel(Role::getHighestRole($cur_user->id));
        $cur_user->canNameMembers = Role::canNameMembers($cur_user->id);
        $cur_user->canNameOfficiers = Role::canNameOfficiers($cur_user->id);
        $cur_user->canSetOtherRoles = Role::canSetOtherRoles($cur_user->id);

        //-- Get user
        $u = new User;
        $user = $u->find($id);
        if (!$user) {
            \Slim\Slim::getInstance()->redirect('/');
        }
        $user->adherent = Role::isAdherent($user->id, date('Y'));
        $user->cm = Role::isCM($user->id);
        //-- Roles table
        $user->highest_role = Role::getHighestRole($user->id);
        $canChangeRoles = ($cur_user_highest_level > Role::getRoleLevel($user->highest_role));  // You can change roles only for people under yourself
        $roles_table = Role::getRolesTable();
        foreach ($roles_table as $role => $role_data) {
            $roles_table[$role]['disabled'] = !$canChangeRoles || ($role_data['level'] >= $cur_user_highest_level);
        }
        //-- Sections
        $s = new Section;
        $sections = $s->equal('archived', 0)->order('`order`')->findAll();
        foreach ($sections as &$section) {
            $section->belong = $user->belongsToSection($section->tag);
        }

        $debug = '';
        return [
            'user' => $user,
            'cur_user' => $cur_user,
            'is_myself' => ($cur_user->id == $user->id),
            'roles_table' => $roles_table,
            'sections' => $sections,
            'debug' => print_r($debug, true),
        ];
    }
}
