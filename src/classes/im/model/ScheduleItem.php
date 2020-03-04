<?php
namespace im\model;

class ScheduleItem extends Base {

    const DB_TABLE = 'schedule_item';
    const PRIMARY_KEYS = ['item_id'];
    const TITLE_FIELD = 'public_name';
    const DB_LISTS = [
        1 => ['fixed'=>'Fixed', 'percentage'=>'Percentage'],
        2 => ['active'=>'Active', 'inactive'=>'Inactive'],
    ];
    const DB_MODEL = [
        'item_id'		        => ['type'=>'key'],
        'site_id'		        => ['type'=>'num'],
        'commission_group_id'	=> ['type'=>'num', 'class'=>'CommissionGroup'],
        'start_date'		    => ['type'=>'dat'],
        'end_date'		        => ['type'=>'dat', 'default'=>'{NOW}'],
        'revenue_type'		    => ['type'=>'txt', 'default'=>'fixed', 'list' => 1],
        'revenue'		        => ['type'=>'num', 'default'=>0],
        'public_name'		    => ['type'=>'txt'],
        'priority'		        => ['type'=>'num'],
        'status'		        => ['type'=>'txt', 'default'=>'inactive', 'list' => 2],
        'sort_order'		    => ['type'=>'num'],
    ];

    protected $cg; // CommissionGroup instance

    public function create(array $data=null, bool $exceptionOnError=false) {
        if ($data) $this->set($data);
        if ( !$this->get('sort_order') ) $this->setDefaultSortOrder();
        if ( !parent::create() ) return false;
    }

    protected function setDefaultSortOrder() {
        $this->set(['sort_order'=>$this->get('commission_group_id')]);
    }

    protected function getCommissionGroup() {
        return $this->getObject('commission_group_id');
    }

    /**
     * Create new reward based on this record
     */
    public function createScheduleReward(int $model_id=2) {

        if ( !$this->isLoaded() ) throw new \Exception('No record loaded');
        $si = $this->get();

        // insert directly, schedule_reward has compound pk
        $reward = [
            'model_id'=>$model_id,
            'item_id'=>$this->getKey()['item_id'],
            'view_status'=>$this->getCommissionGroup()->get('default_view_status'),
        ] + $this->calculateReward($model_id);

        $sr = new ScheduleReward($this->container);
        $sr->set($reward)->create();
    }

    protected function calculateReward($model_id) {
        $reward = $shares = $per_amount = 0;

        $cg = $this->getCommissionGroup()->get();
        $revenue = $this->get('revenue');
        $revenue_type = $this->get('revenue_type');

        if ( $model_id == 2 ) {
            // imutual
            $reward = $revenue;
            if ( $revenue_type == 'percentage' ) {
                if ( $revenue < 5 ) {
                    $reward = $revenue+0.1; // if <5% only add 0.1% to our most popular merchants
                } elseif ( $revenue < 10 ) {
                    // if 5-9%, round up to nearest 0.5% e.g. 8.7% rev => 9% reward, 8% rev => 8.5% reward. Do this by doubling, then rounding down to nearest int, halving, then adding 0.5
                    $reward = (floor($revenue*2)/2)+0.5;
                } else {
                    // otherwise round up to next highest whole number
                    $reward = (floor($revenue*2)/2)+1;
                }

                if ( $reward > 0 ) {
                    // share offer should be more generous for % purchases than fixed offers
                    $shares = 1;
                    $per_amount= ceil(5/$revenue); // e.g. 3% = 1.67 rounded up to 2
                }
            } elseif ( !empty($cg['shares_only']) ) {
                $reward = 0;
                $shares = ( $cg['shares_only'] == 999 ) ? round($revenue/10,0) : $cg['shares_only']; // interpret 999 as artifical value meaning "calculate shares as proportion of revenue"
            } elseif ( $revenue > 100 ) {
                // if less than £9, round up to next multiple of 25p, if less than £20, round up to next multiple of 50p, else next whole £1
                if ( $revenue < 900 ) {
                    // <£9, add 10p/1%' : 'Rev>100, add 10p/1%
                    $reward = $revenue+10;
                } elseif ( $revenue < 2000 ) {
                    // £9-20, next50p/1%
                    $reward = (floor($revenue/50)*50)+50;
                } else {
                    // £20+, next £1
                    $reward = (floor($revenue/100)*100)+100;
                }

                $rev102=round($revenue*1.02,-1); // calculate 102% cashback rounded to nearest 10p
                if ( $reward > $revenue && $reward < $rev102 ) $reward = $rev102; // if offering over 100%, make sure it's at least 102%

                $shares = floor($reward/10); // 1 share for every 10p cashback
            } else {
                // <£1, easy cashback offer
                $reward = floor($revenue/2);
                $shares = floor($revenue/20); // 1 share for every 20p cashback
            }

            // check for any upper/lower limit
            if ( isset($cg['min_imutual_cashback']) && $cg['min_imutual_cashback']>0 ) {
                $reward=max($reward,$cg['min_imutual_cashback']);
            }
            if ( isset($cg['max_imutual_cashback']) && $cg['max_imutual_cashback']>0 ) {
                $reward=min($reward,$cg['max_imutual_cashback']);
            }
        } else {
            // white label model
            if ( $revenue_type == 'percentage' ) {
                $reward=$revenue;
            } else {
                $reward=round($revenue/2,0);
            }

            // check for any upper limits
            if ( isset($cg['max_whitelabel_cashback']) && $cg['max_whitelabel_cashback']>0 ) $reward=min($reward,$cg['max_whitelabel_cashback']);
            if ( isset($cg['max_imutual_cashback']) && $cg['max_imutual_cashback']>0 ) $reward=min($reward,$cg['max_imutual_cashback']); // wl reward should not exceed any imutual maximum
        }
        $reward = ( $revenue_type == 'percentage' ) ? floatval($reward)+0 : round($reward,0); // rounding

        return [
            'reward'=>$reward,
            'shares'=>$shares,
            'per_amount'=>$per_amount,
        ];
    }
}