<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 26/12/2018
 * Time: 16:34
 */

class LsdActiveRecord extends ActiveRecord {

    /**
     * Find does not always return false as promised
     * @param null $id
     * @return ActiveRecord|bool
     */
    public function find($id = null)
    {
        $res = parent::find($id);
        if (!is_object($res) || empty($res->data['id'])) {
            return false;
        } else {
            return $res;
        }
    }

    /**
     * Update or insert, depending on if the id is known or not
     * @return ActiveRecord|bool
     */
    public function save()
    {
        if (empty($this->data['id'])) {
            return $this->insert();
        }
        else {
            return $this->update();
        }
    }

    /**
     * Twig tries to access fields using a function call, so we want to catch it
     * @param string $name
     * @param array $args
     * @return mixed|null
     * @throws Exception
     */
    public function __call($name, $args)
    {
        try {
            return parent::__call($name, $args);
        }
        catch(Exception $e) {
            // Try as a __get(), to help Twig
            $val = $this->__get($name);
            if (is_null($val))  {
                throw new Exception("Method or Field '$name' does not exist.");;
            }
            return $val;
        }
    }
}

