<?php
namespace im\model;

class SenderName extends Base {

    const DB_TABLE = 'sender_name';
    const TITLE_FIELD = 'sender_name';
    const PRIMARY_KEYS = ['id'];
    const DB_MODEL = [
        'id'            => ["type"=>"key"],
        'sender_name'   => ["type"=>"txt", "required"=>true, 'unique'=>true],
        'site_id'       => ["type"=>"num", "required"=>true, 'finder'=>'Site','onChange'=>'siteChanged'],
    ];

    /**
     * Additional actions when we associate email sender with a site
     */
    protected function siteChanged() {
        // Associate past emails with this site
        $imap = new Imap($this->container);
        foreach( $imap->find(['sender_name'=>$this->name(),'site_id'=>null]) as $r) {
            $imap->read($r['imap_id']);
            $imap->update(['site_id'=>$this->get('site_id')]);
        }
    }
}