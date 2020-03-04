<?php
namespace im\model;

class Tenancy extends Base {

    const DB_TABLE = 'tenancy';
    const PRIMARY_KEYS = ['tenancy_id'];
    const DB_LISTS = [
        1 => ['motw'=>'Motw', 'other'=>'Other'],
        2 => ['proposed'=>'Proposed', 'confirmed'=>'Confirmed', 'cancelled'=>'Cancelled', 'paid'=>'Paid', 'payment_pending'=>'Payment_pending', 'written_off'=>'Written_off', 'completed'=>'Completed'],
        3 => ['click'=>'Click', 'other'=>'Other'],
    ];

    const DB_MODEL = [
        'tenancy_id'          => ["type"=>"key"],
        'type'                => ["type"=>"txt", "default"=>"motw", "list"=>1],
        'fee'                 => ["type"=>"num", "scale"=>2],
        'notes'               => ["type"=>"txt"],
        'site_id'             => ["type"=>"num", "required"=>true],
        'item_id'             => ["type"=>"num"],
        'start_date'          => ["type"=>"dat", "required"=>true],
        'end_date'            => ["type"=>"dat", "required"=>true],
        'status'              => ["type"=>"txt", "default"=>"confirmed", "list"=>2],
        'reward_type'         => ["type"=>"txt", "default"=>"click", "list"=>3],
        'post_id'             => ["type"=>"num"],
        'commission_group_id' => ["type"=>"num"],
        'aff_id'              => ["type"=>"num"],
        'trans_id'            => ["type"=>"num"],
        'invoice_id'          => ["type"=>"num"],
        'clicks'              => ["type"=>"num"],
        'unique_clicks'       => ["type"=>"num"],
    ];

    protected function toFinalise() {
        return $this->find([
            'status'=>'confirmed',
            'end_date'=>[
                'lt'=>date('Y-m-d'),
                'gt'=>date('Y-m-d',strtotime('-180 days')),
            ],
        ]);
    }

    public function finalise() {
        $rows = $this->toFinalise();
        $count=0;
        foreach($rows as $row) {
            $sql = 'SELECT count(*) as clicks, COUNT(DISTINCT user_id) as unique_clicks '
            .' FROM '.Click::DB_TABLE
            .' WHERE site_id = '.$row['site_id']
            .' AND click_time BETWEEN "'.$row['start_date'].'" AND "'.$row['end_date'].'"';
            $result = $this->db->sql_query($sql);
            $sum = $this->db->sql_fetchrow($result);
            if ( isset($sum['clicks']) ) {
                $this->loadDbData($row);
                $this->update([
                    'status'        => 'completed',
                    'clicks'        => $sum['clicks'],
                    'unique_clicks' => $sum['unique_clicks'],
                ]);
                $count++;
            }
        }
        return $count;
    }
}