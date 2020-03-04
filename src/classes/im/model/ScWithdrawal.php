<?php
namespace im\model;

class ScWithdrawal extends Base {

    const DB_TABLE = 'sc_withdrawal';
    const PRIMARY_KEYS = ['id'];
    const DB_LISTS = [
        1 => ['pending'=>'Pending', 'sent'=>'Sent', 'cancelled'=>'Cancelled'],
    ];

    const DB_MODEL = [
        'id'            => ["type"=>"key"],
        'user_id'       => ["type"=>"num", "required"=>true],
        'request_date'  => ["type"=>"dat", "default"=>'{NOW}'],
        'units'         => ["type"=>"num", "required"=>true],
        'status'        => ["type"=>"txt", "default"=>"pending", "list"=>1],
        'amount'        => ["type"=>"num", "scale"=>2],
        'investment_id' => ["type"=>"num"],
    ];

    protected function headerLinks() {
        $links = parent::headerLinks();
        if ( $this->get('status') == 'pending' ) {
            $links[] = ['label'=>'Convert','url'=>'sc_withdrawal.php?w='.$this->get('id')];
        }
        return $links;
    }
}