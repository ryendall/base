<?php
namespace im\model;

class ScheduleReward extends Base {

	const DB_TABLE = 'schedule_reward';
	const PRIMARY_KEYS = ['model_id', 'item_id'];
	const DB_LISTS = [
		1 => ['visible'=>'visible', 'hidden'=>'hidden'],
	];

	const DB_MODEL = [
        'model_id'		=> ['type'=>'key'],
        'item_id'		=> ['type'=>'key'],
        'reward'		=> ['type'=>'num', 'default'=>0],
        'view_status'	=> ['type'=>'txt', 'default'=>'visible', 'list' => 1],
        'shares'		=> ['type'=>'num', 'required' => true],
        'per_amount'	=> ['type'=>'num', 'required' => true],
    ];

}