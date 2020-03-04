<?php
namespace im\model;

class SiteLink extends Base {

    const DB_TABLE = 'site_link';
    const PRIMARY_KEYS = ['id'];
    const DB_LISTS = [
        1 => ['active'=>'Active', 'inactive'=>'Inactive'],
    ];

    const DB_MODEL = [
        'id'          => ["type"=>"key"],
        'site_id'     => ["type"=>"num", "required"=>true],
        'url'         => ["type"=>"txt"],
        'title'       => ["type"=>"txt"],
        'list_order'  => ["type"=>"num", "default"=>8000],
        'status'      => ["type"=>"txt", "default"=>"active", "list"=>1],
        'category_id' => ["type"=>"num"],
    ];
}