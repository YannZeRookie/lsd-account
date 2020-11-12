<?php

/**
 * Created by PhpStorm.
 * User: yann
 * Date: 04/05/2019
 * Time: 17:55
 *
 * CRUD of Sections
 */
require_once __DIR__ . '/../libs/Parsedown.php';

class SectionsController
{
    /**
     * A privileged user is a Conseiller, a Bureau member or an Admin
     * @param $user_id
     * @return bool
     */
    static protected function hasPrivilegedAccess($user_id)
    {
        return Role::hasAnyRole($user_id, [Role::kConseiller, Role::kSecretaire, Role::kTresorier, Role::kPresident, Role::kAdmin]);
    }

    static protected function checkAccess()
    {
        //-- Check rights: the connected user can see the list of Sections only if he a privileged user
        $cur_user = User::getConnectedUser();
        if (!$cur_user || !self::hasPrivilegedAccess($cur_user->id)) {
            redirectTo('/');
        }
        return $cur_user;
    }

    static protected function checkAccessList(&$can_edit)
    {
        $cur_user = User::getConnectedUser();
        if (!$cur_user || !$cur_user->isScorpion()) {
            redirectTo('/');
        }
        $can_edit = self::hasPrivilegedAccess($cur_user->id);
        return $cur_user;

    }

    static protected function checkAccessNotes($tag, &$can_read, &$can_edit)
    {
        //-- Check rights for Notes: the connected user can see the list of Sections only if he is a member of the Section
        $cur_user = User::getConnectedUser();
        if (!$cur_user ||!$cur_user->isScorpion()) {
            redirectTo('/sections');
        }
        $can_read = self::canReadNotes($cur_user, $tag);
        $can_edit = self::canEditNotes($cur_user, $tag);
        return $cur_user;
    }

    static protected function canReadNotes($cur_user, $tag)
    {
        if (!$cur_user) return false;
        if (self::hasPrivilegedAccess($cur_user->id)) return true;
        return ($cur_user->getSectionMembership($tag) !== false);
    }

    static protected function canEditNotes($cur_user, $tag)
    {
        if (!$cur_user) return false;
        if (self::hasPrivilegedAccess($cur_user->id)) return true;
        $role = $cur_user->getSectionMembership($tag);
        return $role ? ($role->role == Role::kOfficier) : false;
    }

    /**
     * View all sections
     * @return array
     */
    static public function all()
    {
        $cur_user = self::checkAccessList($can_edit);

        $sections = [];
        $sections['Sections actives'] = Section::getActiveSections(true);
        self::setNotesAccess($sections['Sections actives']);

        if ($can_edit) {
            $sections['Sections archivées'] = Section::getArchivedSections(true);
            self::setNotesAccess($sections['Sections archivées']);
        }

        //--
        $debug = '';
        return [
            'cur_user' => $cur_user,
            'sections' => $sections,
            'can_edit' => $can_edit,
            'debug' => print_r($debug, true),
        ];
    }

    static protected function setNotesAccess(&$sections)
    {
        foreach ($sections as &$section) {
            self::checkAccessNotes($section->tag, $can_read, $can_edit);
            $section->_notes_can_read = $can_read;
            $section->_notes_can_edit = $can_edit;
        }
    }

    /**
     * View a specific Section
     * @param $id
     * @return array
     */
    static public function edit($tag)
    {
        $cur_user = self::checkAccess();
        $section = Section::getSection($tag);

        $debug = '';
        return [
            'cur_user' => $cur_user,
            'new_tag' => ($tag == 'new'),
            'section' => $section,
            'errors' => null,
            'debug' => print_r($debug, true),
        ];
    }

    static public function post($app, $tag, $params = [])
    {
        $cur_user = self::checkAccess();
        $params = array_merge(['tag' => '', 'name' => '', 'archived' => '0'], $params);
        $section = Section::getSection($tag) ?: new Section();

        if ($tag == 'new') {
            $section->tag = strtoupper(trim($params['tag']));
        }
        $section->name = ucfirst(trim($params['name']));
        $section->archived = $params['archived'] ? '1' : '0';

        //-- Check and save
        $errors = $section->validate($tag == 'new');
        if (count($errors) == 0) {
            if ($tag == 'new') {
                $section->data['`order`'] = $section->dirty['`order`'] = Section::total();
                $ok = $section->insert();
            } else {
                $ok = $section->update();
            }
            $app->flash->addMessage('success', "Mise à jour de la Section {$tag} effectuée");
            if ($ok !== false) {
                if ($section->archived) {
                    Role::degradeOfficiers($section->tag);
                }
                redirectTo('/sections');   // Go back to list if success
            }
        }

        $debug = '';
        return [
            'cur_user' => $cur_user,
            'new_tag' => ($tag == 'new'),
            'section' => $section,
            'errors' => $errors,
            'debug' => print_r($debug, true),
        ];
    }

    /**
     * Section notes page
     * @param $tag
     */
    static public function notes($app, $tag, $params = [])
    {
        $cur_user = self::checkAccessNotes($tag, $can_read, $can_edit);
        $section = Section::getSection($tag);

        if ($can_edit && isset($params['submit'])) {
            $section->notes = $params['markdown'];
            $section->save();
            $app->flash->addMessage('success', "Mise à jour des notes de la Section {$tag} effectuée");
            redirectTo('/sections');
        }

        $md = $section->notes;
        $content = self::mdConvert($md);

        $debug = '';
        return [
            'cur_user' => $cur_user,
            'section' => $section,
            'can_read' => $can_read,
            'can_edit' => $can_edit,
            'md' => $md,
            'content' => $content,
            'debug' => print_r($debug, true),
        ];
    }

    static protected function mdConvert($md)
    {
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);
        $parsedown->setBreaksEnabled(true);
        $html = $parsedown->text($md);
        $html = preg_replace('/<table>/', '<table class="table table-striped">', $html);
        return $html;
    }

    static public function markdown($tag, $md)
    {
        $html = '';
        $cur_user = User::getConnectedUser();
        if (self::canEditNotes($cur_user, $tag)) {
            $html = self::mdConvert($md);
        }
        echo $html;
        exit();
    }
}
