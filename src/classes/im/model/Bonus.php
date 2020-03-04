<?php
namespace im\model;

class Bonus extends Base {

    const DB_TABLE = 'bonus';
    const PRIMARY_KEYS = ['vtrans_id'];
    const DB_LISTS = [
        1 => ['pending'=>'Pending', 'awarded'=>'Awarded', 'rejected'=>'Rejected'],
    ];

    const DB_MODEL = [
        'vtrans_id'       => ["type"=>"key"],
        'bonus_vtrans_id' => ["type"=>"num", "required"=>true, 'class'=>'Transaction'],
        'status'          => ["type"=>"txt", "default"=>"pending", "list"=>1],
    ];

    public function award() {
        $this->getObject('bonus_vtrans_id')->update(['vstatus_id'=>30, 'award_day'=>date('Y-m-d')]);
        $this->update(['status'=>'awarded']);
    }

    public function reject() {
        $trans = $this->getObject('bonus_vtrans_id');
        if ( $trans->get('vstatus_id') == 30 ) {
            trigger_error('Cannot reject vtrans_id '.$this->get('bonus_vtrans_id').'; already awarded');
            return false;
        }
        $trans->update(['vstatus_id'=>40]);
        $this->update(['status'=>'rejected']);
    }
}