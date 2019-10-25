<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 28/12/2018
 * Time: 23:40
 */

require_once __DIR__ . '/LsdActiveRecord.php';

class Section extends LsdActiveRecord
{
    public $table = 'lsd_section';
    public $primaryKey = 'tag';

    public function & __get($var)
    {
        if ($var == 'notes') {
            if (isset($this->data[$var])) {
                return $this->data[$var];
            } else {
                $res = '';
                return $res;
            }
        } else {
            return parent::__get($var);
        }
    }

    public static function getActiveSections($include_officers = false)
    {
        $s = new Section;
        $result = $s->equal('archived', 0)->order('`order`')->findAll();
        if ($include_officers) {
            foreach ($result as &$s) {
                $s->_officers = self::getOfficers($s->tag);
            }
        }
        return $result;
    }

    public static function getArchivedSections($include_officers = false)
    {
        $s = new Section;
        $result = $s->equal('archived', 1)->order('`order`')->findAll();
        if ($include_officers) {
            foreach ($result as &$s) {
                $s->_officers = self::getOfficers($s->tag);
            }
        }
        return $result;
    }

    public static function getSection($tag, $include_officers = false)
    {
        $s = new Section;
        $section = $s->find($tag);
        if ($section && $include_officers) {
            $section->_officers = self::getOfficers($tag);
        }
        return $section;
    }

    protected static function getOfficers($tag)
    {
        $u = new User;
        $u->join('lsd_roles as r', "r.user_id=lsd_users.id AND r.role='officier' ", 'INNER');
        $u->addCondition('r.extra', '=', $tag, 'AND', 'join');
        return $u->findAll();
    }

    /**
     * Check the data and return errors if any found.
     * Use before saving to database.
     * @return array List of: 'Human readable field name' => 'Human readable error message'
     */
    public function validate($must_be_new_tag = false)
    {
        $errors = [];
        if ($this->tag == 'NEW') {
            $errors['tag'] = 'Le tag "NEW" est réservé';
        }
        if ($this->tag == '') {
            $errors['tag'] = 'manquant';
        } elseif (!preg_match('/^[\w]+$/', $this->tag)) {
            $errors['tag'] = 'invalide';
        } elseif ($must_be_new_tag && self::getSection($this->tag) !== false) {
            $errors['tag'] = 'déjà utilisé par une autre Section';
        }

        if ($this->name == '') {
            $errors['nom'] = 'manquant';
        }
        if ($this->tag == 'JDM' && $this->archived == 1) {
            $errors['état'] = 'la Section JDM ne peut pas être archivée';
        }
        return $errors;
    }

    /**
     * Return the total number of Sections
     * @return int
     */
    static public function total()
    {
        return intval(self::q_singleval('SELECT count(*) FROM lsd_section'));
    }

    /**
     * Find a Section by a VB group id
     * @param integer $vb_group VB Group ID
     * @return false|array     Return false if not found, or an array with the Section, and flags for membre and officier
     */
    static public function findByVBGroup($vb_group)
    {
        $s = new Section;

        $result = $s->equal('officer_group', $vb_group)->find();
        if ($result) {
            return ['tag' => $result->tag, 'membre' => false, 'officier' => true];
        }

        $result = $s->equal('member_group', $vb_group)->find();
        if ($result) {
            return ['tag' => $result->tag, 'membre' => true, 'officier' => false];
        }
        return false;
    }
}
