<?php
namespace im\model;

class Commission extends Base {

    const DB_TABLE = 'commission';
    const PRIMARY_KEYS = ['commission_id'];
    const DB_LISTS = [
        1 => ['fixed'=>'fixed', 'percentage'=>'percentage'],
    ];
    const DB_MODEL = [
        'commission_id'		    => ['type'=>'key'],
        'program_id'		    => ['type'=>'num'],
        'revenue_type'	    	=> ['type'=>'txt', 'list' => 1],
        'revenue'       		=> ['type'=>'num'],
        'commission_code'		=> ['type'=>'txt'],
        'commission_name'		=> ['type'=>'txt'],
        'last_checked'		    => ['type'=>'dat'],
        'last_revenue_change'	=> ['type'=>'dat'],
        'last_name_change'		=> ['type'=>'dat'],
        'commission_group_id'	=> ['type'=>'num'],
        'currency_id'   		=> ['type'=>'num'],
        'product_id'	    	=> ['type'=>'num'],
        'commission_label'		=> ['type'=>'txt'],
    ];

    protected $site_id;

    public function setSiteId(int $site_id) {
        $this->site_id = $site_id;
        return $this;
    }

    protected function getSiteId() {
        return $this->site_id;
    }

    /**
     * Create new commission group based on this commission record
     * Return new group instance
     */
    public function createCommissionGroup($default_reward_label=null) {

        if ( !$this->isLoaded() ) throw new \Exception('No commission entry loaded');
        if ( !$site_id = $this->getSiteId() ) throw new \Exception('site_id not set');
        $c = $this->get();
        $name = CommissionGroup::createGroupName($c['commission_name'],$c['commission_code'],$default_reward_label);
        $cg = new CommissionGroup($this->container);
        $cg->set(['site_id'=>$site_id, 'private_name'=>$name])->create(); // create new group
        $this->set($cg->getKey())->update(); // store id against commission record
        return $cg;
    }
}