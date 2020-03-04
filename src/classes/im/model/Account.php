<?php
namespace im\model;
use im\helpers\Validate;

class Account extends Base {

    const DB_TABLE = 'm_account';
    const PRIMARY_KEYS = ['account_id'];
    const DB_LISTS = [
        1 => ['false'=>'False', 'true'=>'True'],
    ];

    const DB_MODEL = [
        'account_id'        => ["type"=>"key"],
        'user_id'           => ["type"=>"num"],
        'ecashier_id'       => ["type"=>"num"],
        'sortcode'          => ["type"=>"txt"],
        'account_number'    => ["type"=>"txt"],
        'account'           => ["type"=>"txt"],
        'encrypted'         => ["type"=>"txt"],
        'id_provided'       => ["type"=>"txt", "default"=>"false", "list"=>1],
        'uuid'              => ["type"=>"txt"],
    ];

    /**
     * Set uuid, replace clear text account details with hash
     */
    public function encrypt($uuid) {
        $data = [
            'uuid'=>Validate::uuid($uuid),
            'encrypted'=>$this->encryptedAccountDetails(),
            'sortcode'=>null,
            'account_number'=>'xxxxx'.substr($this->get('account_number'),-3),
        ];
        return $this->set($data);
    }

    protected function encryptedAccountDetails() {
        return md5($this->get('sortcode').$this->get('account_number'));
    }
}