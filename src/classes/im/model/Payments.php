<?php
namespace im\model;
use DI\Container;

class Payments extends BaseLite {

    const DB_TABLE = Payment::DB_TABLE;
    protected $container;
    protected $db;

    function __construct(Container $container) {
        $this->container = $container;
        $this->db = $container->get('db');
    }

    /**
     * For a given user, return amount redeemed in pence
     * @param int $userId
     * @return int $pence
    */
    public function totalForUser(int $userId) {

        $pence = 0;
        $sql = 'SELECT SUM(points) as pence FROM '.self::DB_TABLE.' WHERE user_id = '.$userId.' AND status <= 35';
        $result=$this->db->sql_query($sql);
        if ( $row=$this->db->sql_fetchrow($result) ) {
            $pence = $row['pence'];
        }
        return $pence;
    }

    /**
     * For a given payment method and status (default=pending), return value of payments in pounds
     * @param int $ecashierId
     * @param int $status
     * @return float $amount
    */
    public function totalForMethod(int $ecashierId, int $status = Payment::STATUS['pending']) {

        $pence = 0;
        $sql = 'SELECT SUM(points) as pence FROM '.self::DB_TABLE.' WHERE ecashier_id = '.$ecashierId.' AND status = '.$status;
        $result=$this->db->sql_query($sql);
        if ( $row=$this->db->sql_fetchrow($result) ) {
            $pence = $row['pence'];
        }
        return round($pence/100,2);
    }

    public function autoredeem() {
        $ecashiers = [2,7];
        $now=time();

        foreach($ecashiers as $ecashier_id) {
            $sql = 'SELECT x.rpoints AS balance, r.portal_id, r.user_id, r.auto_redeem, max(r2.redeem_id) as latest, r.account_id, r.user_reference FROM '.self::DB_TABLE.' r, '.self::DB_TABLE.' r2, bb_users_x x WHERE r.auto_redeem > 0 AND x.rpoints >= r.auto_redeem*100 AND r.ecashier_id = '.$ecashier_id.' and r.user_id = r2.user_id and r.status between 30 and 35 AND r.user_id = x.user_id AND x.suspended_id IS NULL GROUP BY r.redeem_id HAVING r.redeem_id = latest';
            $rows=$this->sql_rowset($sql);
            foreach($rows as $r) {
                $usql = 'INSERT INTO '.self::DB_TABLE.' (redeem_time,points,user_id,portal_id,status,ecashier_id,account_id,auto_redeem, user_reference) VALUES ('.$now.', '.$r['balance'].', '.$r['user_id'].', '.$r['portal_id'].', 10, '.$ecashier_id.', '.$r['account_id'].', '.$r['auto_redeem'].', "'.$r['user_reference'].'")';
                $uresult=$this->sql_query($usql);

                $usql = 'UPDATE bb_users_x SET rpoints = rpoints - '.$r['balance'].', rpoints_redeemed = rpoints_redeemed + '.$r['balance'].' WHERE user_id = '.$r['user_id'];
                $uresult=$this->sql_query($usql);
            }
        }
        return count($rows);
    }
}