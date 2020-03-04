<?php
namespace im\model;
use im\helpers\Strings;

class Site extends Base {

    const DB_TABLE = 'm_site';
    const PRIMARY_KEYS = ['site_id'];
    const TITLE_FIELD = 'title';
    const STATII = [
        10 => 'Applied',
        15 => 'Suggested',
        20 => 'Live (non-reward)',
        21 => 'Email only',
        22 => 'Live (hidden)',
        23 => 'Live',
        25 => 'Suspended',
        30 => 'Rejected',
        40 => 'Not applicable',
        50 => 'Hidden',
        99 => 'Deleted',
    ];

	const DB_LISTS = [
		1 => ['central'=>'Central', 'independent'=>'Independent'],
		2 => ['true'=>'Yes', 'false'=>'No'],
        3 => ['inherit'=>'Inherit', 'exclude'=>'Exclude', 'include'=>'Include', 'new'=>'New'],
        4 => [10=>'Applied', 15=>'Suggested', 20=>'Live (non-reward)', 21=>'Email only', 22=>'Live (hidden)', 23=>'Live', 25=>'Suspended', 30=>'Rejected', 40=>'Not applicable', 50=>'Hidden', 99=>'Deleted'],
	];

	const DB_MODEL = [
        'site_id'		            => ['type'=>'key'],
        'new_deal_date'             => ['type'=>'dat', 'default'=>'{NOW}'],
        'list_order'		        => ['type'=>'num', 'default'=>100000],
        'title'		                => ['type'=>'txt', 'required'=>true],
        'offer'		                => ['type'=>'txt', 'required'=>true],
        'blurb'		                => ['type'=>'txt'],
        'astatus'		            => ['type'=>'num', 'required'=>true, 'list'=>4, 'onChange'=>'updateSchedule'],
        'url2'		                => ['type'=>'txt'],
        'deep_link'		            => ['type'=>'txt'],
        'earning_notes'		        => ['type'=>'txt'],
        'aff_id'		            => ['type'=>'num', 'class'=>'Network'],
        'link_id'		            => ['type'=>'txt'],
        'vat_exclusive'		        => ['type'=>'num', 'default'=>5],
        'region_id'		            => ['type'=>'num', 'default'=>1],
        'award_days'		        => ['type'=>'num'],
        'schedule_id'		        => ['type'=>'num'],
        'ip_block'		            => ['type'=>'num'],
        'note_id'                   => ['type'=>'num'],
        'currency_id'		        => ['type'=>'num', 'default'=>1],
        'prog_id'		            => ['type'=>'num'],
        'max_per_day'		        => ['type'=>'num'],
        'merchant_ref'		        => ['type'=>'txt'],
        'max_trans_per_user'		=> ['type'=>'num'],
        'program_id'		        => ['type'=>'num', 'class'=>'Program', 'onChange'=>'updateSchedule'],
        'claim_days'		        => ['type'=>'num'],
        'can_claim'		            => ['type'=>'txt', 'list'=>2, 'default'=>'true', 'onChange'=>'updateSchedule'],
        'claim_response_days'		=> ['type'=>'num'],
        'conditions'                => ["type"=>"txt"],
        'ecashier_id'               => ["type"=>"num"],
        'default_include_status'    => ['type'=>'txt', 'default'=>'inherit', 'list'=>3],
        'manual_keywords'		    => ['type'=>'txt'],
        'url_tag'		            => ['type'=>'txt'],
        'shares_message'		    => ['type'=>'txt'],
        'post_id'		            => ['type'=>'num'],
        'max_per_period_days'		=> ['type'=>'num'],
        'max_tracking_days'		    => ['type'=>'num'],
        'twitter'		            => ['type'=>'txt'],
        'category_id'		        => ['type'=>'num', 'required'=>true, 'class'=>'Category', 'onChange'=>'categoryChanged'],
        'domain_id'		            => ['type'=>'num', 'class'=>'Domain', 'onChange'=>'domainChanged'],
        'guarantee'		            => ['type'=>'txt', 'list'=>2, 'default'=>'true', 'onChange'=>'updateSchedule'],
    ];

