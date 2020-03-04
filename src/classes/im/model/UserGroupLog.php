<?php
namespace im\model;

class UserGroupLog extends Base {

    const DB_TABLE = 'user_group_log';
    const PRIMARY_KEYS = ['id'];
    const DB_LISTS = [
        1 => ['add'=>'Add', 'remove'=>'Remove', 'refuse'=>'Refuse'],
    ];

    const DB_MODEL = [
        'id'          => ["type"=>"key"],
        'action'      => ["type"=>"txt", "required"=>true, "list"=>1],
        'user_id'     => ["type"=>"num", "required"=>true],
        'group_id'    => ["type"=>"num", "required"=>true],
        'action_date' => ["type"=>"dat", "default"=>'{NOW}'],
        'bb_user_id'  => ["type"=>"num"],
    ];
}