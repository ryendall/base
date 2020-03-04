<?php
namespace im\model;

class TransactionItem113 extends TransactionItem {

    const DB_TABLE = 'm_trans_113';
    const DB_MODEL = [
        'trans_id'        => ["type"=>"key"],
        'user_id'         => ["type"=>"num"],
        'trans_time'      => ["type"=>"num"],
//        'ruid'            => ["type"=>"num"],
        'tstatus_id'      => ["type"=>"num", 'required'=>true],
        'pstatus_id'      => ["type"=>"num"],
        'points'          => ["type"=>"num"],
        'link_id'         => ["type"=>"num"],
        'site_id'         => ["type"=>"num"],
        'reference'       => ["type"=>"txt", 'required'=>true],
        'ovalue'          => ["type"=>"num"],
        'revenue'         => ["type"=>"num"],
        'vtrans_id'       => ["type"=>"num"],
        'click_id'        => ["type"=>"num"],
        'oor'             => ["type"=>"num"],
        'report_time'     => ["type"=>"dat", 'default'=>'{NOW}'],
        'error_id'        => ["type"=>"num"],
        'claim_id'        => ["type"=>"num"],
        'admin_id'        => ["type"=>"num"],
        'process_time'    => ["type"=>"dat"],
        'aff_id'          => ["type"=>"num", 'required'=>true],
        'shares'          => ["type"=>"num"],
        'item_id'         => ["type"=>"num"],
        'text_id'         => ["type"=>"num", "required"=>true],
        'promoter_shares' => ["type"=>"num"],
//        'ip'              => ["type"=>"num", "required"=>true],
        'url_tag_id'      => ["type"=>"num"],
        'merchant_payment_id' => ["type"=>"num"],
    ];

}