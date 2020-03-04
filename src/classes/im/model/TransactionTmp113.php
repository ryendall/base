<?php
namespace im\model;

class TransactionTmp113 extends TransactionTmp {

    const DB_TABLE = 'm_trans_113tmp';
    const DB_MODEL = [
        'reference'             => ["type"=>"txt"],
        'user_id'               => ["type"=>"num"],
        'link_id'               => ["type"=>"num"],
        'trans_time'            => ["type"=>"num"],
        'click_id'              => ["type"=>"num"],
        'revenue'               => ["type"=>"num"],
        'ovalue'                => ["type"=>"num", "scale"=>2],
        'tstatus_id'            => ["type"=>"num"],
        'status'                => ["type"=>"num"],
        'error_id'              => ["type"=>"num"],
        'claim_id'              => ["type"=>"num"],
        'merchant_payment_id'   => ["type"=>"num"],
        'trans_text'            => ["type"=>"txt", "input"=>"textarea"],
    ];
}
