<?php
namespace im\model;
use im\helpers\Strings;
use \Exception;

class Program extends Base {

	const DB_TABLE = 'program';
	const PRIMARY_KEYS = ['program_id'];
    const TITLE_FIELD = 'program';
	const DB_LISTS = [
		1 => ['accepted'=>'Accepted', 'not applied'=>'Not applied', 'denied'=>'Denied', 'ended'=>'Ended', 'on hold'=>'On hold', 'unknown'=>'Unknown', 'applied'=>'Applied'],
		2 => ['true'=>'Yes', 'false'=>'No'],
		3 => ['open'=>'Open', 'no link'=>'No link', 'no commission'=>'No commission', 'no cashback'=>'No cashback', 'not permitted'=>'Not permitted', 'ignored'=>'Ignored'],
		4 => ['ok'=>'Ok', 'error'=>'Error', 'changed'=>'Changed', 'nofollow'=>'Nofollow'],
		5 => ['unknown'=>'Unknown', 'yes'=>'Yes', 'no'=>'No'],
		6 => ['default'=>'Default', 'auto'=>'Auto', 'manual'=>'Manual'],
	];

	const DB_MODEL = [
        'program_id'		    => ['type'=>'key'],
        'aff_id'		        => ['type'=>'num', 'required'=>true, 'class'=>'Network'],
        'site_id'		        => ['type'=>'num', 'onChange'=>'siteChanged'],
        'program'		        => ['type'=>'txt', 'required'=>true],
        'link_id'		        => ['type'=>'txt'],
        'network_ref'		    => ['type'=>'txt'],
        'last_updated'		    => ['type'=>'dat'],
        'last_reviewed'		    => ['type'=>'dat'],
        'error_msg'		        => ['type'=>'txt'],
        'error_id'		        => ['type'=>'num'],
        'logo_small'		    => ['type'=>'txt'],
        'url2'		            => ['type'=>'txt'],
        'status'		        => ['type'=>'txt', 'default'=>'unknown', 'list' => 1],
        'logo_medium'		    => ['type'=>'txt'],
        'checking'		        => ['type'=>'txt', 'default'=>'false', 'list' => 2],
        'process_status'		=> ['type'=>'txt', 'default'=>'open', 'list' => 3],
        'url'		            => ['type'=>'txt'],
        'submerchant_id'		=> ['type'=>'num'],
        'submerchant_name'		=> ['type'=>'txt'],
        'strapline'		        => ['type'=>'txt'],
        'cookie_period'		    => ['type'=>'num'],
        'commission_summary'	=> ['type'=>'txt'],
        'commission_changed'	=> ['type'=>'dat'],
        'pence'		            => ['type'=>'num'],
        'commission_error_date'	=> ['type'=>'dat'],
        'commission_error'		=> ['type'=>'txt'],
        'url2_destination'		=> ['type'=>'txt'],
        'url2_status'		    => ['type'=>'txt', 'list' => 4],
        'url2_checked'		    => ['type'=>'dat'],
        'domain_id'		        => ['type'=>'num', 'class'=>'Domain'],
        'url2_redirect_count'	=> ['type'=>'num'],
        'meta_redirect'		    => ['type'=>'txt', 'default'=>'false', 'list' => 2],
        'vat_exclusive'		    => ['type'=>'txt', 'default'=>'unknown', 'list' => 5],
        'max_percent'		    => ['type'=>'num'],
        'url2_http_code'		=> ['type'=>'num'],
        'notes'		            => ['type'=>'txt'],
        'url2_domain_id'		=> ['type'=>'num'],
        'commission_checked'	=> ['type'=>'dat'],
        'commission_reporting'	=> ['type'=>'txt', 'default'=>'default', 'list' => 6],
        'curl_errno'		    => ['type'=>'num'],
        'commission_check_priority'		=> ['type'=>'num', 'default'=>1000],
    ];

    protected $programText;
    protected $network;
    protected $domain;
    protected $commissions;
    protected $commissionCount;

    protected function load() {
        $this->commissions = $this->commissionCount = null;
        parent::load();
        $this->programText = new ProgramText($this->container);
        try {
            $this->programText->read($this->get('program_id'));
        } catch (\Exception $e) {
            if ( $e->getCode() != 404 ) throw new \Exception($e->getMessage());
        }
    }

    public function getProgramText() {
        return $this->programText;
    }

    public function getNetwork() {
        return $this->getObject('aff_id');
    }

    public function getCommissions() {
        if ( !isset($this->commissionCount) ) {
            $this->getCommissionCount();
        }
        return $this->commissions;
    }

