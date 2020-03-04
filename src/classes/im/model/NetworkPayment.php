<?php
namespace im\model;

class NetworkPayment extends Base {

    const DB_TABLE = 'network_payment';
    const PRIMARY_KEYS = ['id'];
    const TITLE_FIELD = 'reference';
    const DB_LISTS = [
        1 => ['pending'=>'Pending', 'complete'=>'Complete', 'error'=>'Error'],
        2 => ['pending'=>'Pending', 'paid'=>'Paid', 'cancelled'=>'Cancelled'],
    ];

    const DB_MODEL = [
        'id'              => ["type"=>"key"],
        'aff_id'          => ["type"=>"num", "required"=>true],
        'payment_date'    => ["type"=>"dat", "default"=>"2017-01-01"],
        'report_time'     => ["type"=>"dat", 'default'=>'{NOW}'],
        'status'          => ["type"=>"txt", "default"=>"pending", "list"=>1],
        'transactions'    => ["type"=>"num"],
        'amount'          => ["type"=>"num", "scale"=>2],
        'expected_amount' => ["type"=>"num", "scale"=>2],
        'vat'             => ["type"=>"num", "scale"=>2],
        'currency_id'     => ["type"=>"num", "default"=>1],
        'reference'       => ["type"=>"num"],
        'payment_status'  => ["type"=>"txt", "default"=>"paid", "list"=>2],
        'details'         => ["type"=>"text", 'input'=>'textarea'],
    ];

    public function fetchLatestForNetwork(int $aff_id) {
        $sql = 'SELECT id FROM '.self::DB_TABLE.' WHERE aff_id = '.$aff_id.' ORDER BY payment_date DESC LIMIT 1';
        $result = $this->db->sql_query($sql);
        if ( $row=$this->db->sql_fetchrow($result) ) {
            return $this->read($row['id']);
        } else {
            return false;
        }
    }

}