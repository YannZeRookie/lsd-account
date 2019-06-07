<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 08/05/2019
 * Time: 16:15
 */

require_once __DIR__ . '/LsdActiveRecord.php';
require_once __DIR__ . '/LsdActiveRecord.php';

class Adhesion extends LsdActiveRecord
{
    public $table = 'lsd_adhesions';

    const kCotisationStandard = 'standard';
    const kCotisationCustom = 'custom';

    public function __construct()
    {
        $this->user_id = 0;
        $this->name = '';
        $this->firstname = '';
        $this->dob = '';
        $this->address = '';
        $this->telephone = '';
        $this->cotisation = self::kCotisationStandard;
        $this->amount = 0.00;
        $this->created_on = time();
        parent::__construct();
    }

    protected static function checkEmpty($value, $name, $errors)
    {
        if (trim($value) == '') {
            $errors[$name] = 'manquant';
        }
        return $errors;
    }

    public function validate()
    {
        $errors = [];
        $errors = self::checkEmpty($this->name, 'nom', $errors);
        $errors = self::checkEmpty($this->firstname, 'prenom', $errors);
        $errors = self::checkEmpty($this->dob, 'naissance', $errors);
        $errors = self::checkEmpty($this->address, 'adresse', $errors);
        $errors = self::checkEmpty($this->telephone, 'telephone', $errors);
        return $errors;
    }
}
