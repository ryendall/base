<?php
namespace im\model;

class Transaction extends Base {

    const DB_TABLE = 'vtrans';
    const PRIMARY_KEYS = ['vtrans_id'];
    const STATUS = [
        'claim_pending'     => 5,
        'provisional'       => 10,
        'extended'          => 15,
        'pending'           => 20,
        'merchant_paid'     => 25,
        'awarded'           => 30,
        'capped'            => 38,
        'rejected'          => 40,
        'rejected_hidden'   => 42,
        'claim_rejected'    => 45,
        'deleted'           => 60,
        'duplicate'         => 65,
    ];

    const DB_LISTS = [
		1 => [
            5  => 'Claim pending',
            10 => 'Provisional',
            15 => 'Extended',
            20 => 'Pending',
            25 => 'Merchant paid',
            30 => 'Awarded',
            38 => 'Capped',
            40 => 'Rejected',
            42 => 'Rejected(hidden)',
            45 => 'Claim rejected',
            60 => 'Deleted',
        ],
	];

    const DB_MODEL = [
        'vtrans_id'        => ["type"=>"key"],
        'user_id'          => ["type"=>"num", "required"=>true, 'class'=>'Bbusersx'],
        'site_id'          => ["type"=>"num", "required"=>true, 'class'=>'Site'],
        'vstatus_id'       => ["type"=>"num", "list"=>1, "required"=>true, 'onChange'=>'userRecalc'],
        'ovalue'           => ["type"=>"num", "default"=>0],
        'rpoints'          => ["type"=>"num", "default"=>0, 'onChange'=>'userRecalc'],
        'trans_date'       => ["type"=>"uts", "required"=>true],
        'last_updated'     => ["type"=>"uts"],
        'revenue'          => ["type"=>"num"],
        'award_day'        => ["type"=>"dat"],
        'aff_id'           => ["type"=>"num", "required"=>true, 'class'=>'Network'],
        'click_id'         => ["type"=>"num"],
        'shares'           => ["type"=>"num", 'onChange'=>'userRecalc'],
        'item_id'          => ["type"=>"num", 'class'=>'ScheduleItem'],
        'text_id'          => ["type"=>"num"],
        'transactions'     => ["type"=>"num", "default"=>1, 'onChange'=>'userRecalc'],
    ];

    protected $network;

    protected $reference;

    protected function load() {
        parent::load();
        $this->network = new Network($this->container, $this->get('aff_id'));
        $this->itemClass = 'TransactionItem'.$this->get('aff_id');
    }

    public function getNetwork() {
        return $this->network;
    }

    public function getReference() {
        return $this->reference;
    }

    public function award() {
        return $this->update([
            'vstatus_id'    => self::STATUS['awarded'],
            'award_day'     => date('Y-m-d'),
            'last_updated'  => time(),
        ]);
    }

    public function reject() {
        return $this->update([
            'vstatus_id'    => self::STATUS['rejected'],
            'last_updated'  => time(),
        ]);
    }

    protected function userRecalc() {
        // Ensure transaction count is correct
        if ( $this->get('vstatus_id') > 35 || $this->get('rpoints') == 0 ) {
            // rejected / zero cashback. Transaction count should be zero
            $this->update(['transactions'=>0]);
        } elseif ( $this->get('transactions') == 0 ) {
            // valid cashback. Transaction count should be 1
            $this->update(['transactions'=>1]);
        }
        $this->getObject('user_id')->checkBalance();
    }

    protected function setReference($reference) {
        $this->reference = $reference;
    }

    /**
     * Delete existing transaction and reset items for re-processing
     */
    public function resetTransaction() {
        $items = $this->getItems();
        $last_item_id = count($items)-1;

        $item = new $this->itemClass($this->container);
        foreach($items as $idx=>$data) {
            $item->read($data['trans_id']);
            if ( $idx == $last_item_id ) {
                // This is latest, reset status
                $item->update([
                    'pstatus_id'=>0,
                    'vtrans_id'=>null,
                    'process_time'=>null,
                ]);
                $this->setReference($item->get('reference'));
            } else {
                // Earlier update, just set it to "ignored"
                $item->update([
                    'pstatus_id'=>5,
                    'vtrans_id'=>null,
                    'process_time'=>null,
                ]);
            }
        }
        $this->update([
            'vstatus_id'=>60,
            'last_updated'=>time(),
        ]);
    }

    public function getItems(bool $networkOnly = true) {
        $sql = 'SELECT * FROM '.$this->network->getTransTable().' WHERE vtrans_id = '.$this->get('vtrans_id').' ORDER BY report_time';
        $result = $this->db->sql_query($sql);
        return $this->db->sql_fetchrowset($result);
    }


}