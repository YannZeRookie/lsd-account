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
        $cur_user = self::checkAccess();
        //--
        $debug = '';
        return [
            'cur_user' => $cur_user,
            'years' => self::buildYears(),
            'sections' => Section::getActiveSections(),
            'debug' => print_r($debug, true),
        ];
    }

    /**
     * AJAX search of users
     * @param array $params
     * @return array
     */
    static public function search($params = [])
    {
        $cur_user = self::checkAccess();
        //-- Analyse the search parameters and build the SQL query
        $u = new User;
        $u->select('SQL_CALC_FOUND_ROWS distinct(lsd_users.id) as uid, lsd_users.*, vb.username as vb_username');
        //--- User name
        if (isset($params['s_name']) && $params['s_name'] !== '') {
            $u->like('discord_username', '%' . $params['s_name'] . '%');
        }
        //--- Role
        if (isset($params['s_role']) && $params['s_role'] !== '') {
            $u->join('lsd_roles as r1', "r1.user_id=lsd_users.id", 'INNER');     // Notice the use of aliasing
            if (preg_match('/adherent_(\d+)/', $params['s_role'], $reg)) { // Special case for adherents: extract the year
                $u->addCondition('r1.role', '=', 'adherent', 'AND', 'join');
                $u->addCondition('r1.extra', '=', $reg[1], 'AND', 'join');
            } else {
                $u->addCondition('r1.role', '=', $params['s_role'], 'AND', 'join');  // This is how you add a condition to the JOIN part
            }
        }
        //--- Section
        if (isset($params['s_section']) && $params['s_section'] !== '') {
            $u->join('lsd_roles as r2', "r2.user_id=lsd_users.id AND r2.role in ('membre', 'officier')", 'INNER');
            $u->addCondition('r2.extra', '=', $params['s_section'], 'AND', 'join');
        }
        //--- VB Pseudo
        if (isset($params['s_vb']) && $params['s_vb'] !== '') {
            $u->join('vb_user as vb', "vb.userid=lsd_users.vb_id", 'INNER');
            $u->addCondition('vb.username', 'like', '%' . $params['s_vb'] . '%', 'AND', 'join');
        } else {
            $u->join('vb_user as vb', "vb.userid=lsd_users.vb_id", 'LEFT');
        }
        //--- Paging
        $pagination = 20;    // Number of items per page
        $page = intval($params['s_page'] ?? 1);
        $start = ($page-1)*$pagination;

        //-- Search
        $users = $u->order('discord_username')->limit($start, $pagination)->findAll();
        $total = LsdActiveRecord::rowCount();
        $pages = intdiv($total, $pagination) + ($total % $pagination ? 1 : 0);
        return [
            'cur_user' => $cur_user,
            'users' => $users,
            'page' => $page,
            'pages' => $pages,
        ];
    }

    /**
     * Can the user list users?
     * The user can see the list of users only if he is an Officer, a Conseiller, a CM, a Bureau member or an Admin
     * @param $user_id
     * @return bool
     */
    static public function canListUsers($user_id)
    {
        return Role::hasAnyRole($user_id, [Role::kOfficier, Role::kConseiller, Role::kSecretaire, Role::kTresorier, Role::kPresident, Role::kAdmin, Role::kCM]);
    }

    /**
     * Can the connected user list users?
     * @return ActiveRecord|bool    The current user
     */
    static public function checkAccess()
    {
        $cur_user = User::getConnectedUser();
        if (!$cur_user || !self::canListUsers($cur_user->id)) {
            \Slim\Slim::getInstance()->redirect('/');
        }
        return $cur_user;
    }

    /**
     * Check if we can edit the user $id. If not, redirect to /
     * Also fills in a number of information about roles and rights.
     * @param $id                       Target user to edit
     * @param bool $accept_read_only    Accept read only mode
     * @return ActiveRecord|bool        Current connected user
     */
    static public function canEditUser($id, $accept_read_only=false)
    {
        $cur_user = User::getConnectedUser();
        $role_ok = Role::hasAnyRole($cur_user->id, [Role::kOfficier, Role::kConseiller, Role::kSecretaire, Role::kTresorier, Role::kPresident, Role::kAdmin, Role::kCM]);
        $scorpion = $cur_user->isScorpion();
        if (!$cur_user || !($role_ok || $scorpion || $cur_user->id == $id)) {
            \Slim\Slim::getInstance()->redirect('/');
        }
        $cur_user->_read_only = !$role_ok && $scorpion && ($cur_user->id != $id);
        if (!$accept_read_only && $cur_user->_read_only) {
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
        if ($user->reviewer_id) {
            $uu = new User;
            $user->_reviewer = $uu->find($user->reviewer_id);
        } else {
            $user->_reviewer = false;
        }
        if ($user->vb_id) {
            $vb = new VBUser;
            $user->_vb_user = $vb->find($user->vb_id);
        } else {
            $user->_vb_user = false;
        }
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
     * Can the connected user comment on the target user?
     * @param $cur_user
     * @param $user
     * @return bool
     */
    static protected function canComment($cur_user, $user)
    {
        return $cur_user->isOfficier() || $cur_user->isConseiller() || $cur_user->isBureau() || $cur_user->isAdmin();
    }


    /**
     * Build the Roles table, with some additional permission info
     * @param User $cur_user
     * @param User $user
     * @return array
     */
    static protected function buildRolesTable($cur_user, $user)
    {
        $canChangeRoles = ($cur_user->_highest_level > $user->_highest_level);  // You can change roles only for people under yourself
        $roles_table = Role::getRolesTable(true, false, false);
        foreach ($roles_table as $role => &$role_data) {
            if ($role == Role::kOfficier) {
                $role_data['checked'] = $user->isOfficier();
            } else {
                $role_data['checked'] = $user->hasRole($role);
            }
            $role_data['class'] = Role::isBasicRole($role) ? 'basic_role' : 'higher_role';
            $role_data['iname'] = Role::isBasicRole($role) ? 'irole' : 'i' . $role;
            $role_data['disabled'] = !$canChangeRoles || ($role_data['level'] >= $cur_user->_highest_level) || $role == Role::kOfficier;   // Officiers are set through the Section table
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
        $sections = Section::getActiveSections();
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
        $cur_user = self::canEditUser($id, true);
        //-- Get edited user
        $user = self::getTargetUser($id);
        $user->comments = $user->comments ?: '';

        $query = \Slim\Slim::getInstance()->request()->get();
        $returnto = isset($query['returnto']) ? $query['returnto'] : false;

        $debug = '';
        return [
            'user' => $user,
            'cur_user' => $cur_user,
            'can_change_email' => self::canEditEmail($cur_user, $user),
            'can_comment' => self::canComment($cur_user, $user),
            'roles_table' => self::buildRolesTable($cur_user, $user),
            'bureau_table' => self::buildBureauTable($user),
            'sections' => self::buildSectionsTable($user),
            'year' => self::buildYears(),
            'errors' => null,
            'returnto' => $returnto,
            'debug' => print_r($debug, true),
        ];
    }

    static public function post($id, $params = [])
    {
        $defaults = ['iconseiller' => false, 'adherent_ly' => false, 'adherent_cy' => false, 'adherent_ny' => false, 'cm' => false];
        $params = array_merge($defaults, $params); // Set defaults, as these might be missing from the $params
        $years = self::buildYears();

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

        //-- Comments
        $can_comment = self::canComment($cur_user, $user);
        if ($can_comment) {
            $user->comments = trim($params['comments']);
        }

        //-- Done with modifications on the user, check and save
        $errors = $user->validate();
        if (count($errors) == 0) {
            $user->save();
        }

        //-- Roles
        if ($cur_user->_highest_level > $user->_highest_level && isset($params['irole'])) {  // You can change roles only for people under yourself
            $user->setRole($params['irole'], null);
        }

        //-- Conseiller & Bureau
        if ($cur_user->isAdmin()) {
            $user->toggleRole(Role::kConseiller, $params['iconseiller']);
            $user->setBureauRole($params['bureau']);
        }

        //-- Sections (Membre or Officier)
        if ($cur_user->_canNameMembres || $cur_user->_canNameOfficiers) {
            if (isset($params['irole']) && ($params['irole'] == Role::kVisiteur || $params['irole'] == Role::kInvite)) {
                //-- Remove user from all Sections
                $user->RemoveFromAllSections();
            } else {
                $sections = self::buildSectionsTable($user);
                foreach ($sections as $section) {
                    $user->setSectionMembership($section->tag, isset($params[$section->tag . '_M']),
                        $cur_user->_canNameOfficiers ? isset($params[$section->tag . '_O']) : null,
                        trim($params[$section->tag . '_pseudo']));
                }
            }
        } elseif ($cur_user->id == $user->id && $user->isScorpion()) {
            // A Scorpion can set his own Section Pseudos
//            error_log('A Scorpion can set his own Section Pseudos');
            $sections = self::buildSectionsTable($user);
            foreach ($sections as $section) {
//                error_log('Pseudo for ' . $section->tag . ' : ' . $params[$section->tag . '_pseudo']);
                $user->setPseudo($section->tag, trim($params[$section->tag . '_pseudo']));
            }
        }

        //-- Other Roles: Adherent, CM...
        if ($cur_user->_canSetOtherRoles) {
            $user->toggleRole(Role::kCM, $params['cm']);
            $user->toggleRole(Role::kAdherent, $params['adherent_ly'], $years['last']);
            $user->toggleRole(Role::kAdherent, $params['adherent_cy'], $years['current']);
            $user->toggleRole(Role::kAdherent, $params['adherent_ny'], $years['next']);

        }

        //-- Synch-up to Discord
        self::synchToDiscord($cur_user, $user);

        //-- Check if we are supposed to return somewhere
        $query = \Slim\Slim::getInstance()->request()->get();
        $returnto = isset($query['returnto']) ? $query['returnto'] : false;
        if (count($errors) == 0) {
            if ($returnto) {
                \Slim\Slim::getInstance()->redirect($returnto);
            }
        }

        //-- Now that we have made all kind of changes, reload the user for the UI
        $user = self::getTargetUser($id);

        $debug = '';
        return [
            'user' => $user,
            'cur_user' => $cur_user,
            'can_change_email' => self::canEditEmail($cur_user, $user),
            'can_comment' => $can_comment,
            'roles_table' => self::buildRolesTable($cur_user, $user),
            'bureau_table' => self::buildBureauTable($user),
            'sections' => self::buildSectionsTable($user),
            'year' => $years,
            'errors' => $errors,
            'returnto' => $returnto,
            'debug' => print_r($debug, true),
        ];

    }

    /**
     * Synchronize to Discord
     * @param User $cur_user    Connected user
     * @param User $user    Target user
     */
    static public function synchToDiscord(User $cur_user, User $user)
    {
        global $discord_upsynch;

        if (!$discord_upsynch) {
            return; // Bail out, feature is disabled
        }
        if (!Role::hasAnyRole($cur_user->id, [Role::kOfficier, Role::kConseiller, Role::kSecretaire, Role::kTresorier, Role::kPresident, Role::kAdmin, Role::kCM])) {
            return; // Bail out, no use to stay here
        }
        //-- Get the user's Discord roles
        $discord_roles = Discord::discord_get_roles();
//        error_log('synchToDiscord: $discord_roles=' . print_r($discord_roles, true));
        $user_info = Discord::discord_get_user_roles($user->discord_id);
        $d_roles = [];
        $d_otherroles = [];
        foreach ($user_info->roles as $r) {
            $lsd_role = Role::discordToLsdRole($r->name);
            if ($lsd_role) {
                $d_roles[$lsd_role] = $r->name;
            } else {
                $d_otherroles[$r->name] = $r->name;
            }
        }
//        error_log('synchToDiscord: $user_info=' . print_r($user_info, true));
//        error_log('synchToDiscord: d_roles=' . print_r($d_roles, true));
//        error_log('synchToDiscord: d_otherroles=' . print_r($d_otherroles, true));

        //-- Visiteur/Invité/Scorpion exclusive roles
        if (Role::hasAnyRole($cur_user->id, [Role::kOfficier, Role::kConseiller, Role::kSecretaire, Role::kTresorier, Role::kPresident, Role::kAdmin, Role::kCM])) {
            $basic = Role::getBasicRole($user->id);
            if ($basic && !isset($d_roles[$basic->role])) {
                foreach ([Role::kVisiteur, Role::kInvite, Role::kScorpion] as $e) {
                    if (isset($d_roles[$e])) {
                        Discord::removeRole($user->discord_id, Role::lsdToDiscordRole($e), $discord_roles);
                    }
                }
                Discord::addRole($user->discord_id, Role::lsdToDiscordRole($basic->role), $discord_roles);
            }
        }
        //-- Section-specific roles
        if (Role::hasAnyRole($cur_user->id, [Role::kOfficier, Role::kConseiller, Role::kSecretaire, Role::kTresorier, Role::kPresident, Role::kAdmin, Role::kCM])) {
            $specific = ['DU' => 'Dual-Universe'];
            foreach($specific as $s_role => $d_role) {
                $sr = $user->getSectionMembership($s_role);
                if ($sr && !isset($d_otherroles[$d_role])) {
                    Discord::addRole($user->discord_id, $d_role, $discord_roles);
                } elseif (!$sr && isset($d_otherroles[$d_role])) {
                    Discord::removeRole($user->discord_id, $d_role, $discord_roles);
                }
            }
        }
        //-- Officier
        if (Role::hasAnyRole($cur_user->id, [Role::kConseiller, Role::kSecretaire, Role::kTresorier, Role::kPresident, Role::kAdmin])) {
            $is_officier = $user->isOfficier();
            if ($is_officier && !isset($d_roles[Role::kOfficier])) {
                Discord::addRole($user->discord_id, Role::lsdToDiscordRole(Role::kOfficier), $discord_roles);
            } elseif (!$is_officier && isset($d_roles[Role::kOfficier])) {
                Discord::removeRole($user->discord_id, Role::lsdToDiscordRole(Role::kOfficier), $discord_roles);
            }
        }
        //-- Conseiller
        if (Role::hasAnyRole($cur_user->id, [Role::kAdmin])) {
            $is_conseiller = $user->isConseiller();
            if ($is_conseiller && !isset($d_roles[Role::kConseiller])) {
                Discord::addRole($user->discord_id, Role::lsdToDiscordRole(Role::kConseiller), $discord_roles);
            } elseif (!$is_conseiller && isset($d_roles[Role::kConseiller])) {
                Discord::removeRole($user->discord_id, Role::lsdToDiscordRole(Role::kConseiller), $discord_roles);
            }
        }
        //-- Bureau
        if (Role::hasAnyRole($cur_user->id, [Role::kAdmin])) {
            $is_bureau = $user->isBureau();
            if ($is_bureau && !isset($d_otherroles['Bureau'])) {
                Discord::addRole($user->discord_id, 'Bureau', $discord_roles);
            } elseif (!$is_bureau && isset($d_otherroles['Bureau'])) {
                Discord::removeRole($user->discord_id, 'Bureau', $discord_roles);
            }
        }
    }

    static public function canReviewUsers()
    {
        $cur_user = User::getConnectedUser();
        if (!$cur_user || !$cur_user->canReviewUsers()) {
            \Slim\Slim::getInstance()->redirect('/');
        }
        return $cur_user;
    }


    /**
     * Review candidates
     * @return array
     */
    static public function review()
    {
        $cur_user = self::canReviewUsers();
        //--
        $u = new User;
        $users = $u->select('distinct(lsd_users.id) as uid, lsd_users.*')
            ->notequal('submited_on', 0)
            ->equal('reviewed_on', 0)
            ->order('discord_username')
            ->findAll();

        $debug = '';
        return [
            'cur_user' => $cur_user,
            'users' => $users,
            'debug' => print_r($debug, true),
        ];
    }

    /**
     * Accept or refuse a candidate
     * @param $id
     * @param $params
     */
    static public function reviewUser($id, $params)
    {
        global $discord_channel_review;

        $cur_user = self::canReviewUsers();
        $u = new User;
        $target_user = $u->notequal('submited_on', 0)->equal('reviewed_on', 0)->find($id);
        if ($target_user) {
            $target_user->reviewed_on = time();
            $target_user->review = $params['review'];
            $target_user->reviewer_id = $cur_user->id;
            $target_user->save();
            if (isset($params['validate'])) {
                $target_user->setRole(Role::kScorpion); // Note that this will automatically remove kInvite and kVisiteur :-)
                self::synchToDiscord($cur_user, $target_user); // Grant Scorpion role on Discord
                Discord::sendPrivateMessage($target_user->discord_id, "Bonjour, ta candidature à la guilde Les Scorpions du Désert a été validée, bienvenue chez nous !");
            } else {
                Discord::sendPrivateMessage($target_user->discord_id, "Bonjour, ta candidature à la guilde Les Scorpions du Désert a été examinée mais n'a malheureusement pas été acceptée. Merci pour ton intérêt pour notre guilde et bonne continuation.");
            }
            Discord::sendChannelMessage($discord_channel_review, "La candidature de `" . $target_user->discord_username . "` a été traitée par `" . $cur_user->discord_username . "`. Merci.");

        }
        \Slim\Slim::getInstance()->redirect('/users/review');
        return [];
    }


}