    public function getCommissionCount() {

        if ( !isset($this->commissionCount) ) {
            $this->commissions=[];
            $count = ['commissions'=>0, 'unassigned'=>0, 'unique'=>0];
            $last_checked = $this->get('commission_checked') ?? date('Y-m-d');
            $percentage=$fixed=[];
            $sql = 'SELECT * FROM commission WHERE last_checked >= "'.$last_checked.'" AND program_id = '.$this->get('program_id');
            $result=$this->db->sql_query($sql);
            $rows = $this->db->sql_fetchrowset($result);
            $this->db->sql_freeresult($result);
            foreach($rows as $r) {
                $commission = new Commission($this->container);
                $commission->set($r);
                $this->commissions[] = $commission;
                if ( empty($r['commission_group_id']) ) {
                    $count['unassigned']++;
                }
                if ( !in_array($r['revenue'], ${$r['revenue_type']}) ) {
                    ${$r['revenue_type']}[] = $r['revenue'];
                }
            }
            $count['commissions'] = count($rows);
            $count['unique'] = count($percentage) + count($fixed);
            $this->commissionCount = $count;
        }
        return $this->commissionCount;
    }

    public function setUnassignedCommissionCount(int $count) {
        $this->getCommissionCount();
        $this->commissionCount['unassigned'] = $count;
    }

    public function getProgUrl() {
        // @todo AF and Linkshare use PROGID. Use Platform class instead
        if ( $url = $this->getNetwork()->getNetworkType()->get('prog_url') ) {
            $values = $this->getNetwork()->get() + [
                'mref'=>$this->get('network_ref'),
                'prog_id'=>$this->get('submerchant_id'),
            ];
            $url = Strings::populatePlaceholders($url, $values);
        }
        return $url;
    }

    public function suggestTitleAndStrapline() {
        $title = $program = $this->get('program');
        $strapline = '';
        if ( $meta_title = $this->getProgramText()->get('meta_title') ) {

            if ( strpos($meta_title,'|') !== false) {
                $tmp = array_map('trim',explode('|',$meta_title));
            } else {
                $tmp = array_map('trim',explode(' - ',$meta_title));
            }
            if ( count($tmp) > 1 ) {
                $closest_id = null;
                $closest_sim = null;
                foreach ($tmp as $text_id => $text) {
                    // Find which part of meta_title is closest match to program name
                    similar_text(strtoupper($program), strtoupper($text),$similarity);
                    //if ( !empty($_GET['debug']) ) $first_para .= '<br>'.$text.' => '.$similarity;
                    if ( $closest_id === null || $similarity > $closest_sim ) {
                        $closest_id = $text_id;
                        $closest_sim = $similarity;
                    }
                }
                $title = $tmp[$closest_id];
                unset($tmp[$closest_id]);
                $strapline = implode('. ',$tmp);
            } else {
                $title = $tmp[0];
            }
        }
        if ( substr($title,0,6) == 'FREE: ' ) $title = substr($title,6);
        if ( substr($title,-5) == ' - UK' ) $title = substr($title,0,-5);
        $pos=strpos($title,' (');
        if ( $pos > 2 ) $title = substr($title,0,$pos); // remove text in brackets
        return ['title'=>$title, 'offer'=>$strapline];
    }

    /**
     * Return data items needed to update site record when switching programs
     */
    public function getSiteData() {
        return [
            'program_id'=>$this->get('program_id'),
            'aff_id'=>$this->get('aff_id'),
            'domain_id'=>$this->get('domain_id'),
            'merchant_ref'=>$this->get('network_ref'),
            'link_id'=>$this->get('link_id'),
            'prog_id'=>$this->get('submerchant_id'),
            'url2'=>$this->generateAffiliateLink(),
            'deep_link'=>$this->generateDeepLink(),
        ];
    }

    public function generateAffiliateLink() {
        if ( $url = $this->get('url2') ) {
            if ( strpos($url,'{USERID}') === false ) {
                $url .= $this->getNetwork()->getNetworkType()->get('clickref_string_1');
            }
            try {
                $this->validateUrl($url);
            } catch (Exception $e) {
                trigger_error($e->getMessage());
                $url = null;
            }
        }
        return $url;
    }

    public function generateDeepLink() {
        return $this->getNetwork()->get('deep_link');
    }

    /**
     * Adapt valid affiliate link to include clickref parameter
     */
    public function addClickRefToUrl(string $url) {
        try {
            return $this->getNetwork()->addClickRefToUrl($url);
        } catch (Exception $e) {
            return false;
        }
    }

    protected function siteChanged() {
        if ( $this->get('site_id') === null ) {
            // if null, find site record, set program to null and (if live) suspend
            $site = new Site($this->container);
            if ( $site->findOne(['program_id'=>$this->id()]) ) {
                $data = ['program_id'=>null];
                if ( $site->isLive() ) $data['astatus']=25;
                $site->update($data);
            }
        }
    }

    protected function unload() {
        $this->commissionCount = null;
        return parent::unload();
    }
}