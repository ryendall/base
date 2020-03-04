<?php
namespace im\model;

class ClaimReferenceSite extends Base {

    const DB_TABLE = 'claim_reference_site';
    const PRIMARY_KEYS = ['ref_id'];
    const DB_LISTS = [
        1 => ['true'=>'Yes', 'false'=>'No'],
    ];

    const DB_MODEL = [
        'ref_id'        => ["type"=>"key"],
        'site_id'       => ["type"=>"num", "required"=>true],
        'question'      => ["type"=>"txt", "required"=>true],
        'validation'    => ["type"=>"txt"],
        'unique'        => ["type"=>"txt", "default"=>"false", "list"=>1],
        'help'          => ["type"=>"txt"],
        'error'         => ["type"=>"txt"],
        'compulsory'    => ["type"=>"txt", "default"=>"true", "list"=>1],
        'validation_id' => ["type"=>"num"],
        'order'         => ["type"=>"num"],
        'field_id'      => ["type"=>"num"],
    ];
}