    //'tracking'
    /* 'api_conditions','autoshare','awin_rule_id','beyond_award_date','cap_period','cap','cashback','cashback2','claim_calc_method','claims_url','claims','click_flood','click_table','click','clicks','cookie_period','delivery_regexp','ecashier_id','end_date','extended_rate','height','highest_fixed','highest_percentage','image_checked','image_small_checked','image_small','image','imap_url_match','instant_id','javascript','keywords','latest','lead','link_errors','max_delay','named_links','offer_cat_id','phrase_update','phrase','pointbooster','ppc_notes','price_regexp','qtr_clicks','qtr_profit','qtr_revenue_old','qtr_revenue','qtr_transactions','rating','ref','referrer','rejection_rate','report_delay','requires_verification','reviewer','rpoints_live','sale','sale2','secondary_domain','share','share2','start_date','stat_avg_payment_time','stat_avg_tracking_time','stat_claim_approval_rate','stat_rejection_rate','stat_tracking_reliability','subcategory_id','topic_id','tracking_rate','tracking','trans','type','url_tag_id','url','use_clicktime_as_transtime','user_id','user_lang','verify_question','verify_validation','votes','width','xml_blurb' */

    const MAX_LENGTH_TITLE_AND_HEADLINE = 100;
    const CONDITIONS_NO_GUARANTEE = 'The cashback guarantee does not apply to this offer.';
    const CONDITIONS_NO_CLAIM = 'This merchant does not accept claims for missing transactions.';

    protected $program; // Program obj
    protected $category; // Category obj
    protected $siteLinks = []; // array of SiteLink records

    protected function listFields() {
        return ['title','astatus','aff_id','program_id'];
    }

    protected function headerLinks() {
        return [
            ['label'=>'rpt', 'url'=>'/rpt/?sc=60&s={site_id}&show_hidden=vars&merchant={title}'],
            ['label'=>'r', 'url'=>'rewards.php?m=a&s={site_id}'],
            ['label'=>'cl', 'url'=>'manage_claims.php?claim_statii=all&query_statii=all&order_by=c.claim_date%20DESC&s={site_id}'],
            ['label'=>'i', 'url'=>'site_image.php?s={site_id}'],
            ['label'=>'t', 'url'=>'/rpt/shw.php?id=369&sc=60&s={site_id}&show_hidden=vars&merchant={title}'],
            ['label'=>'f', 'url'=>'content.php?m=a&s={site_id}'],
            ['label'=>'sc', 'url'=>'cats.php?s={site_id}'],
            ['label'=>'v', 'url'=>'/rpt/shw.php?id=1073&sc=60&s={site_id}&show_hidden=vars&merchant={title}'],
            ['label'=>'ck', 'url'=>'clicks.php?u=all&s={site_id}'],
            ['label'=>'cr', 'url'=>'claim_refs.php?m=a&s={site_id}'],
            ['label'=>'sh', 'url'=>'sharing.php?s={site_id}'],
            ['label'=>'pn', 'url'=>'/rpt/shw.php?id=99&s={site_id}&merchant={title}'],
        ];
    }

    /**
     * Switch site to another network program
     * Triggers updates of related tables as necessary
     */
    public function setProgram(int $program_id) {
        $this->getProgram()->read($program_id);
        $program_site_id = $this->getProgram()->get('site_id');
        if ( $program_site_id && $program_site_id != $this->get('site_id') ) {
            throw new \Exception('Program is assigned to another site');
        }
        $this->set($this->getProgram()->getSiteData());
        // @todo site_link and status_schedule
        // @todo update below shouldnt happen until site changes comitted to db
        if ( $this->get('site_id') ) {
            // Changing an existing site record, assume changes will be committed to DB using update()
            $this->getProgram()->update(['site_id'=>$this->get('site_id')]);
            $this->updateSiteLinks();
            $this->matchEmailsByProgram();
        }
        return $this;
    }

