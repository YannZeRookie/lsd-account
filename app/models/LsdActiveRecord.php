<?php

/**
 * Created by PhpStorm.
 * User: yann
 * Date: 26/12/2018
 * Time: 16:34
 */
class LsdActiveRecord extends ActiveRecord
{

    protected $private = [];    // Add the possibility to store private data through the magic __set() and __get() methods

    /**
     * magic function to SET values of the current object.
     * We consider variables that start by '_' as private instance variables
     */
    public function __set($var, $val)
    {
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
    public function & __get($var)
    {
        if ($var{0} == '_') {
            if (isset($this->private[$var])) {
                return $this->private[$var];
            } else {
                return null;
            }
        } else {
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
        if (!is_object($res) || empty($res->data[$res->primaryKey])) {
            return false;
        } else {
            return $res;
        }
    }

    /**
     * Update or insert, depending on if the id is known or not
     * @param bool $force_insert
     * @return ActiveRecord|bool
     */
    public function save($force_insert = false)
    {
        if ($force_insert || empty($this->data[$this->primaryKey])) {
            return $this->insert();
        } else {
            return $this->update();
        }
    }

    /**
     * Bug fix, so that it works with NULL fields
     */
    public function update() {
        if (count($this->dirty) == 0) return true;
        foreach($this->dirty as $field => $value) {
            if ($value === null) {
                $this->addCondition($field, '= NULL', null, ',' , 'set');
            } else {
                $this->addCondition($field, '=', $value, ',' , 'set');
            }
        }
        if(self::execute($this->eq($this->primaryKey, $this->{$this->primaryKey})->_buildSql(array('update', 'set', 'where')), $this->params))
            return $this->dirty()->reset();
        return false;
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
        } catch (Exception $e) {
            // Try as a __get(), to help Twig
            $val = $this->__get($name);
            if (is_null($val)) {
                throw new Exception("Method or Field '$name' does not exist.");;
            }
            return $val;
        }
    }

    /**
     * Helper function to get a single object from a raw SQL query
     * @param string $sql
     * @param array $params
     * @return bool|mixed
     */
    static public function q_singleobj($sql, $params = [])
    {
        $stmt = self::$db->prepare($sql);
        $res = $stmt->execute($params);
        return $res ? $stmt->fetchObject() : false;
    }

    /**
     * Helper function to get a single value from a raw SQL query
     * @param $sql
     * @param array $params
     * @return bool|string
     */
    static public function q_singleval($sql, $params = [])
    {
        $stmt = self::$db->prepare($sql);
        $res = $stmt->execute($params);
        return $res ? $stmt->fetchColumn() : false;
    }

    /**
     * Return the number of potentially found rows of the previous SQL statement, provided that SQL_CALC_FOUND_ROWS
     * was used.
     * @return int
     */
    public static function rowCount()
    {
        $rs1 = self::$db->query("SELECT FOUND_ROWS()");
        return intval($rs1->fetchColumn());
    }

}

