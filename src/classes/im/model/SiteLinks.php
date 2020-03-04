<?php
namespace im\model;

class SiteLinks {

    const DB_TABLE = 'site_link';

    /**
     * For a given site, return array of site_link records
     * @param $db
     * @param int $siteId
     * @param string $status
     * @return array $results
     *
    */
    public static function findBySite(\SqlDb $db, int $siteId, $status='active') {

        $sql = 'SELECT * FROM '.self::DB_TABLE.' WHERE site_id = '.$siteId;
        if ( $status !== null ) $sql .= ' AND status = "'.$status.'"';
        $result=$db->sql_query($sql);
        return $db->sql_fetchrowset($result);
    }
}