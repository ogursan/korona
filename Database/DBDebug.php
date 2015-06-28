<?php
namespace Database;

use \Exception;
use \PDOStatement;

class DBDebug {

    const PATH_LOGS             = 'logs/';
    const FILENAME_LOG          = 'sql.log';
    const FILENAME_LOG_ERROR    = 'sql.error.log';
    const DATE_FORMAT           = 'Y-m-d_H-i-s';

    /**
     * Wrapper query & data for write to log
     *
     * @param string $sql
     * @param array $data
     * @param bool $error
     */
    static public function set($sql, $data, $error = false){
        list ($string, $count) = self::sqlSplit($sql, $data);
        $str = self::sqlLogStr($string, $count);

        self::write($str, $error ? self::FILENAME_LOG_ERROR : self::FILENAME_LOG);
    }

    /**
     * Get date formatted string to log
     *
     * @param string $sql
     * @param int $count
     * @return string
     */
    static private function sqlLogStr($sql, $count){
        return date(self::DATE_FORMAT) . '; (replaced ' . $count .  ' keys) :: ' . $sql;
    }

    /**
     * Print error PDO
     *
     * @param PDOStatement $sth
     */
    static public function sqlSplitError(PDOStatement$sth){
        self::write('Error occurred: '.implode(': ', $sth->errorInfo()), self::FILENAME_LOG_ERROR);
    }

    /**
     * Sql query split to string
     *
     * @param string $query
     * @param array $params
     * @return array
     */
    static private function sqlSplit($query, array $params) {
        $keys = array();
        $values = $params;

        # build a regular expression for each parameter
        foreach ($params as $key => $value) {

            $keys[] = is_string($key) ? '/:'.$key.'/' : '/[?]/';

            if (is_bool($value))
                $values[$key] = $value ? 'TRUE' : 'FALSE';

            elseif (is_string($value))
                $values[$key] = "'" . $value . "'";

            elseif (is_array($value)){
                if (isset($value[0]) && isset($value[1])){

                    if (is_bool($value[0]))
                        $values[$key] = !!$value[0] ? 'TRUE' : 'FALSE';
                    elseif (is_string($value[0]))
                        $values[$key] = "'" . $value[0] . "'";
                    elseif (is_null($value[0]))
                        $values[$key] = 'NULL';
                    elseif (is_numeric($value[0]))
                        $values[$key] = $value[0];
                    elseif (is_float($value[0]))
                        $values[$key] = "'" . $value[0] . "'";
                    else
                        $values[$key] = 'Array' . json_encode($value);
                } else {
                    $values[$key] = 'Array' . json_encode($value);
                }
            }

            elseif (is_numeric($value))
                $values[$key] = $value;

            elseif (is_null($value))
                $values[$key] = 'NULL';

            elseif (is_float($value))
                $values[$key] = "'" . $value . "'";;
        }

        $query = preg_replace($keys, $values, $query, 1, $count);

        return array($query, $count);
    }

    /**
     * Write string to log
     *
     * @param string $str
     * @param string $file
     * @throws Exception
     */
    static public function write($str, $file = self::FILENAME_LOG){
        $filename = WORKPATH . self::PATH_LOGS . $file;

        $status = file_put_contents($filename, "\n" . $str, FILE_APPEND);

        if (!$status)
            throw new Exception('DB_FAIL_LOG');
    }
}