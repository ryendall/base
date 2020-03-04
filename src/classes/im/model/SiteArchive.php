<?php
namespace im\model;

class SiteArchive extends Base {

    const DB_TABLE = 'site_archive';
    const PRIMARY_KEYS = ['site_id'];
    const DB_MODEL = [
        'site_id' => ["type"=>"key"],
        'title'   => ["type"=>"txt", "required"=>true],
    ];
}