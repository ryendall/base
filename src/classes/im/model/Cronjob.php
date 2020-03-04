<?php
namespace im\model;

class Cronjob extends Base {

    const DB_TABLE = 'cronjob';
    const PRIMARY_KEYS = ['cronjob_id'];
    const TITLE_FIELD = 'title';
    const DB_LISTS = [
        1 => ['every5mins'=>'Every5mins', 'every10mins'=>'Every10mins', 'hourly'=>'Hourly', 'daily'=>'Daily', 'breakfast'=>'Breakfast', 'lunchtime'=>'Lunchtime', 'teatime'=>'Teatime', 'weekly'=>'Weekly', 'monthly'=>'Monthly', 'other'=>'Other'],
        2 => ['active'=>'Active', 'inactive'=>'Inactive', 'auto'=>'Auto'],
    ];

    const DB_MODEL = [
        'cronjob_id'   => ["type"=>"key"],
        'title'        => ["type"=>"txt"],
        'short_title'  => ["type"=>"txt"],
        'frequency'    => ["type"=>"txt", "input"=>"textarea", "list"=>1],
        'status'       => ["type"=>"txt", "input"=>"textarea", "default"=>"inactive", "list"=>2],
        'last_started' => ["type"=>"dat"],
        'last_updated' => ["type"=>"dat"],
    ];
}