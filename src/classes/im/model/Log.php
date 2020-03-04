<?php
namespace im\model;

class Log extends Base {

    const DB_TABLE = 'm_log';
    const PRIMARY_KEYS = ['log_id'];
    const DB_MODEL = [
        'log_id'      => ["type"=>"key"],
        'activity_id' => ["type"=>"num", 'required'=>true],
        'user_id'     => ["type"=>"num"],
        'atime'       => ["type"=>"num"],
        'reference'   => ["type"=>"num"],
    ];

    protected $user_id;

    public function setUserId(int $user_id) {
        $this->user_id = $user_id;
    }

    public function getUserId() {
        return $this->user_id;
    }

    public function create(array $data = null, bool $exceptionOnError=false) {
        if ( $data ) {
            $data['user_id'] = $this->getUserId();
            $data['atime'] = time();
        }
        if ( !$log_id = parent::create($data) ) return false;
        return $log_id;
    }
}