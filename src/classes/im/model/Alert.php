<?php
namespace im\model;

class Alert extends Base {

    const DB_TABLE = 'alert';
    const PRIMARY_KEYS = ['alert_id'];
    const DB_LISTS = [
        1 => ['active'=>'Active', 'inactive'=>'Inactive'],
    ];

    const DB_MODEL = [
        'alert_id'    => ["type"=>"key"],
        'title'       => ["type"=>"txt", "required"=>true],
        'short_title' => ["type"=>"txt"],
        'user_id'     => ["type"=>"num", "default"=>450],
        'details'     => ["type"=>"txt"],
        'status'      => ["type"=>"txt", "default"=>"inactive", "list"=>1],
        'url'         => ["type"=>"txt"],
        'last_active' => ["type"=>"dat"],
    ];

    public function turnOn(string $details) {
        $this->update([
            'status'        => 'active',
            'last_active'   => date('Y-m-d H:i:s'),
            'details'       => $details,
        ], true);
    }

    public function turnOff() {
        $this->update(['status' => 'inactive'], true);
    }
}