    public function stripSitenameFromSentence(string $text) {
        if ( !$sitename = $this->get('title') ) return $text;
        $prefixes=['at','with','from'];
        foreach($prefixes as $prefix) {
            $text = preg_replace('/ '.$prefix.' '.$sitename.'[\w\.!]*/i','',$text);
        }
        return $text;
    }

    public function addNote(string $text, int $userId) {
        $this->mustBeLoaded();
        $note = new SiteNote($this->container);
        $note_id = $note->create([
            'site_id'   => $this->id(),
            'note'      => $text,
            'user_id'   => $userId,
        ],true);
        $this->update(['note_id'=>$note_id]);
        return $note_id;
    }

    /**
     * @todo put userId in container?
     */
    public function suspend(string $note) {

    }


    protected function updateSchedule() {
        if ( $old_schedule_id = $this->get('schedule_id') ) {
            $old_schedule = new SiteSchedule($this->container,$this->get('schedule_id'));
        }
        $new_schedule = new SiteSchedule($this->container);
        $site_schedule_id = $new_schedule->create([
            'site_id'       => $this->id(),
            'start_date'    => $this->now,
            'astatus'       => $this->get('astatus'),
            'program_id'    => $this->get('program_id'),
            'can_claim'     => $this->get('can_claim'),
            'guarantee'     => $this->get('guarantee'),
        ],true);
        $this->update(['schedule_id'=>$site_schedule_id]);
        if ( $old_schedule_id ) {
            $old_schedule->update(['end_date'=>$this->now]);
        }
        // @todo Remove all other future schedule changes for this site
        // @todo CHECK THAT ONE SCHEDULE ENTRY RUNS UNTIL 2030, IF NOT ADD 'SUSPENDED' ENTRY AFTER LAST ONE
        // UPDATE LOG IF MADE LIVE / SWITCHED
    }

    protected function matchEmailsByProgram() {
        if ( $program_id = $this->get('program_id') ) {
            $imap = new Imap($this->container);
            $emails = $imap->find(['program_id'=>$program_id, 'site_id'=>null]);
            foreach($emails as $e) {
                $imap->read($e['imap_id']);
                $imap->update(['site_id'=>$this->id()]);
            }
        }
    }

    // update site link(s) with new url
    protected function updateSiteLinks() {
        $siteLink = new SiteLink($this->container);
        $links = $siteLink->find(['site_id'=>$this->get('site_id'), 'status'=>'active']);
        foreach($links as $link) {
            $siteLink->read($link['id']);
            if ( empty($link['category_id']) || $link['category_id'] == $this->get('category_id') ) {
                // This is the main site link, update the url for the new program
                $siteLink->update(['url'=>$this->get('url2')]);
            } else {
                // Secondary site link. Just set it to inactive for now
                $siteLink->update(['status'=>'inactive']);
                trigger_error('Site #'.$this->get('site_id').' secondary link (cat_id '.$link['category_id'].') deactviated due to program switch');
            }
        }
    }

    /**
     * Additional actions when we associate domain with a site
     */
    protected function domainChanged() {
        // Associate domain with this site
        $domain = new Domain($this->container, $this->get('domain_id'));
        if ( $domain->get('match_part') == 'host' ) {
            $domain->update(['site_id'=>$this->id()]);
        }
    }

    /**
     * Additional actions when we change site's primary category
     */
    protected function categoryChanged() {
        if ( $url_tag_id = $this->get('url_tag_id') ) {
            // Update category_id on site url_tag record
            $url_tag = new UrlTag($this->container, $url_tag_id);
            $url_tag->update(['category_id'=>$this->get('category_id')]);
        }
        $cs = new CategorySite($this->container);
        $cs->findOrCreate(['site_id'=>$this->id(), 'category_id'=>$this->get('category_id')]);
    }

    public function isLive() {
        return $this->get('astatus') >= 20 && $this->get('astatus') <= 23;
    }

