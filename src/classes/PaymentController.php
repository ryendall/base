<?php
use Psr\Container\ContainerInterface;

class PaymentController {

    const BANK_ACCOUNT_ID = 2; // revolut
    const ECASHIER_ID = 2; // revolut

    protected $container;
    protected $db;
    protected $bankAccount; // imutual a/c used to process payments
    protected $bankApi;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->db = $container->get('db');
        $this->bankAccount = new BankAccount($this->db, self::BANK_ACCOUNT_ID);
        $this->bankApi = $this->bankAccount->getBankApi();
    }

    /**
     * Create target account for payment via bank API
     * @var array $input
     * Input format is:
     * {
     *  "account_number": 12345678,
     *  "sortcode": 123456,
     * And one of the following:
     *  "user_id": 12345,
     *  "name": 'John Doe',
     * }
     */
    public function addAccount($request, $response, $args) {
        if ( !$input = $request->getParsedBody() ) {
            return $response->withJson(['error' => json_last_error_msg()],400);
        }

        $accountData = [
            'ecashier_id' => self::ECASHIER_ID,
            'account_number' => Transform::ukBankAccountNumber($input['account_number']),
            'sortcode' => Transform::ukSortCode($input['sortcode']),
        ];
        if ( !empty($input['user_id']) ) {
            $user = new Bbusersx($this->db, $input['user_id']);
            $accountData['user_id'] = $input['user_id'];
            $input['name'] = $input['name'] ?? $user->get('full_name') ?? null;
        }
        if ( empty($input['name']) ) return $response->withStatus(400)->withJson(['error'=>'Missing name']);
        $account = new Account($this->db);
        $account->create($accountData, true);

        // Add counterparty to bank Api
        $cp = [
            'name' => $input['name'],
            'sortcode' => $account->get('sortcode'),
            'account_number' => $account->get('account_number'),
        ];
        if ( !$uuid = $this->bankApi->addCounterparty($cp) ) return $response->withStatus(500)->withJson(['error'=>'Failed to add counterparty']);

        // Update newly-created account with bank reference and encrypt
        $account->encrypt($uuid)->update(null, true);
        $result = ['account_id'=>$account->get('account_id')];
        return $response->withStatus(201)->withJson($result);
    }

    /**
     * Send user payment via external payment method
     * @var array $input
     * @return array $result
     * Input format is:
     * {
     *  "user_id": 12345,
     *  "account_id": 12345678,
     *  "amount": 123.4,
     *  "reference": "myRef" // optional
     *  "auto_redeem": 1.00 // optional
     * }
     * Response format is:
     * {
     *  "redeem_id": 12345,
     *  "status": 30
     * }
     */
    public function payUser($request, $response, $args) {
        // Validate inputs
        $input = $request->getParsedBody();
        /* Now done by Base/Payment
        if ( !is_numeric($input['amount']) ) throw new Exception('Invalid amount', 400);
        $user = new Bbusersx($this->db, $input['user_id']);
        $balanceInPounds = $user->balanceInPounds();
        if ( $input['amount'] > $balanceInPounds ) throw new Exception('User has insufficient funds', 400);
        $account = new Account($this->db, $input['account_id']);
        if ( $account->get('user_id') != $input['user_id'] )  throw new Exception('Account/User mismatch', 400);
        if ( empty($account->get('uuid')) )  throw new Exception('Account has no counterparty_id', 400);
        */

        $payment = new Payment($this->db);
        $payment->create([
            'user_id'           => $input['user_id'],
            'account_id'        => $input['account_id'],
            'points'            => $input['amount']*100,
            'status'            => Payment::STATUS['in_progress'],
            'redeem_time'       => time(),
            'ecashier_id'       => self::ECASHIER_ID,
            'auto_redeem'       => $input['auto_redeem'] ?? null,
            'user_reference'    => $input['reference'] ?? null,
        ], true);

        $result = $this->pay($payment);
        $code = ( isset($result['error']) ) ? 202 : 201;
        return $response->withStatus($code)->withJson($result);
    }

    /**
     * Use details of pending payment to send funds via external API
     * Then use Api response to update payment status
     * @return array $result
     */
    protected function pay(Payment $payment) {
        $redeem_id = $payment->getKey()['redeem_id'];
        $result=['redeem_id'=>$redeem_id, 'status'=>$payment->get('status')];

        $request_id = Payment::BANK_REFERENCE_PREFIX.$redeem_id;
        $payRequest = [
            'request_id'        => $request_id,
            'amount'            => $payment->getAmount(),
            'counterparty_id'   => $payment->getObject('account_id')->get('uuid'),
            'reference'         => $payment->get('user_reference') ?? $request_id,
        ];
        try {
            $apiResponse = $this->bankApi->pay($payRequest);
            $result['status'] = Payment::bankStateToPaymentStatus($apiResponse['state']);
        } catch (Exception $e) {
            // Payment failed. Leave as "in progress" and return a 202 "Processing" response
            $this->container['logger']->critical($e->getMessage(),$payRequest);
            $result['error'] = $e->getMessage();
            return $result;
        }

        $payment->update([
            'uuid'          => $apiResponse['uuid'],
            'status'        => $result['status'],
            'process_time'  => time(),
        ], true);
        return $result;
    }

    public function sendPayment($request, $response, $args) {
        // Validate inputs
        $payment = new Payment($this->db, $args['id']);
        if ( $payment->get('status') != Payment::STATUS['in_progress'] ) throw new Exception('Payment not in progress', 403);

        $result = $this->pay($payment);
        $code = ( isset($result['error']) ) ? 500 : 200;
        return $response->withStatus($code)->withJson($result);
    }

    public function getTransaction($request, $response, $args) {
        // Validate inputs
        $payment = new Payment($this->db, $args['id']);
        if ( in_array($payment->get('status'), [
            Payment::STATUS['in_progress'],
            Payment::STATUS['paid_unconfirmed']
        ]) && $payment->get('uuid') ) {
            // Check with bank to see if payment has completed/failed
            $bankTransaction = $this->bankApi->getTransaction($payment->get('uuid'));
            $new_payment_status = Payment::bankStateToPaymentStatus($bankTransaction['state']);
            if ( $new_payment_status != $payment->get('status') ) {
                $payment->update(['status'=>$new_payment_status], true);
            }
        }
        return $response->withStatus(200)->withJson($payment->get());
    }

}

