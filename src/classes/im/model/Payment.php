<?php
namespace im\model;

class Payment extends Base {

    const DB_TABLE = 'm_redeem';
    const PRIMARY_KEYS = ['redeem_id'];
    const DB_LISTS = [
        1 => ['true'=>'True', 'false'=>'False'],
    ];

    const DB_MODEL = [
        'redeem_id'         => ["type"=>"key"],
        'redeem_time'       => ["type"=>"num", "required"=>true],
        'points'            => ["type"=>"num", "required"=>true, 'onChange'=>'userRecalc'],
        'user_id'           => ["type"=>"num", "required"=>true, 'class'=>'Bbusersx'],
        'status'            => ["type"=>"num", "required"=>true, 'onChange'=>'userRecalc'],
        'ecashier_id'       => ["type"=>"num", "required"=>true],
        'process_time'      => ["type"=>"num"],
        'currency_id'       => ["type"=>"num", "default"=>1],
        'account_id'        => ["type"=>"num", "required"=>true, 'class'=>'Account'],
        'payer_id'          => ["type"=>"num"],
        'auto_redeem'       => ["type"=>"num", "scale"=>2],
        'reference'         => ["type"=>"num"], // our ref, not the user's
        'uuid'              => ["type"=>"txt"],
        'user_reference'    => ["type"=>"txt", "regex"=>'/^[a-zA-Z\d\.\-& \/]{0,18}$/'],
    ];

    const BANK_REFERENCE_PREFIX = 'rd';

    const STATUS = [
        'awaiting_invoice'      => 5,
        'pending'               => 10,
        'on_hold'               => 15,
        'in_progress'           => 20,
        'investigating'         => 25,
        'missing'               => 26,
        'paid_unconfirmed'      => 30,
        'paid_auto-confirmed'   => 32,
        'paid_confirmed'        => 35,
        'cancelled'             => 40,
    ];

    protected $user;

    protected function customValidate() {

        if ( $this->isNewRecord() ) {
            // Check user has sufficient balance
            if ( !$user = $this->getObject('user_id') ) {
                $this->setError('user_id', 'User not found');
            } elseif ( $this->get('points')/100 > $user->balanceInPounds() ) {
                $this->setError('points', 'Insufficient funds');
            }
        }

        // Validate payment account
        if ( !$account = $this->getObject('account_id') ) {
            $this->setError('account_id', 'Invalid account');
        } elseif ( $account->get('user_id') != $this->get('user_id') ) {
            $this->setError('account_id', 'Account user mis-match');
        } elseif ( $account->get('ecashier_id') != $this->get('ecashier_id') ) {
            $this->setError('account_id', 'Account type mis-match');
        } elseif ( $account->get('ecashier_id') == 2 && !$account->get('uuid') ) {
            $this->setError('account_id', 'FPI Account has no counterparty_id');
        }
    }

    protected function userRecalc() {
        $this->getObject('user_id')->checkBalance();
    }

    public function getAmount() {
        return round($this->get('points')/100,2);
    }

    /**
     * Mapping of bank.state to m_redeem.status
     */
    public static function bankStateToPaymentStatus($state) {
        switch($state) {
            case 'pending':
            return self::STATUS['paid_unconfirmed'];
            break;

            case 'completed':
            return self::STATUS['paid_auto-confirmed'];
            break;

            case 'cancelled':
            return self::STATUS['cancelled'];
            break;

            default:
            throw new E\xception('Unexpected state: '.$state);
            break;

        }
    }
}
