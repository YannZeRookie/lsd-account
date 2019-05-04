<?php

/**
 * Created by PhpStorm.
 * User: yann
 * Date: 04/05/2019
 * Time: 17:55
 *
 * CRUD of Sections
 */
class SectionsController
{
    static protected function checkAccess()
    {
        //-- Check rights: the connected user can see the list of users only if he is a Conseiller, a Bureau member or an Admin
        $cur_user = User::getConnectedUser();
        if (!$cur_user || !Role::hasAnyRole($cur_user->id, [Role::kConseiller, Role::kSecretaire, Role::kTresorier, Role::kPresident, Role::kAdmin])) {
            \Slim\Slim::getInstance()->redirect('/');
        }
        return $cur_user;
    }


    /**
     * View all sections
     * @return array
     */
    static public function all()
    {
        $cur_user = self::checkAccess();

        $sections = [
            'Sections actives' => Section::getActiveSections(true),
            'Sections archivées' => Section::getArchivedSections(true)
        ];

        //--
        $debug = '';
        return [
            'sections' => $sections,
            'debug' => print_r($debug, true),
        ];
    }


    /**
     * View a specific Section
     * @param $id
     * @return array
     */
    static public function edit($tag)
    {
        self::checkAccess();
        $section = Section::getSection($tag);

        $debug = '';
        return [
            'new_tag' => ($tag == 'new'),
            'section' => $section,
            'errors' => null,
            'debug' => print_r($debug, true),
        ];
    }

    static public function post($tag, $params = [])
    {
        self::checkAccess();
        $params = array_merge(['tag'=>'', 'name'=>'', 'archived'=>'0'], $params);
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
            if ($ok !== false)
            {
               \Slim\Slim::getInstance()->redirect('/sections');   // Go back to list if success
            }
        }

        $debug = '';
        return [
            'new_tag' => ($tag == 'new'),
            'section' => $section,
            'errors' => $errors,
            'debug' => print_r($debug, true),
        ];

    }
}
