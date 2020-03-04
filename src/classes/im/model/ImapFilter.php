<?php
namespace im\model;

class ImapFilter extends Base {

    const DB_TABLE = 'imap_filter';
    const PRIMARY_KEYS = ['filter_id'];
    const TITLE_FIELD = 'title';
    const DB_LISTS = [
        1 => ['active'=>'Active', 'inactive'=>'Inactive'],
    ];

    const DB_MODEL = [
        'filter_id'       => ["type"=>"key"],
        'status'          => ["type"=>"txt", "default"=>"active", "list"=>1],
        'title'           => ["type"=>"txt", "required"=>true],
        'from_folder_id'  => ["type"=>"num", "default"=>ImapFolder::INBOX_FOLDER_ID],
        'to_folder_id'    => ["type"=>"num", "required"=>true, 'class'=>'ImapFolder', 'lookup'=>true],
        'text_match'      => ["type"=>"txt", "required"=>true, 'transform'=>'toLowerCase'],
        'priority'        => ["type"=>"num"],
        'last_updated'    => ["type"=>"dat", "default"=>'{NOW}'],
        'aff_id'          => ["type"=>"num", 'class'=>'Network', 'lookup'=>true],
        'last_email_time' => ["type"=>"dat"],
    ];

    protected function listFields() {
        return ['text_match','status','aff_id','to_folder_id','last_email_time'];
    }

    protected function headerLinks() {
        $links = parent::headerLinks();
        if ( $this->get('status') <= 15 ) {
            $links[] = ['label'=>'Matches','url'=>'/rpt/shw.php?id=1065&filter_id='.$this->id().'&text_match='.$this->get('text_match')];
        }
        return $links;
    }

    protected function toLowerCase(string $text) {
        return strtolower($text);
    }
}