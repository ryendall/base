<?php
namespace im\model;

class Family extends Base {

    const DB_TABLE = 'family';
    const PRIMARY_KEYS = ['id'];
    const TITLE_FIELD = 'reference';
    const DB_MODEL = [
        'id'            => ["type"=>"key"],
        'reference'     => ["type"=>"txt", "required"=>true, 'unique'=>true, 'max'=>32],
        'name'          => ["type"=>"txt", "required"=>true, 'unique'=>true, 'max'=>255],
        'segment_id'    => ["type"=>"num", "required"=>true, 'class'=>'Segment'],
    ];
}