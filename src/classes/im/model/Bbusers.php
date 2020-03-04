<?php
namespace im\model;

class Bbusers extends Base {

    const DB_TABLE = 'bb_users';
    const PRIMARY_KEYS = ['user_id'];
    const DB_LISTS = [
        1 => ['Y'=>'Yes', 'N'=>'No'],
        2 => ['yes'=>'Yes', 'no'=>'No', 'pending'=>'Pending'],
    ];

    const DB_MODEL = [
        'user_id'               => ["type"=>"key"],
        'username'              => ["type"=>"txt"],
        'user_regdate'          => ["type"=>"num"],
        'user_password'         => ["type"=>"txt"],
        'user_email'            => ["type"=>"txt"],
        'user_level'            => ["type"=>"num"],
        'name_changed'          => ["type"=>"txt", "list"=>1],
        'user_active'           => ["type"=>"num", "default"=>1],
        'mailing_sent'          => ["type"=>"txt", "default"=>"'no'", "list"=>2],
        'user_remove'           => ["type"=>"num"],
        'ruid'                  => ["type"=>"num"],
        'scheme_id'             => ["type"=>"num", "default"=>1],
        'portal_id'             => ["type"=>"num", "default"=>1],
        'iso_id'                => ["type"=>"num", "default"=>247],
        'user_ref'              => ["type"=>"txt"],
    ];
}