    public function create(array $data=null, bool $exceptionOnError=false) {
        if ($data) $this->set($data); // must do this now so that values are available for getXxxDefaults() methods
        $this->addFallbackValues($this->getCategoryDefaults()+$this->getNetworkDefaults());
        $this->finaliseConditions();
        if ( !$site_id = parent::create() ) return false;

        // Generate url tag
        $urlTag = new UrlTag($this->container);
        $urlTag->generateSiteTag($this);

        // Associate new site record with the network program
        $this->getProgram()->set(['site_id'=>$site_id])->update();

        // Add subcategory link
        $cs = new CategorySite($this->container);
        if ( !$cs->create(['site_id'=>$site_id, 'category_id'=>$this->get('category_id')]) ) {
            trigger_error(json_encode($cs->getErrors()));
            throw new \Exception('Error creating CategorySite entry for site_id '.$site_id);
        }
        // @todo update category.links if changing astatus from/to 20/23

        // Add site/category link
        $sl = new SiteLink($this->container);
        if ( !$sl->create([
            'site_id'=>$site_id,
            'category_id'=>$this->get('category_id'),
            'url'=>$this->get('url2'),
            ])
        ) {
            trigger_error(json_encode($sl->getErrors()));
            throw new \Exception('Error creating SiteLink entry for site_id '.$site_id);
        }

        // Add conditions
        $cond = new SiteConditions($this->container);
        if ( !$cond->create(['site_id'=>$site_id, 'conditions'=>$this->get('conditions') ?? '']) ) {
            trigger_error(json_encode($cond->getErrors()));
            throw new \Exception('Error creating SiteConditions entry for site_id '.$site_id);
        }

        // Add default reference field for any claims
        $ref = new ClaimReferenceSite($this->container);
        $claim_refs = [
            [
                'question'=>'Transaction reference',
                'unique'=>'true',
                'compulsory'=>'true',
                'help'=>'Reference that uniquely identifies your transaction e.g. order/policy number, account name etc',
                'order'=>80,
                'validation_id'=>null,
                'field_id'=>1,
                'error'=>null,
            ],
            [
                'question'=>'Product type / description',
                'unique'=>'false',
                'compulsory'=>'true',
                'help'=>'The type of product / transaction undertaken e.g. DVD, Car insurance, registration, deposit',
                'order'=>60,
                'validation_id'=>null,
                'field_id'=>2,
                'error'=>null,
            ],
            [
                'question'=>'Email address',
                'unique'=>'false',
                'compulsory'=>'true',
                'help'=>'Email address provided to the merchant as part of this transaction',
                'order'=>40,
                'validation_id'=>4,
                'field_id'=>3,
                'error'=>'Must be a valid email address',
            ],
            [
                'question'=>'Full name',
                'unique'=>'false',
                'compulsory'=>'true',
                'help'=>'Full name of the person making the transaction',
                'order'=>20,
                'validation_id'=>null,
                'field_id'=>4,
                'error'=>null,
            ],
        ];
        foreach($claim_refs as $data) {
            if ( !$ref->create($data + ['site_id'=>$site_id]) ) {
                trigger_error(json_encode($ref->getErrors()));
                throw new \Exception('Error creating claim reference entry for site_id '.$site_id);
            }
        }

        // Add model_site entries
        foreach(Models::getModels($this->db) as $model) {
            $ms = new ModelSite($this->container);
            if ( !$ms->create(['site_id'=>$site_id, 'model_id'=>$model['model_id']]) ) {
                trigger_error(json_encode($ms->getErrors()));
                throw new \Exception('Error creating ModelSite entry for site_id '.$site_id);
            }
        }

        // for each portal, add a portal_site record using status from portal_category table
        foreach(PortalCategories::getPortalsByCategory($this->db, $this->get('category_id')) as $row) {
            $ps = new PortalSite($this->container);
            if ( !$ps->create(['site_id'=>$site_id, 'portal_id'=>$row['portal_id'], 'status'=>$row['status']]) ) {
                trigger_error(json_encode($ps->getErrors()));
                throw new \Exception('Error creating PortalSite entry for site_id '.$site_id);
            }
        }

        require_once('collection/functions_sites.php');
        $return = add_url_tag(1,$site_id);

        return $site_id;
    }

