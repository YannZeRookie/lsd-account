<?php
/**
 * Created by PhpStorm.
 * User: yann
 * Date: 26/12/2018
 * Time: 16:34
 */

class LsdActiveRecord extends ActiveRecord {

    protected $private = [];    // Add the possibility to store private data through the magic __set() and __get() methods

    /**
     * magic function to SET values of the current object.
     * We consider variables that start by '_' as private instance variables
     */
    public function __set($var, $val) {
        if ($var{0} == '_') {
            $this->private[$var] = $val;
        } else {
            parent::__set($var, $val);
        }
    }

    /**
     * magic function to GET the values of current object.
     * We consider variables that start by '_' as private instance variables
     */
    public function & __get($var) {
        if ($var{0} == '_') {
            if (isset($this->private[$var])) {
                return $this->private[$var];
            } else {
                return null;
            }
        }
        else {
            return parent::__get($var);
        }
    }

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

