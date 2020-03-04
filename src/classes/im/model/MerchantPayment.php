<?php
namespace im\model;

class MerchantPayment extends Base {

    const DB_TABLE = 'merchant_payment';
    const PRIMARY_KEYS = ['id'];
    const TITLE_FIELD = 'reference';
    const DB_LISTS = [
        1 => ['pending'=>'Pending', 'complete'=>'Complete', 'error'=>'Error'],
    ];

    const DB_MODEL = [
        'id'                 => ["type"=>"key"],
        'reference'          => ["type"=>"num", "required"=>true],
        'network_payment_id' => ["type"=>"num", "required"=>true],
        'aff_id'             => ["type"=>"num", "required"=>true],
        'merchant_id'        => ["type"=>"num", "required"=>true],
        'report_time'        => ["type"=>"dat", 'default'=>'{NOW}'],
        'status'             => ["type"=>"txt", "default"=>"pending", "list"=>1],
        'transactions'       => ["type"=>"num"],
        'amount'             => ["type"=>"num", "scale"=>2],
        'expected_amount'    => ["type"=>"num", "scale"=>2],
        'commissions'        => ["type"=>"num", "scale"=>2],
        'vat'                => ["type"=>"num", "scale"=>2],
        'details'            => ["type"=>"txt", "input"=>"textarea"],
    ];
}