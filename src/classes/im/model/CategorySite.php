<?php
namespace im\model;

class CategorySite extends Base {

    const DB_TABLE = 'category_site';
    const PRIMARY_KEYS = ['id'];
    const DB_LISTS = [
        1 => ['active'=>'Active', 'inactive'=>'Inactive'],
    ];

    const DB_MODEL = [
        'id'          => ["type"=>"key"],
        'site_id'     => ["type"=>"num", "required"=>true],
        'category_id' => ["type"=>"num", "required"=>true],
        'list_order'  => ["type"=>"num", "default"=>100000],
        'status'      => ["type"=>"txt", "default"=>"active", "list"=>1],
    ];
}