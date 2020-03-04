<?php
namespace im\model;

class Network extends Base {

    const DB_TABLE = 'm_aff';
    const PRIMARY_KEYS = ['aff_id'];
    const TITLE_FIELD = 'title';
    const DB_LISTS = [
        1 => ['Auto'=>'Auto', 'Manual'=>'Manual', 'None'=>'None'],
        2 => ['no'=>'No', 'yes'=>'Yes'],
        3 => ['yes'=>'Yes', 'no'=>'No', 'unknown'=>'Unknown'],
        4 => ['true'=>'Yes', 'false'=>'No'],
        5 => ['false'=>'No', 'true'=>'Yes'],
        6 => ['all'=>'All', 'single'=>'Single', 'either'=>'Either', 'false'=>'False'],
    ];

    const DB_MODEL = [
        'aff_id'                     => ["type"=>"key"],
        'url'                        => ["type"=>"txt"],
        'status'                     => ["type"=>"num"],
        'title'                      => ["type"=>"txt", "required"=>true],
        'blurb'                      => ["type"=>"txt"],
        'notes'                      => ["type"=>"txt"],
        'clicks'                     => ["type"=>"num"],
        'offer'                      => ["type"=>"txt"],
        'phrase'                     => ["type"=>"txt"],
        'phrase_update'              => ["type"=>"num", "default"=>1009868400],
        'category_id'                => ["type"=>"num"],
        'topic_id'                   => ["type"=>"num"],
        'url2'                       => ["type"=>"txt"],
        'user_id'                    => ["type"=>"num"],
        'deep_link'                  => ["type"=>"txt"],
        'login'                      => ["type"=>"txt"],
        'link_format'                => ["type"=>"txt"],
        'tracking'                   => ["type"=>"txt", "default"=>"None", "list"=>1],
        'last_checked'               => ["type"=>"num"],
        'stats_url'                  => ["type"=>"txt"],
        'latest_trans'               => ["type"=>"dat"],
        'link_eg'                    => ["type"=>"txt"],
        'invoice_email'              => ["type"=>"txt"],
        'invoice_reference'          => ["type"=>"txt"],
        'invoice_address'            => ["type"=>"txt"],
        'vat'                        => ["type"=>"num"],
        'address'                    => ["type"=>"txt"],
        'contact_phone'              => ["type"=>"txt"],
        'fax'                        => ["type"=>"txt"],
        //'prog_url'   superseded by m_network.prog_url
        'pwd'                        => ["type"=>"txt"],
        'contact_id'                 => ["type"=>"num"],
        'claims_contact_id'          => ["type"=>"num"],
        'forum_id'                   => ["type"=>"num"],
        'trans_table'                => ["type"=>"txt", "default"=>"m_trans"],
        'claims_manager'             => ["type"=>"num", "default"=>81613],
        'notes_reporting'            => ["type"=>"txt"],
        'notes_payment'              => ["type"=>"txt"],
        'banner_url'                 => ["type"=>"txt"],
        'sitename'                   => ["type"=>"txt"],
        'user_lang'                  => ["type"=>"txt", "default"=>"english"],
        'unallocated'                => ["type"=>"num"],
        'currency_id'                => ["type"=>"num", "default"=>1],
        'region_id'                  => ["type"=>"num"],
        'cookie_period'              => ["type"=>"num", "default"=>30],
        'login_form'                 => ["type"=>"txt"],
        'external_user'              => ["type"=>"num"],
        'affiliate_id'               => ["type"=>"txt"],
        'access_key'                 => ["type"=>"txt"],
        'claims_url'                 => ["type"=>"txt"],
        'network_id'                 => ["type"=>"num"],
        'date_sep'                   => ["type"=>"txt"],
        'time_sep'                   => ["type"=>"txt"],
        'money_sep'                  => ["type"=>"txt"],
        'data_method'                => ["type"=>"txt"],
        'award_days'                 => ["type"=>"num", "default"=>60],
        'tmp_errors'                 => ["type"=>"num"],
        'prog_id_required'           => ["type"=>"txt", "default"=>"no", "list"=>2],
        'self_billing'               => ["type"=>"txt", "default"=>"unknown", "list"=>3],
        'contact_email'              => ["type"=>"txt"],
        'contact_fullname'           => ["type"=>"txt"],
        'claims_email'               => ["type"=>"txt"],
        'claims_fullname'            => ["type"=>"txt"],
        'contact_mobile'             => ["type"=>"txt"],
        'notes_claims'               => ["type"=>"txt"],
        'special_rules'              => ["type"=>"txt", "default"=>"false", "list"=>4],
        'rank'                       => ["type"=>"num"],
        'claim_days'                 => ["type"=>"num", "default"=>7],
        'can_claim'                  => ["type"=>"txt", "default"=>"true", "list"=>4],
        'claim_response_days'        => ["type"=>"num", "default"=>80],
        'company'                    => ["type"=>"txt"],
        'language_id'                => ["type"=>"num"],
        'annual_payments'            => ["type"=>"num"],
        'latest_payment'             => ["type"=>"dat"],
        'financial_year_payments'    => ["type"=>"num"],
        'auto_program_feed'          => ["type"=>"txt", "default"=>"false", "list"=>4],
        'access_key_2'               => ["type"=>"txt"],
        'guarantee'                  => ["type"=>"txt", "list"=>4],
        'new_program_alert'          => ["type"=>"txt", "default"=>"false", "list"=>5],
        'new_programs'               => ["type"=>"num"],
        'token'                      => ["type"=>"txt"],
        'token_expiry'               => ["type"=>"dat"],
        'domain_id'                  => ["type"=>"num"],
        'earned_this_financial_year' => ["type"=>"num"],
        'auto_trans_feed'            => ["type"=>"txt", "default"=>"false", "list"=>4],
        'sites'                      => ["type"=>"num"],
        'auto_commission_feed'       => ["type"=>"txt", "default"=>"false", "list"=>6],
        'commissions_in_prog_data'   => ["type"=>"txt", "default"=>"false", "list"=>4],
        'platform'                   => ["type"=>"txt"],
    ];

