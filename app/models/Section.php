<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 28/12/2018
 * Time: 23:40
 */

require_once __DIR__ . '/LsdActiveRecord.php';

class Section extends LsdActiveRecord {
    public $table = 'lsd_section';

    public static function getActiveSections()
    {
        $s = new Section;
        return $s->equal('archived', 0)->order('`order`')->findAll();
    }
}
