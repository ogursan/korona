<?php
namespace Database;

use \PDO;
use \PDOStatement;
use \Exception;
use \PDOException;

class DB {

    const DB_SQL_DEBUG = DEBUG;

    const DATA_IMPLODE_KEYS  = ', ';
    const DATA_IMPLODE_WHERE = ' AND ';

    const IS_CONNECT            = 'CONNECT';
    const FAIL_CONNECT          = 'DB_FAIL_CONNECT';
    const FAIL_PREPARE          = 'DB_FAIL_PREPARE';
    const FAIL_EXEC             = 'DB_FAIL_EXEC';
    const INSERT_EMPTY_DATA     = 'DB_INSERT_EMPTY_DATA';
    const DATA_TYPE_EMPTY       = 'DB_DATA_TYPE_EMPTY';
    const INVALID_BIND_ARRAY    = 'DB_INVALID_BIND_ARRAY';
    const INVALID_TYPE_ITEM     = 'DB_INVALID_TYPE_ITEM';

    /**
     * @var \PDO $handle
     */
    static public $handle;

    /**
     * @param string $host
     * @param string $port
     * @param string $dbname
     * @param string $user
     * @param string $pwd
     * @throws \Exception
     */
    static public function initialize($host, $port, $dbname, $user, $pwd){
        self::$handle = self::connect($host, $port, $dbname, $user, $pwd);
    }

    /**
     * Connect to DB
     *
     * @param string $host
     * @param string $port
     * @param string $dbname
     * @param string $user
     * @param string $pwd
     * @throws Exception
     * @return PDO
     */
    static public function connect($host, $port, $dbname, $user, $pwd){
        $dsn = sprintf("mysql:host=%s; port=%d; dbname=%s", $host, $port, $dbname);

        try {

            $dbh = new PDO($dsn, $user, $pwd);

            if (self::DB_SQL_DEBUG)
                DBDebug::write("\n" . self::IS_CONNECT . " === " . date(DBDebug::DATE_FORMAT));

            return $dbh;

        } catch (PDOException $e){
            if (self::DB_SQL_DEBUG)
                DBDebug::write("\n" . self::FAIL_CONNECT . " === " . date(DBDebug::DATE_FORMAT));
            throw new Exception(self::FAIL_CONNECT .': '. $e->getMessage());
        }
    }

    /**
     * Run sql, binding data
     *
     * @param string $sql
     * @param array $data
     * @return PDOStatement
     * @throws Exception
     */
    static public function exec($sql, array $data = array()){

        if (self::DB_SQL_DEBUG)
            DBDebug::set($sql, $data);

        $sth = self::$handle->prepare($sql);

        if (!$sth) {
            DBDebug::set($sql, $data, true);
            DBDebug::sqlSplitError($sth);
            throw new Exception(self::FAIL_PREPARE);
        }

        if (!empty($data) && !is_numeric( array_keys($data)[0] )){
            foreach ($data as $key => $item) {
                if (is_array($item)){
                    $value = $item[0];
                    $type  = $item[1];
                } else {
                    list($value, $type) = self::getDataType($item);
                }

                $sth->bindValue($key, $value, $type);
            }

            $status = $sth->execute();
        } else {
            $status = $sth->execute($data);
        }

        if ($status === false){
            if (self::DB_SQL_DEBUG) {
                DBDebug::set($sql, $data, true);
                DBDebug::sqlSplitError($sth);
            }

            throw new Exception(self::FAIL_EXEC);
        }

        return $sth;
    }

    /**
     * Update table with data & where
     *
     * @param string $table
     * @param array $data
     * @param array $where
     * @throws \Exception
     */
    static public function update($table, array $data, array $where = array()){
        $keys = self::prepareDataKeys($data);

        $search = empty($where) ? '' : ' WHERE ' . self::prepareDataKeys($where, self::DATA_IMPLODE_WHERE);

        $sql = "UPDATE $table SET $keys " . $search;

        $values = self::setDataType($data);
        $values += self::setDataType($where);
        self::exec($sql, $values);
    }

