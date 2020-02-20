<?php
use Psr\Container\ContainerInterface;
use im\model\User;

class ApiController {
    protected $container;
    protected $db;

    // constructor receives container instance
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->db = $container->get('db');
    }

    public function ping($request, $response, $args) {
        $result = ['version'=>'1.0'];
        return $response->withJson($result);
    }

    public function health($request, $response, $args) {
        try {
            // Test out database by checking RY balance
            file_put_contents('/tmp/h','START');
            $user = new User($this->db, 450);
            file_put_contents('/tmp/h',"\nLN".__LINE__,FILE_APPEND);
            $user->getBbusersx()->checkBalance();
            file_put_contents('/tmp/h',"\nLN".__LINE__,FILE_APPEND);
            $result = ['status' => 'pass'];
            file_put_contents('/tmp/h',"\nLN".__LINE__,FILE_APPEND);
        } catch (Exception $e) {
            $result = [
                'status'    => ( $e->getCode() < 400 ) ? 'warn' : 'fail',
                'notes'     => $e->getMessage(),
            ];
            file_put_contents('/tmp/h',"\nLN".__LINE__,FILE_APPEND);
            $this->container['logger']->critical('imutual API health endpoint',$result);
        }
        return $response->withJson($result);
    }

    public function test($request, $response, $args) {
        $identity = new Starling\Identity('nZrtXmimG8oVAk2CrR2fKNkMhGMttmpPfUZoSwDgezewA85oKj7MyJjaJSdWMpa9');
        $client = new Starling\Api\Client($identity, ['env' => 'prod']);
        $request = new Starling\Api\Request\Accounts\Balance();

        $result = $client->request($request);
        return $response->withJson( json_decode((string) $result->getBody(), true) );
    }
}

