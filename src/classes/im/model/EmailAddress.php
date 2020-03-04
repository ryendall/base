<?php
namespace im\model;

class EmailAddress extends Base {

    const DB_TABLE = 'email_address';
    const PRIMARY_KEYS = ['id'];
    const DB_MODEL = [
        'id'        => ["type"=>"key"],
        'username'  => ["type"=>"txt", "required"=>true],
        'domain_id' => ["type"=>"num", "required"=>true, 'class'=>'Domain'],
        'aff_id'    => ["type"=>"num", 'class'=>'Network', 'onChange'=>'networkChanged'],
        'site_id'   => ["type"=>"num", 'onChange'=>'siteChanged'],
    ];

    protected function headerLinks() {
        $links = parent::headerLinks();
        $links[] = ['label'=>'Matches','url'=>'/rpt/shw.php?id=1066&email_address_id='.$this->id().'&sender='.$this->emailAddress()];
        return $links;
    }

    public function emailAddress() {
        return $this->get('username').'@'.$this->getObject('domain_id')->get('domain');
    }

    /**
     * Additional actions when we associate email address with a site
     */
    protected function siteChanged() {
        // Associate past emails with this site
        $imap = new Imap($this->container);
        foreach( $imap->find(['email_address_id'=>$this->id(),'site_id'=>null]) as $r) {
            $imap->read($r['imap_id']);
            $imap->update(['site_id'=>$this->get('site_id')]);
        }
    }

    /**
     * Additional actions when we associate email address with a network
     */
    protected function networkChanged() {
        // Associate past emails with this network
        $imap = new Imap($this->container);
        foreach( $imap->find(['email_address_id'=>$this->id(),'aff_id'=>null]) as $r) {
            $imap->read($r['imap_id']);
            $imap->update(['aff_id'=>$this->get('aff_id')]);
        }
    }

    /**
     * Return list of sites associated with this email address
     * @return array $siteData
     * e.g. [['site_id'=>1, 'name'=>'My site']]
     */
    public function linkedSites() {
        $this->mustBeLoaded();
        $sql = 'SELECT distinct i.site_id, s.'.Site::TITLE_FIELD.' as name FROM '.Imap::DB_TABLE.' i, '.Site::DB_TABLE.' s WHERE i.email_address_id = '.$this->id().' AND i.site_id = s.site_id ORDER BY s.title';
        $result = $this->db->sql_query($sql);
        return $this->db->sql_fetchrowset($result);
    }
}