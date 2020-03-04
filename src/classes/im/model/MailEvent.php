<?php
namespace im\model;

class MailEvent extends Base {

    const DB_TABLE = 'mail_event';
    const PRIMARY_KEYS = ['mail_event_id'];
    const DB_LISTS = [
        1 => ['block'=>'block', 'bounce'=>'bounce', 'invalid'=>'invalid', 'spam'=>'spam', 'unsubscribe'=>'unsubscribe'],
        2 => ['pending'=>'pending', 'processed'=>'processed', 'ignored'=>'ignored'],
    ];

    const DB_MODEL = [
        'mail_event_id'  => ["type"=>"key"],
        'created'        => ["type"=>"dat", "required"=>true],
        'email'          => ["type"=>"txt", "required"=>true],
        'reason'         => ["type"=>"txt"],
        'type'           => ["type"=>"txt", "required"=>true, "list"=>1],
        'status'         => ["type"=>"txt"],
        'process_status' => ["type"=>"txt", "default"=>"pending", "list"=>2],
        'email_log_id'   => ["type"=>"num"],
        'user_id'        => ["type"=>"num"],
        'ipn'            => ["type"=>"num"],
    ];
}
?>