<?php
namespace im\model;

class Bank extends Base {

    const DB_TABLE = 'bank';
    const PRIMARY_KEYS = ['id'];
    const DB_LISTS = [
        1 => ['pending'=>'Pending', 'processed'=>'Processed', 'ignore'=>'Ignore'],
        2 => ['pending'=>'Pending', 'completed'=>'Completed', 'cancelled'=>'Cancelled'],
    ];

    const DB_MODEL = [
        'id'                => ["type"=>"key"],
        'trans_date'        => ["type"=>"dat", "required"=>true],
        'reference'         => ["type"=>"txt"],
        'debit'             => ["type"=>"num", "scale"=>2],
        'credit'            => ["type"=>"num", "scale"=>2],
        'balance'           => ["type"=>"num", "scale"=>2],
        'amount'            => ["type"=>"num", "required"=>true, "scale"=>2],
        'status'            => ["type"=>"txt", "default"=>"pending", "list"=>1],
        'bank_type_id'      => ["type"=>"num"],
        'aff_id'            => ["type"=>"num"],
        'type_code'         => ["type"=>"txt"],
        'payee_id'          => ["type"=>"num"],
        'notes'             => ["type"=>"txt"],
        'vat'               => ["type"=>"num", "scale"=>2],
        'vat_rate'          => ["type"=>"num", "scale"=>2],
        'bank_account_id'   => ["type"=>"num", "required"=>true],
        'uuid'              => ["type"=>"txt"],
        'counterparty_uuid' => ["type"=>"txt"],
        'state'             => ["type"=>"txt", "list"=>2, 'onChange'=>'updatePayment'],
        'redeem_id'         => ["type"=>"num"],
    ];

    const ACCOUNT_IDS = [
        'lloyds' => 1,
        'revolut' => 2,
    ];

    protected function listFields() {
        return ['trans_date','reference','amount'];
    }

    protected function updatePayment() {
        if ( $redeem_id = $this->get('redeem_id') ) {
            $payment = new Payment($this->container, $redeem_id);
            if ( $new_payment_status = Payment::bankStateToPaymentStatus($this->get('state')) ) {
                $payment->update(['status' =>$new_payment_status]);
            }
        }
    }

    public static function getPaymentIdFromReference($reference) {
        if ( preg_match('/^(\d*)$/', $reference, $matches) ) {
            return $matches[1];
        } else {
            return false;
        }
    }
}