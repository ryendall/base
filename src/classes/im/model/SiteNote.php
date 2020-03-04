<?php
namespace im\model;

class SiteNote extends Base {

    const DB_TABLE = 'm_site_note';
    const PRIMARY_KEYS = ['note_id'];
    const DB_MODEL = [
        'note_id'    => ["type"=>"key"],
        'site_id'    => ["type"=>"num"],
        'note_time'  => ["type"=>"num"],
        'note'       => ["type"=>"txt", "input"=>"textarea"],
        'user_id'    => ["type"=>"num"],
        'date_added' => ["type"=>"dat"],
    ];
}