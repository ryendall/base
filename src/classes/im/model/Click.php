<?php
namespace im\model;

class Click extends Base {

    const DB_TABLE = 'click';
    const PRIMARY_KEYS = ['click_id'];
    const DB_MODEL = [
        'click_id'   => ["type"=>"key"],
        'click_time' => ["type"=>"dat", "default"=>"0000-00-00 00:00:00"],
        'portal_id'  => ["type"=>"num"],
        'site_id'    => ["type"=>"num"],
        'user_id'    => ["type"=>"num"],
        'offer_id'   => ["type"=>"num"],
        'ruid'       => ["type"=>"num"],
        'aff_id'     => ["type"=>"num"],
        'ip'         => ["type"=>"num"],
        'ben_id'     => ["type"=>"num"],
        'url_tag_id' => ["type"=>"num"],
    ];
}