<?php
namespace im\model;

class TransText extends Base {

    const DB_TABLE = 'trans_text';
    const PRIMARY_KEYS = ['text_id'];
    const TITLE_FIELD = 'reference';
    const DB_LISTS = [
        1 => ['true'=>'True', 'false'=>'False'],
    ];

    const DB_MODEL = [
        'text_id'          => ["type"=>"key"],
        'aff_id'           => ["type"=>"num", 'required'=>true],
        'reference'        => ["type"=>"txt", 'required'=>true],
        'trans_text'       => ["type"=>"jsn", 'required'=>true],
        'has_visible_data' => ["type"=>"txt", "list"=>1], // @todo set() to autodetect on create/update
    ];
}