<?php
namespace im\model;

class Domain extends Base {

    const DB_TABLE = 'domain';
    const PRIMARY_KEYS = ['domain_id'];
    const TITLE_FIELD = 'domain';
    const DB_LISTS = [
        1 => ['host'=>'host', 'page'=>'page', 'full'=>'full', 'ignore'=>'ignore', 'first_var'=>'first_var'],
    ];

    const DB_MODEL = [
        'domain_id'  => ["type"=>"key"],
        'domain'     => ["type"=>"txt", "required"=>true, 'transform'=>'stripWww', 'mode'=>'read'],
        'match_part' => ["type"=>"txt", "default"=>"host", "list"=>1],
        'aff_id'     => ["type"=>"num", 'class'=>'Network', 'lookup'=>true, 'onChange'=>'networkChanged'],
        'site_id'    => ["type"=>"num", 'finder'=>'Site', 'onChange'=>'siteChanged'],
    ];

    /**
     * Custom version of base function
     * If domain not found, it searches database for related domain with site_id set
     * If related domain found and associated with single site, new domain will inherit site_id value
     */
    public function findOrCreate(array $data) {
        if ( !$this->findOne($data) ) {
            if ( empty($data['site_id']) ) {
                // Can we identify site from related domains?
                if ( $parentDomain = \Helpers::hasParentDomain($data['domain']) ) {
                    $parent = new Domain($this->container);
                    if ( $parent->findOne(['domain'=>$parentDomain]) ) {
                        // inherit site from parent
                        $data['site_id'] = $parent->get('site_id');
                    }
                } else {
                    // This is a parent domain. See if subdomain already exists
                    $sql = 'SELECT site_id, COUNT(DISTINCT site_id) as sites FROM '.static::DB_TABLE.' WHERE domain LIKE "%.'.$this->db->sql_escape_string($data['domain']).'" AND site_id IS NOT NULL HAVING sites = 1';
                    $result=$this->db->sql_query($sql);
                    if ($row = $this->db->sql_fetchrow($result) ) {
                        $data['site_id'] = $row['site_id'];
                    }
                }
            }
            return $this->create($data);
        }
        return $this->id();
    }

    /**
     * Remove www. prefix from hostname
     */
    protected function stripWww(string $text) {
        $text = strtolower($text);
        if ( substr($text,0,4) === 'www.'  ) {
            $text = substr($text,4);
        }
        return $text;
    }

    /**
     * Additional actions when we associate domain with a network
     */
    protected function networkChanged() {
        // Associate past email addresses with this network
        $email = new EmailAddress($this->container);
        foreach( $email->find(['domain_id'=>$this->id(),'aff_id'=>null]) as $r) {
            $email->read($r['id']);
            $email->update(['aff_id'=>$this->get('aff_id')]);
        }
    }

    /**
     * Additional actions when we associate domain with a site
     */
    protected function siteChanged() {
        // Associate past email addresses with this site
        $email = new EmailAddress($this->container);
        foreach( $email->find(['domain_id'=>$this->id(),'site_id'=>null]) as $r) {
            $email->read($r['id']);
            $email->update(['site_id'=>$this->get('site_id')]);
        }
    }


}
