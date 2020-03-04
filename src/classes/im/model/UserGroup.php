<?php
namespace im\model;

class UserGroup extends Base {

    const DB_TABLE = DB_FORUM_NAME.'.phpbb_user_group';
    const PRIMARY_KEYS = ['group_id', 'user_id'];

    const DB_MODEL = [
        'group_id'      => ["type"=>"key"],
        'user_id'       => ["type"=>"key"],
        'group_leader'  => ["type"=>"num", "default"=>0],
        'user_pending'  => ["type"=>"num", "default"=>0],
    ];

}