    protected $transTable;
    protected $platform;

    protected function load() {
        parent::load();
        $this->networkType = new NetworkType($this->container, $this->get('network_id'));
        $this->transTable = ( !empty($this->get('trans_table')) ) ? $this->get('trans_table') : 'm_trans_'.$this->get('aff_id');
    }

    protected function getPlatform() {
        if ( empty($this->platform) ) {
            if ( !$class = $this->get('platform') ) throw new \Exception('Platform class not defined');
            $class = '\\im\\platform\\'.$class;
            if ( !class_exists($class) ) throw new \Exception('Platform class not found: '.$class);
            $this->platform = new $class($this);
        }
        return $this->platform;
    }

    public function getInvoiceableNetworks() {
        return $this->find(['invoice_email'=>['ne'=>'']]);
    }

    public function getNextInvoiceDates() {
        $invoice = new Invoice($this->container);
        if ( $invoice->fetchLatestForNetwork($this->get('aff_id')) ) {
            $d = new \DateTime($invoice->get('supply_to'));
            $d->modify('first day of next month');
        } else {
            $d = new \DateTime();
            $d->modify('first day of last month');
        }
        $supply_from = $d->format('Y-m-d');
        $d->modify('last day of this month');
        $supply_to = $d->format('Y-m-d');
        return ['supply_from'=>$supply_from, 'supply_to'=>$supply_to];
    }

    public function getCompanyName() {
        return !empty($this->get('company')) ? $this->get('company') : $this->get('title');
    }

    public function getNetworkType() {
        return $this->networkType;
    }

    public function getTransTable() {
        return $this->transTable;
    }

    /**
     * Return data from network
     */
    public function fetch(string $type, array $options=[]) {
        $data = $this->getPlatform()->fetch($type, $options);
        return $data;
    }

    public function fetchPayments($options=[]) {
        if ( empty($options['payment_start_date']) ) {
            $payment = new NetworkPayment($this->container);
            if ( $payment->fetchLatestForNetwork($this->get('aff_id')) ) {
                $start = strtotime($payment->get('payment_date'));
           } else {
               $start = strtotime('- 1 year');
           }
        } else {
            $start = strtotime($options['payment_start_date']);
        }
        $options['payment_start_date'] = date('Y-m-d',$start);
        if ( empty($options['payment_end_date']) ) {
            // Max date span 1 year
            $options['payment_end_date'] = date('Y-m-d',$start+(365*86400));
        }
        return $this->fetch('payments',$options);
    }

    /**
     * Fetch subpayments (by merchant) for a network payment (where supported e.g. Linkshare)
     */
    public function fetchMerchantPayments(NetworkPayment $payment) {
        if ( empty($payment->get('reference')) ) throw new Exception('Missing payment reference',400);
        $options = ['payment_reference'=>$payment->get('reference')];
        return $this->fetch('merchantPayments',$options);
    }

    /**
     * Fetch transaction details for a subpayment (where supported e.g. Linkshare)
     */
    public function fetchMerchantPaymentDetails(MerchantPayment $mpayment) {
        if ( empty($mpayment->get('reference')) ) throw new Exception('Missing payment reference',400);
        $options = ['merchant_payment_reference'=>$mpayment->get('reference')];
        return $this->fetch('merchantPaymentDetails',$options);
    }

    /**
     * Adapt valid affiliate link to include clickref parameter
     */
    public function addClickRefToUrl(string $url) {
        return $this->getPlatform()->addClickRefToUrl($url);
    }

}