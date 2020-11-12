<?php

/**
 * Created by PhpStorm.
 * User: yann
 * Date: 17/12/2020
 * Time: 21:53
 *
 * Display the association members on Joomla in an iFrame
 */

class MembersController
{

    /**
     * Display the members by categories
     * @return array
     */
    static public function members()
    {
        $debug = '';

        $members = [];
        $members['Bureau de l\'Association']['Président(e)'] = self::getOneUserByRole(Role::kPresident);
        $members['Bureau de l\'Association']['Trésorier(e)'] = self::getOneUserByRole(Role::kTresorier);
        $members['Bureau de l\'Association']['Secrétaire']= self::getOneUserByRole(Role::kSecretaire);
        $members['Conseil de l\'Association'] = self::getUsersByRole(Role::kConseiller);
        $members['Adhérents'] = self::getUsersByRole(Role::kAdherent, date('Y'));
        //$members['Modérateurs'] = [];
        $members['Gestionnaires de communauté'] = self::getUsersByRole(Role::kCM);
        $members['Administrateurs techniques'] = self::getUsersByRole(Role::kAdmin);
        $sections = Section::getActiveSections(true);
        foreach ($sections as $s) {
            $members['Gradés ' . $s->name] = self::getUsersByRole(Role::kOfficier, $s->tag);
        }

        return [
            'members' => $members,
            'debug' => print_r($debug,true),
        ];
    }

    /**
     * Get a list of users by a role
     * @param string $role See Role constants
     * @param string $extra
     * @return mixed list of Users
     */
    static public function getUsersByRole($role, $extra='')
    {
        $u = new User;
        $u->select('distinct(lsd_users.id) as uid, lsd_users.*');
        $u->join('lsd_roles as r1', "r1.user_id=lsd_users.id", 'INNER');   // Notice the use of aliasing
        if ($extra) {
            $u->addCondition('r1.role', '=', $role, 'AND', 'join');            // This is how you add a condition to the JOIN part
            $u->addCondition('r1.extra', '=', $extra, 'AND', 'join');
        } else {
            $u->addCondition('r1.role', '=', $role, 'AND', 'join');
        }
        return $u->order('discord_username')->findAll();
    }

    /**
     * Get one User by a rol
     * @param string $role See Role constants
     * @param string $extra
     * @return User
     */
    static public function getOneUserByRole($role, $extra='')
    {
        $users = self::getUsersByRole($role, $extra);
        return (count($users) ? $users[0] : []);
    }

}
