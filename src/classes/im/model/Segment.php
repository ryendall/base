<?php
namespace im\model;

class Segment extends Base {

    const DB_TABLE = 'segment';
    const PRIMARY_KEYS = ['id'];
    const TITLE_FIELD = 'reference';
    const DB_MODEL = [
        'id'        => ["type"=>"key"],
        'reference' => ["type"=>"txt", "required"=>true, 'unique'=>true, 'max'=>32],
        'name'      => ["type"=>"txt", "required"=>true, 'unique'=>true, 'max'=>255],
    ];
}