<?php
namespace im\model;

class BankAccount extends Base {

    const DB_TABLE = 'bank_account';
    const PRIMARY_KEYS = ['bank_account_id'];
    const DB_LISTS = [
        1 => ['revolut'=>'Revolut', 'other'=>'Other'],
    ];

    const DB_MODEL = [
        'bank_account_id' => ["type"=>"key"],
        'title'           => ["type"=>"txt", "required"=>true],
        'sortcode'        => ["type"=>"txt", "required"=>true],
        'account_number'  => ["type"=>"txt", "required"=>true],
        'uuid'            => ["type"=>"txt"],
        'balance'         => ["type"=>"num", "required"=>true, "scale"=>2],
        'last_updated'    => ["type"=>"dat", "default"=>"{NOW}"],
        'bank'            => ["type"=>"txt", "default"=>"other", "list"=>1],
        'api_class'       => ["type"=>"txt"],
        'oauthCode'       => ["type"=>"txt"],
        'oauthToken'      => ["type"=>"txt"],
    ];

    public function getBankApi() {
        $class = $this->get('api_class');
        if ( empty($class) || !class_exists($class) ) throw new \Exception('No api class for '.$this->get('title').print_r($this->get()));
        return new $class($this);
    }
}