    public function getCategory() {
        return $this->getObject('category_id');
    }

    public function getProgram() {
        return $this->getObject('program_id');
    }

    public function createCommissionGroups() {
        foreach($this->getProgram()->getCommissions() as $commission) {
            if ( empty($commission->get('commission_group_id')) ) {
                $commission->setSiteId($this->getKey()['site_id']); // Pass site_id
                $commissionGroup = $commission->createCommissionGroup($this->getCategory()->getDefaultRewardLabel());
                $scheduleItem = $commissionGroup->createScheduleItem($commission);
                $scheduleItem->createScheduleReward();
            }
        }
        $this->getProgram()->setUnassignedCommissionCount(0);
    }

    public function getStatusText() {
        return self::STATII[$this->get('astatus')];
    }

    public function delete(array $data = NULL, bool $exceptionOnError = false) {
        if ( !$site_id = $this->id() ) throw new \Exception('Site not loaded');
        $status = $this->get('astatus');
        if ( $this->get('astatus') < 40 ) throw new \Exception('Cannot delete site with astatus < 40');
        $deleteTables = ['category_site', 'site_link', 'site_conditions', 'claim_reference_site', 'm_model_site', 'site_schedule', 'm_portal_site', 'commission_group', 'schedule_item', 'offer', 'url_tag', 'stats_site_period'];
        foreach($deleteTables as $table) {
            $sql = 'DELETE FROM '.$table.' WHERE site_id = '.$site_id;
            $result = $this->db->sql_query($sql);
        }

        $updateTables = ['program', 'domain', 'email_address'];
        foreach($updateTables as $table) {
            $sql = 'UPDATE '.$table.' SET site_id = NULL WHERE site_id = '.$site_id;
            $result = $this->db->sql_query($sql);
        }
        return parent::delete();
    }

    protected function load() {
        parent::load();
        $this->siteLinks = SiteLinks::findBySite($this->db, $this->getKey()['site_id']);
    }

    /**
     * Custom validation after standard checks pass
     * @var bool $fullValidation
     * @return bool
    */
    public function validate(bool $fullValidation=true) {
        if ( !parent::validate($fullValidation) ) return false;

        foreach($this->data as $var=>$value) {
            switch($var) {
                case 'title':
                $bannedWords = ['Limited','Ltd','-'];
                $badWords = Strings::findWordsInText($value, $bannedWords);
                if ( count($badWords) > 0 ) {
                    $this->setError($var, 'Contains: '.implode(', ',array_keys($badWords)), 'warning');
                    continue;
                }
                break;

                case 'offer':
                $title = $this->get('title');
                if ( !empty($title) && !empty($value) ) {
                    $titleWords = explode(' ',trim($title));
                    $repeatedWords = Strings::findWordsInText($value, $titleWords);
                    if ( count($repeatedWords) > 0 ) {
                        $this->setError($var, 'Title words repeated: '.implode(', ',array_keys($repeatedWords)), 'warning');
                        continue;
                    }
                }
                $length = strlen($value) + strlen($title ?? null);
                if ( $length > self::MAX_LENGTH_TITLE_AND_HEADLINE ) {
                    $this->setError($var, 'Combined length of title and headline ('.$length.') cannot exceed '.self::MAX_LENGTH_TITLE_AND_HEADLINE);
                    continue;
                }
                break;

                case 'blurb':
                $bannedWords = ['cpa','commission','affiliate'];
                $badWords = Strings::findWordsInText($value, $bannedWords);
                if ( count($badWords) > 0 ) {
                    $this->setError($var, 'Contains: '.implode(', ',array_keys($badWords)), 'warning');
                    continue;
                }
                break;

                case 'astatus':
                $program_id = $this->get('program_id');
                if ( in_array($value, [22,23]) && empty($program_id) ) {
                    $this->setError($var, 'Cannot set site live without a program assigned');
                    continue;
                }
                break;

                case 'guarantee':
                if ( $value != 'false' && $this->get('can_claim') == 'false' ) {
                    $this->setError($var, 'If can_claim=no, guarantee=no');
                    continue;
                }
                break;

                default:
                    continue;
            }
        }
        return $this->postValidation($fullValidation);
    }

