<?php
namespace im\model;

class Users {

    const DB_TABLE = Bbusersx::DB_TABLE;

    /**
     * Return total payable balance in pence for users who qualify for Faster Payments
     * @param $db
     * @param int $ecashierId
     * @param int $status
     * @return float $amount
    */
    public static function redeemableByFasterPayments(\SqlDb $db) {

        $pence=0;
        // Must have used FP in past 180 days
        $sql = 'select distinct user_id from '.Payment::DB_TABLE.' where ecashier_id = 2 AND redeem_time > unix_timestamp()-(180*86400)';
        $result=$db->sql_query($sql);
        $users = $db->sql_fetchrowset($result);
        foreach($users as $row) {
            $pence += self::getRedeemableBalance($db, $row['user_id']);
        }
        return round($pence/100,2);
    }

    public static function getRedeemableBalance(\SqlDb $db, int $userId) {

        $pence=0;
        $sql = 'SELECT rpoints FROM '.self::DB_TABLE.' WHERE suspended_id IS NULL AND user_id = '.$userId;
        $result=$db->sql_query($sql);
        if ( $row = $db->sql_fetchrow($result) ) {
            $pence += min($row['rpoints'],10000); // max is £50x2days
        }
        return $pence;
    }
}