    /**
     * @param array $data
     * @param string $type
     * @return string
     */
    static private function prepareDataKeys($data, $type = self::DATA_IMPLODE_KEYS){
        $keys = array();

        foreach ($data as $key => $item)
            $keys[] = $key . ' = :' . $key;

        return implode($type, $keys);
    }

    /**
     * Insert data via table & multi-data
     *
     * @param string $table
     * @param array $data
     * @param null $return
     * @return array|null
     * @throws Exception
     */
    static public function insert($table, array $data, $return = null){
        if (empty($data))
            throw new Exception(self::INSERT_EMPTY_DATA);

        // multi
        if (!!is_numeric(array_keys($data)[0])) {

            $keys = array_keys($data[0]);
            $listKeys = implode(', ', $keys);
            $listValues = ':' . implode(', :', $keys);

            $sql = "INSERT INTO $table ($listKeys) VALUES ($listValues)" . ($return ? ' RETURNING ' . $return : '');

            $inserts = array();

            foreach ($data as $item){

                $sth = self::exec($sql, $item);

                if (!$return)
                    continue;

                $response = $sth->fetch();
                if (isset($response[0]))
                    $inserts[] = $response[0];
            }

            return $inserts;
        }
        // single
        else {

            $keys = array_keys($data);
            $listKeys = implode(', ', $keys);
            $listValues = ':' . implode(', :', $keys);

            $sql = "INSERT INTO $table ($listKeys) VALUES ($listValues)" . ($return ? ' RETURNING ' . $return : '');

            $sth = self::exec($sql, $data);

            if (!$return)
                return null;

            $response = $sth->fetch();
            if (isset($response[0]))
                return $response[0];

            return null;
        }
    }

    /**
     * Select row[s]
     *
     * @param string $sql
     * @param array $data
     * @param bool $single
     * @param bool $bind
     * @return array
     */
    static public function select($sql, array $data = array(), $single = false, $bind = false){
        $sth = self::exec($sql, $data, $bind);
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);

        if ($single && !empty($result) && isset($result[0]))
            return $result[0];

        return $result;
    }

    /**
     * Wrap over query ` IN (:id1,id2, ... etc) `
     *
     * @param array $data
     * @param string $key
     * @return array [':in_ids0,:in_ids1', ['in_ids0' => '<value0>', ...]]
     */
    static public function wrap_in($data, $key = 'in_ids'){
        if (!$data)
            return array('', array());

        $count = count($data);

        if ($count > 1){
            $keys = array();

            foreach ($data as $k => $item)
                $keys[] = $key.'_'.$k;

            $query = ':'.implode(',:', $keys);
            $result = array_combine($keys, $data);
        } else {
            $query = ':'.$key;
            $result = array($key => $data[0]);
        }

        if (self::DB_SQL_DEBUG)
            DBDebug::write('DB::wrap_in: ' . json_encode(array($query, $result)));

        return array($query, $result);
    }

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    static private function setDataType($data){
        if (empty($data))
            throw new Exception(self::DATA_TYPE_EMPTY);

        $response = array();

        foreach ($data as $key => $item){
            $response[$key] = self::getDataType($item);
        }

        return $response;
    }

    /**
     * @param mixed $item
     * @throws Exception
     * @return array
     */
    static private function getDataType($item){
        if (is_bool($item))
            return array($item, PDO::PARAM_BOOL);

        elseif (is_float($item))
            return array($item, PDO::PARAM_STR);

        elseif (is_numeric($item))
            return array($item, PDO::PARAM_INT);

        elseif (is_string($item))
            return array($item, PDO::PARAM_STR);

        elseif (is_null($item))
            return array($item, PDO::PARAM_NULL);

        elseif (is_array($item))
            throw new Exception(self::INVALID_BIND_ARRAY);

        else
            throw new Exception(self::INVALID_TYPE_ITEM);
    }

}