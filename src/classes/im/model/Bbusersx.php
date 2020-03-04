<?php
namespace im\model;

class Bbusersx extends Base {

    const DB_TABLE = 'bb_users_x';
    const PRIMARY_KEYS = ['user_id'];
    const DB_LISTS = [
        1 => ['visible'=>'visible', 'invisible'=>'invisible'],
        2 => ['yes'=>'Yes', 'no'=>'No', 'pending'=>'Pending'],
    ];

    const DB_MODEL = [
        'user_id'                     => ["type"=>"key"],
        'last_update'                 => ["type"=>"num"],
        'statement_viewed'            => ["type"=>"num"],
        'firstname'                   => ["type"=>"txt"],
        'rpoints'                     => ["type"=>"num"],
        'rpoints_pending'             => ["type"=>"num"],
        'rpoints_redeemed'            => ["type"=>"num"],
        'terms_id'                    => ["type"=>"num"],
        'schedule_id'                 => ["type"=>"num"],
        'email_format'                => ["type"=>"num", "default"=>10],
        'statement_updated'           => ["type"=>"num"],
        'ecashier_id'                 => ["type"=>"num"],
        'quit_attempts'               => ["type"=>"num"],
        'quit_time'                   => ["type"=>"num"],
        'country_id'                  => ["type"=>"num", "default"=>222],
        'age_id'                      => ["type"=>"num"],
        'gender_id'                   => ["type"=>"num"],
        'scheme_id'                   => ["type"=>"num", "default"=>7],
        'trust'                       => ["type"=>"num", "default"=>100],
        'rpoints_earned'              => ["type"=>"num"],
        'vip_status'                  => ["type"=>"num"],
        'suspended_id'                => ["type"=>"num"],
        'region_id'                   => ["type"=>"num"],
        'currency_id'                 => ["type"=>"num", "default"=>1],
        'hide_logos'                  => ["type"=>"num"],
        'hof_anon_time'               => ["type"=>"num"],
        'hof_visible_time'            => ["type"=>"num"],
        'source_id'                   => ["type"=>"num"],
        'path_id'                     => ["type"=>"num"],
        'qstring_id'                  => ["type"=>"num"],
        'clickref_id'                 => ["type"=>"num"],
        'revenue'                     => ["type"=>"num"],
        'claims'                      => ["type"=>"num"],
        'queries'                     => ["type"=>"num"],
        'profit'                      => ["type"=>"num"],
        'latest_clickthru'            => ["type"=>"num"],
        'track_id'                    => ["type"=>"num"],
//      'profit_pending'              => ["type"=>"num"], // @todo remove
        'portal_id'                   => ["type"=>"num", "default"=>1],
        'view_status'                 => ["type"=>"txt", "default"=>"visible", "list"=>1],
        'post_trust'                  => ["type"=>"num", "default"=>100],
        'unsubscribe_id'              => ["type"=>"num"],
        'referral_scheme_id'          => ["type"=>"num"],
        'referrer_referral_scheme_id' => ["type"=>"num"],
        'autologin_token'             => ["type"=>"txt"],
        'sub_portal_id'               => ["type"=>"num"],
        'lastname'                    => ["type"=>"txt"],
        'telephone'                   => ["type"=>"txt"],
        'shares_pending'              => ["type"=>"num"],
        'shares_awarded'              => ["type"=>"num"],
        'total_transactions'          => ["type"=>"num"],
        'iso_id'                      => ["type"=>"num"],
        'shareholder_approved'        => ["type"=>"dat"],
        'agm_shares'                  => ["type"=>"num"],
        'full_name'                   => ["type"=>"txt"],
        'mail_event_id'               => ["type"=>"num"],
        'user_remove'                 => ["type"=>"num"],
        'ipn'                         => ["type"=>"num"],
        'ipcountry'                   => ["type"=>"num"],
        'is_shareholder'              => ["type"=>"txt", "default"=>"pending", "list"=>2],
        'postcode'                    => ["type"=>"txt"],
        'latitude'                    => ["type"=>"num"],
        'longitude'                   => ["type"=>"num"],
        'iso_code'                    => ["type"=>"txt"],
    ];

    public function balanceInPounds() {
        $this->checkBalance();
        return round($this->get('rpoints')/100,2);
    }

    public function checkBalance(bool $adjustTotals = true) {
        if ( !$this->isLoaded() ) {
            $tmp = debug_backtrace()[0];
            trigger_error("Failed checkBalance call from line ".$tmp['line'].' of '.$tmp['file'].': '.json_encode($this->get()));
            return [];
        }
        $logile = '/tmp/u'.$this->get('user_id');
        $adjustments = [];
        $pts = Transactions::forUserbyStatus($this->db, $this->get('user_id'));
        $payments = new Payments($this->container);
        $pts['rpoints_redeemed'] = $payments->totalForUser($this->get('user_id'));
        $pts['rpoints'] = $pts['rpoints_earned'] - $pts['rpoints_redeemed'];
        file_put_contents($logile,"\n\n**** ".date('Y-m-d H:i')." menuRecalc pts:".print_r($pts,true),FILE_APPEND);
        foreach(['rpoints_pending', 'rpoints_earned', 'rpoints_redeemed', 'rpoints', 'shares_pending', 'shares_awarded', 'total_transactions'] as $field) {
            file_put_contents($logile,"\n$field:".$this->get($field),FILE_APPEND);
            $diff = $pts[$field] - $this->get($field);
            if ( $diff != 0 ) {
                $adjustments[$field]=$diff;
                $this->set([$field => $pts[$field]]);
                file_put_contents($logile," should be ".$this->get($field),FILE_APPEND);
            }
        }

        if ( !empty($adjustments) ) {
            $pts['adjustments']=$adjustments;
            if ( $adjustTotals ) {
                $this->update();
                file_put_contents($logile,"\nADJUSTED",FILE_APPEND);
            }
        }
        return $pts;
    }
}