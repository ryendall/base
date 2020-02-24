<?php
namespace im\model;

class ApiUsers extends Base {

    const DB_TABLE = 'api_users';
    const PRIMARY_KEYS = ['user_id'];
    const TITLE_FIELD = 'username';
    const DB_MODEL = [
        'user_id'  => ["type"=>"key"],
        'username' => ["type"=>"txt", "required"=>true],
        'password' => ["type"=>"txt"],
    ];
}