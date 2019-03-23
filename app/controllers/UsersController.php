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
        //--
        $u = new User;
        $user = $u->find($id);
        $roles = [];
        if ($user) {
            $roles = Role::getRoles($u->id);
        }
        $debug = '';
        return [
            'user' => $user,
            'roles' => $roles,
            'cur_user' => $cur_user,
            'is_myself' => ($cur_user->id == $user->id),
            'debug' => print_r($debug, true),
        ];
    }
}
