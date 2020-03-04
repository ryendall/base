<?php
namespace im\model;

class CommissionGroup extends Base {

    const DB_TABLE = 'commission_group';
    const PRIMARY_KEYS = ['commission_group_id'];
    const DB_LISTS = [
        1 => ['active'=>'Active', 'inactive'=>'Inactive'],
        2 => ['true'=>'Yes', 'false'=>'No'],
        3 => ['visible'=>'visible', 'hidden'=>'hidden'],
    ];
    const DB_MODEL = [
        'commission_group_id'               => ['type'=>'key'],
        'site_id'		                    => ['type'=>'num'],
        'private_name'		                => ['type'=>'txt'],
        'status'		                    => ['type'=>'txt', 'default'=>'active', 'list'=>1],
        'provisional_revenue'		        => ['type'=>'num'],
        'can_claim'		                    => ['type'=>'txt', 'default'=>'true', 'list'=>2],
        'max_per_period'		            => ['type'=>'num'],
        'max_per_period_days'		        => ['type'=>'num'],
        'shares_only'		                => ['type'=>'num'],
        'max_whitelabel_cashback'		    => ['type'=>'num'],
        'max_imutual_cashback'		        => ['type'=>'num'],
        'min_imutual_cashback'		        => ['type'=>'num'],
        'treat_percentage_reward_as_fixed'	=> ['type'=>'txt', 'default'=>'false', 'list'=>2],
        'default_view_status'		        => ['type'=>'txt', 'default'=>'visible', 'list'=>3],
        'trans_text_match'		            => ['type'=>'txt'],
        'feed_text_match'		            => ['type'=>'txt'],
        'pct_of_field'		                => ['type'=>'txt'],
    ];
    const GENERIC_LABELS = ['default', 'sale', 'general'];

    public static function createGroupName($name,$fallback_name,$default_name='Purchases') {
        $name = ( strtolower($name) == 'default commission' && !is_numeric($fallback_name) ) ? $fallback_name : $name;
        if ( in_array(strtolower($name),self::GENERIC_LABELS) ) {
            return $default_name;
        }
        $name = str_replace('_',' ',$name);
        $words=explode(' ',$name);
        foreach ($words as $id=>$word) {
            if ( strlen($word) > 2 || in_array($word,array('TO','IF','AN','BE','AT','DO','GO','IN','IT','NO','ON','OR','SO','UP')) ) {
                $words[$id]=ucwords(strtolower($word));
            }
        }
        return implode(' ',$words);
    }

    public function createScheduleItem(Commission $commission) {
        if ( !$this->isLoaded() ) throw new \Exception('No commission group loaded');
        $si = new ScheduleItem($this->container);
        $si->set([
            'site_id'=>$this->get('site_id'),
            'commission_group_id'=>$this->getKey()['commission_group_id'],
            'revenue_type'=>$commission->get('revenue_type'),
            'revenue'=>$commission->get('revenue'),
            'public_name'=>$this->get('private_name'),
        ])->create(); // create new item
        return $si;
    }
}