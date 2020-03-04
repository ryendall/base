<?php
namespace im\model;

class PortalCategories {

    const DB_TABLE = 'portal_category';

    /**
     * For a given top-level category, return array of portal_category data
     * @param $db
     * @param int $categoryId
     * @return array $results
     *
    */
    public static function getPortalsByCategory(\SqlDb $db, int $categoryId) {

        $sql = 'SELECT t.* FROM '.self::DB_TABLE.' t, m_portal p WHERE p.status = "live" AND p.portal_id = t.portal_id AND t.category_id = '.$categoryId;
        $result=$db->sql_query($sql);
        return $db->sql_fetchrowset($result);
    }
}