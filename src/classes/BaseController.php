<?php
use Psr\Container\ContainerInterface;

class BaseController {

    protected $container;
    protected $db;
    protected $model;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->db = $container->get('db');
        $this->setModel();
    }

    /**
     * Instantiates data model object
     * Class name derived from controller child class
     */
    protected function setModel() {
        $class = str_replace('Controller','',get_class($this));
        $fqn = 'im\\model\\'.$class;
        if  ( !class_exists($fqn) || !is_subclass_of($fqn,'im\model\Base') ) {
            throw new Exception('Invalid class');
        }
        $this->model = new $fqn($this->db);
    }

    /**
     * Create new record
     * @var array $input
     */
    public function create($request, $response, $args) {
        if ( !$input = $request->getParsedBody() ) {
            return $response->withJson(['error' => $this->jsonError()],400);
        }
        try {
            $id = $this->getModel()->create($input, true);
            $result = ['id'=>$id];
            $code = 201;
        } catch(Exception $e) {
            $code = $e->getCode();
            $result = ['error'=>$this->exceptionMessage($e)];
        }
        return $response->withStatus($code)->withJson($result);
    }

    /**
     * Update existing record
     * @var array $input
     */
    public function update($request, $response, $args) {
        if ( !$input = $request->getParsedBody() ) {
            return $response->withJson(['error' => $this->jsonError()],400);
        }
        try {
            $id = $this->getModel()->read($args['id'])->update($input, true);
            $result = ['id'=>$id];
            $code = 200;
        } catch(Exception $e) {
            $code = $e->getCode();
            $result = ['error'=>$this->exceptionMessage($e)];
        }
        return $response->withStatus($code)->withJson($result);
    }

    /**
     * Read existing record
     * @var int $id
     */
    public function read($request, $response, $args) {
        try {
            $result = $this->getModel()->read($args['id'])->get();
            $code = 200;
        } catch(Exception $e) {
            $code = $e->getCode();
            $result = ['error'=>$this->exceptionMessage($e)];
        }
        return $response->withStatus($code)->withJson($result);
    }

    /**
     * Delete existing record
     * @var int $id
     */
    public function delete($request, $response, $args) {
        try {
            $result = $this->getModel()->read($args['id'])->delete();
            $code = 200;
        } catch(Exception $e) {
            $code = $e->getCode();
            $result = ['error'=>$this->exceptionMessage($e)];
        }
        return $response->withStatus($code)->withJson($result);
    }

    public function list($request, $response, $args) {
        try {
            $result = $this->getModel()->listData();
            $code = 200;
        } catch(Exception $e) {
            $code = $e->getCode();
            $result = ['error'=>$this->exceptionMessage($e)];
        }
        return $response->withStatus($code)->withJson($result);
    }

    protected function getModel() {
        return $this->model;
    }

    protected function jsonError() {
        $msg = json_last_error_msg();
        if ( $msg == 'No error' ) $msg = 'Bad input';
        return $msg;
    }

    protected function exceptionMessage(Exception $e) {
        if ( $msg = $e->getMessage() ) {
            $decoded = json_decode($msg);
            if ( $decoded && json_last_error() == JSON_ERROR_NONE ) {
                $msg = $decoded;
            }
            return $msg;
        }
        switch($e->getCode()) {
            case '401':
                $msg = 'Unauthorized';
                break;
            case '404':
                $msg = 'Not found';
                break;
            default:
                $msg = 'An error occurred';
                break;
        }
        return $msg;
    }
}

