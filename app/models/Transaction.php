<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 02/11/2019
 * Time: 16:02
 */

require_once __DIR__ . '/LsdActiveRecord.php';

class Transaction extends LsdActiveRecord
{
    public $table = 'lsd_transactions';

    public function __construct()
    {
        $this->adhesion_id = 0;
        $this->ipn_status = '';
        parent::__construct();
    }

}
