<?php
namespace im\model;

class Models {

    const DB_TABLE = 'm_model';

    /**
     * Return list of active models
     * @param $db
     * @return array $results
     *
    */
    public static function getModels(\SqlDb $db) {

        $sql = 'SELECT * FROM '.self::DB_TABLE.' WHERE status = "live"';
        $result=$db->sql_query($sql);
        return $db->sql_fetchrowset($result);
    }
}