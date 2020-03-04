<?php
namespace im\model;

class ModelSite extends Base {

    const DB_TABLE = 'm_model_site';
    const PRIMARY_KEYS = ['model_id', 'site_id'];
    const DB_MODEL = [
        'model_id'    => ["type"=>"key"],
        'site_id'     => ["type"=>"key"],
        'schedule_id' => ["type"=>"num"],
    ];
}