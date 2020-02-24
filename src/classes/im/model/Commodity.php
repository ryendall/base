<?php
namespace im\model;

class Commodity extends Base {

    const DB_TABLE = 'commodity';
    const PRIMARY_KEYS = ['id'];
    const TITLE_FIELD = 'reference';
    const DB_MODEL = [
        'id'                    => ["type"=>"key"],
        'reference'             => ["type"=>"txt", "required"=>true, 'unique'=>true, 'max'=>32],
        'name'                  => ["type"=>"txt", "required"=>true, 'unique'=>true, 'max'=>255],
        'commodity_class_id'    => ["type"=>"num", 'class'=>'CommodityClass', "required"=>true],
    ];
}