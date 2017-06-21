<?php
/**
 * Created by PhpStorm.
 * User: mmoser
 * Date: 10.10.2016
 * Time: 11:22
 */

namespace CustomerManagementFrameworkBundle\Service;

use Pimcore\Db;

class MariaDb {

    CONST DYNAMIC_COLUMN_DATA_TYPE_CHAR = 'char';
    CONST DYNAMIC_COLUMN_DATA_TYPE_DOUBLE = 'double';
    CONST DYNAMIC_COLUMN_DATA_TYPE_INTEGER = 'integer';
    CONST DYNAMIC_COLUMN_DATA_TYPE_BOOLEAN= 'boolean';

    private function __construct()
    {

    }

    /**
     * @return static
     */
    private static $instance;
    public static function getInstance()
    {
        if(is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }


    /**
     * Generates insert SQL statement for MariaDBs dynamic column feature.
     *
     * @param array $data
     *
     * @return string
     */
    public function createDynamicColumnInsert(array $data, array $dataTypes = []) {

        $insert = '';
        $i=0;
        foreach($data as $key => $value) {
            $i++;
            if(!is_array($value)) {

                $dataType = isset($dataTypes[$key]) ? $dataTypes[$key] : false;

                $insert .= "'" . $key . "'" . ','. $this->convertDynamicColumnValueAccordingToDataType($value, $dataType);

                $dataType = $this->castDynamicColumnDatatype($dataType);
                
                if($dataType) {
                    $insert .= " as " . $dataType;
                }

            } else {
                $insert .= "'" . $key . "'" . ','.$this->createDynamicColumnInsert($value, $dataTypes);
            }

            if($i < sizeof($data)) {
                $insert .= ',';
            }
        }

        return "COLUMN_CREATE(" . $insert .  ")";
    }

    private function castDynamicColumnDatatype($dataType) {

        if($dataType == self::DYNAMIC_COLUMN_DATA_TYPE_BOOLEAN) {
            return self::DYNAMIC_COLUMN_DATA_TYPE_INTEGER;
        }

        return $dataType;
    }

    private function convertDynamicColumnValueAccordingToDataType($value, $dataType) {

        $db = Db::get();

        if(is_null($value)) {
            return 'null';
        }

        if($dataType == self::DYNAMIC_COLUMN_DATA_TYPE_BOOLEAN) {
            return $value ? 1 : 0;
        }

        return $db->quote($value);
    }

    /**
     * Insert $data into table $tableName. Returns last inserted ID.
     *
     * @param string $tableName
     * @param array  $data
     *
     * @return int
     */
    public function insert($tableName, array $data) {

        $db = Db::get();

        foreach($data as $key => $value) {
            if(is_null($value)) {
                unset($data[$key]);
            }
        }

        $sql = sprintf("INSERT INTO %s (%s) VALUES (%s)",
            $tableName,
            implode(',', array_keys($data)),
            implode(',', array_values($data)));

        $db->query($sql);

        return $db->lastInsertId();
    }

    /**
     * Updates table $tableName with $data for rows which are matching $where.
     *
     * @param $tableName
     * @param $data
     * @param $where
     *
     * @return void
     */
    public function update($tableName, $data, $where) {
        $db = Db::get();

        $sql = "UPDATE " . $tableName . " SET ";

        $set = [];
        foreach($data as $key => $value) {
            if(is_null($value)) {
                $set[] = $key . ' = NULL';
            } else {
                $set[] = $key . ' = ' . $value;
            }

        }

        $sql .= implode(', ', $set);
        $sql .= " WHERE " . $where;

        $db->query($sql);
    }

}