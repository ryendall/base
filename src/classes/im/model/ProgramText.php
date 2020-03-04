<?php
namespace im\model;

class ProgramText extends Base {

    const DB_TABLE = 'program_text';
    const PRIMARY_KEYS = ['program_id'];
    const DB_MODEL = [
        'program_id'       => ["type"=>"key"],
        'description'      => ["type"=>"txt"],
        'offer'            => ["type"=>"txt"],
        'other'            => ["type"=>"txt"],
        'last_updated'     => ["type"=>"dat", 'default'=>'{NOW}'],
        'meta_tags'        => ["type"=>"txt"],
        'meta_title'       => ["type"=>"txt"],
        'meta_description' => ["type"=>"txt"],
        'meta_properties'  => ["type"=>"txt"],
        'meta_keywords'    => ["type"=>"txt"],
    ];
}