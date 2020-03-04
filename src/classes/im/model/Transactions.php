<?php
namespace im\model;

class Transactions {

    const DB_TABLE = 'vtrans';

    /**
     * For a given user, return summary of transaction totals by status
     * @param $db
     * @param int $userId
     * @return array $results
     * format of $results: {
     *  "pending": 123,
     *  "awarded": 456
     * }
     *
    */
    public static function forUserbyStatus(\SqlDb $db, int $userId) {

        $results = ['rpoints_pending'=>0, 'rpoints_earned'=>0, 'shares_pending'=>0, 'shares_awarded'=>0, 'total_transactions'=>0];
        $sql = 'SELECT SUM(transactions) as transactions, SUM(rpoints) as pence, SUM(shares) as shares, vstatus_id FROM '.self::DB_TABLE.' WHERE user_id = '.$userId.' GROUP BY vstatus_id';
        $result=$db->sql_query($sql);
        $rows=$db->sql_fetchrowset($result);

        foreach($rows as $row) {
            if ( $row['vstatus_id'] < 30 ) {
                $results['rpoints_pending'] += $row['pence'];
                $results['shares_pending'] += $row['shares'];
                $results['total_transactions'] += $row['transactions'];
            } elseif ( $row['vstatus_id'] == 30 ) {
                $results['rpoints_earned'] += $row['pence'];
                $results['shares_awarded'] += $row['shares'];
                $results['total_transactions'] += $row['transactions'];
            }
        }
        return $results;

    }
}