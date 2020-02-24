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
            // Test out database
            // @todo test somewthing meaningful
            $result = ['status' => 'pass'];
        } catch (Exception $e) {
            $result = [
                'status'    => ( $e->getCode() < 400 ) ? 'warn' : 'fail',
                'notes'     => $e->getMessage(),
            ];
            $this->container['logger']->critical('API health endpoint',$result);
        }
        return $response->withJson($result);
    }
}

