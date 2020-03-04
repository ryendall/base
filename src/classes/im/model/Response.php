<?php
namespace im\model;

class Response extends Base {

    const DB_TABLE = 'm_response';
    const PRIMARY_KEYS = ['response_id'];
	const DB_LISTS = [
		1 => [10=>'Claims', 20=>'Queries', 25=>'Suggestions', 30=>'HCP', 35=>'Link errors', 40=>'Regexp', 50=>'Other', 60=>'Affiliates', 70=>'Suspension', 45=>'Email', 80=>'Conditions'],
    ];

    const DB_MODEL = [
        'response_id'  => ["type"=>"key"],
        'type_id'      => ["type"=>"num", "required"=>true],
        'title'        => ["type"=>"txt", "required"=>true],
        'response_1'   => ["type"=>"txt", "required"=>true],
        'list_order'   => ["type"=>"num", "default"=>9999],
        'click_value'  => ["type"=>"num"],
        'extra_values' => ["type"=>"txt"],
        'user_id'      => ["type"=>"num"],
        'created'      => ["type"=>"dat", "default"=>'{NOW}'],
        'portal_id'    => ["type"=>"num"],
    ];
}