    protected function finaliseConditions() {
        // @todo amend for updates, when guarantee/can_claim swap between true and false
        $conditions = $this->get('conditions');
        $can_claim = $this->get('can_claim');
        if ( $can_claim == 'false' ) $this->set(['guarantee'=>'false']); // cant have guarantee if claims not allowed
        $guarantee = $this->get('guarantee');
        if ( $guarantee == 'false' || $can_claim == 'false') {
            if ( substr($conditions,0,1) == '-' ) $conditions = PHP_EOL.PHP_EOL.$conditions; // put blank lines in front of warnings we're about to add
            if ( $guarantee == 'false' && strpos($conditions, self::CONDITIONS_NO_GUARANTEE) === false ) {
                $conditions = self::CONDITIONS_NO_GUARANTEE.' '.$conditions;
            }
            if ( $can_claim == 'false' && strpos($conditions, self::CONDITIONS_NO_CLAIM) === false ) {
                $conditions = self::CONDITIONS_NO_CLAIM.' '.$conditions;
            }
            $this->set(['conditions'=>$conditions]);
        }
    }

    protected function getNetworkDefaults() {
        $adata = array_filter($this->getProgram()->getNetwork()->get());
        $ndata = array_filter($this->getProgram()->getNetwork()->getNetworkType()->get());
        $award_days = $adata['award_days'] ?? $ndata['award_days'] ?? 60;
        $claim_days = $adata['claim_days'] ?? $ndata['claim_days'] ?? 7;
        $claim_response_days = $adata['claim_response_days'] ?? $ndata['claim_response_days'] ?? 80;
        $can_claim = $adata['can_claim'] ?? $ndata['can_claim'] ?? "true";

        return [
            'award_days'=>$award_days,
            'claim_days'=>$claim_days,
            'claim_response_days'=>$claim_response_days,
            'can_claim'=>$can_claim,
        ];
    }

    protected function getCategoryDefaults() {
        if ( !$this->get('category_id') ) return [];
        $categoryObj = $this->getCategory()->read($this->get('category_id'));
        $category = array_filter($categoryObj->get());
        $parent = array_filter($categoryObj->getParent()->get());
        $grandparent = array_filter($categoryObj->getParent()->getParent()->get());
        $max_trans_per_user = $category['max_trans_per_user'] ?? $parent['max_trans_per_user'] ?? $grandparent['max_trans_per_user'] ?? null;
        $max_per_day = $category['max_per_day'] ?? $parent['max_per_day'] ?? $grandparent['max_per_day'] ?? null;
        $max_per_period_days = $category['max_per_period_days'] ?? $parent['max_per_period_days'] ?? $grandparent['max_per_period_days'] ?? null;
        $can_claim = $category['can_claim'] ?? $parent['can_claim'] ?? $grandparent['can_claim'] ?? null;
        $response_id = $category['response_id'] ?? $parent['response_id'] ?? $grandparent['response_id'] ?? 140;
        $response = new Response($this->container, $response_id);
        if ( !$conditions = $response->get('response_1') ) {
            throw new \Exception('Error fetching conditions (response_id '.$response_id.')');
        }
        return array_filter([
            'max_trans_per_user'=>$max_trans_per_user,
            'max_per_day'=>$max_per_day,
            'max_per_period_days'=>$max_per_period_days,
            'can_claim'=>$can_claim,
            'conditions'=>$conditions,
        ]);
    }
}