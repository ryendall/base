<?php
namespace im\model;

class PortalSite extends Base {

    const DB_TABLE = 'm_portal_site';
    const PRIMARY_KEYS = ['portal_id', 'site_id'];
    const DB_LISTS = [
        1 => ['new'=>'New', 'exclude'=>'Exclude', 'include'=>'Include'],
    ];

    const DB_MODEL = [
        'portal_id'  => ["type"=>"key"],
        'site_id'    => ["type"=>"key"],
        'status'     => ["type"=>"txt", "default"=>"new", "list"=>1],
        'list_order' => ["type"=>"num", "default"=>100],
    ];
}