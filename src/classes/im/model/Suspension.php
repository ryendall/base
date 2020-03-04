<?php
namespace im\model;

class Suspension extends Base {

    const DB_TABLE = 'm_suspended';
    const PRIMARY_KEYS = ['suspended_id'];
    const DB_LISTS = [
        1 => ['true'=>'True', 'false'=>'False'],
        2 => ['pending'=>'Pending', 'lifted'=>'Lifted', 'permanent'=>'Permanent', 'other'=>'Other'],
    ];

    const DB_MODEL = [
        'suspended_id' => ["type"=>"key"],
        'sdate'        => ["type"=>"dat", "default"=>'{NOW}'],
        'message'      => ["type"=>"txt", "required"=>true],
        'redeem_ban'   => ["type"=>"txt", "default"=>"true", "list"=>1],
        'click_ban'    => ["type"=>"txt", "default"=>"false", "list"=>1],
        'site_id'      => ["type"=>"num"],
        'admin_id'     => ["type"=>"num"],
        'reason'       => ["type"=>"txt"],
        'status'       => ["type"=>"txt", "default"=>"pending", "list"=>2],
        'user_id'      => ["type"=>"num", "required"=>true],
        'last_updated' => ["type"=>"dat", "default"=>'{NOW}'],
        'content_ban'  => ["type"=>"txt", "default"=>"false", "list"=>1],
    ];
}