<?php
namespace im\model;

class SiteConditions extends Base {

    const DB_TABLE = 'site_conditions';
    const PRIMARY_KEYS = ['site_conditons_id'];
    const DB_LISTS = [
        1 => ['false'=>'No', 'true'=>'Yes'],
    ];

    const DB_MODEL = [
        'site_conditons_id'   => ["type"=>"key"],
        'site_id'             => ["type"=>"num", "required"=>true],
        'start_date'          => ["type"=>"dat", "default"=>'{NOW}'],
        'acceptance_required' => ["type"=>"txt", "default"=>"false", "list"=>1],
        'show_always'         => ["type"=>"txt", "default"=>"false", "list"=>1],
        'conditions'          => ["type"=>"txt", "required"=>true],
    ];
}