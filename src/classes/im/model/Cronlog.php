<?php
namespace im\model;

class Cronlog extends Base {

    const DB_TABLE = 'cronjob_log';
    const PRIMARY_KEYS = ['cronjob_log_id'];
    const TITLE_FIELD = 'status';
    const DB_LISTS = [
        1 => ['in progress'=>'In progress', 'incomplete'=>'Incomplete', 'complete'=>'Complete', 'errors'=>'Errors', 'stopped'=>'Stopped'],
    ];

    const DB_MODEL = [
        'cronjob_log_id' => ["type"=>"key"],
        'cronjob_id'     => ["type"=>"num", 'class'=>'Cronjob'],
        'process_date'   => ["type"=>"dat", 'default'=>'{NOW}'],
        'start_time'     => ["type"=>"dat", 'default'=>'{NOW}'],
        'end_time'       => ["type"=>"dat"],
        'status'         => ["type"=>"txt", "input"=>"textarea", "default"=>"in progress", "list"=>1],
        'updates'        => ["type"=>"num"],
        'duration'       => ["type"=>"num"],
        'code'           => ["type"=>"num", "default"=>200],
        'reference'      => ["type"=>"num"],
    ];

    const CRONJOB_TABLE ='cronjob';

    protected $cronjob; // im\model\Cronjob
    protected $update_count;

    public function start(string $filename, int $reference=null) {

        $parts =  explode('_',$filename);
        if ( !isset($parts[1]) || !is_numeric($parts[1]) ) {
            throw new Exception('Expects x_99_ filename format in '.$filename);
        }
        $cronjob_id = intval($parts[1]);
        $this->cronjob = new Cronjob($this->container,$cronjob_id);

        $rows = $this->find(['cronjob_id'=>$cronjob_id],[
            'order'=>['process_date'=>'desc'],
            'limit'=>1,
        ]);
        if ( !empty($rows) ) {
            $r=$rows[0];
            if ( $r['status'] == 'errors' ) {
                $msg = 'Previous job produced errors; DELETE FROM '.self::CRONJOBLOG_TABLE.' WHERE cronjob_log_id = '.$r['cronjob_log_id'].';';
                trigger_error($msg);
                exit($msg);
            } elseif ( in_array($r['status'], ['in progress','incomplete']) ) {
                $this->read($r['cronjob_log_id']);
                $this->update(['status'=>'stopped', 'end_time'=>date('c')]);
            }
        }

        $this->create(['cronjob_id' => $cronjob_id, 'reference'=>$reference]);
        $this->getCronjob()->update(['last_started'=>date('c')]);
        return $this->id();
    }

    public function update_count(int $num) {
        $this->update_count += $num;
    }

    public function end(int $code=200, string $error_message = null) {
        if ( $error_message ) {
            $status = 'error';
            $tmp = debug_backtrace()[0];
            $msg = $code." error LN".$tmp['line'].' of '.$tmp['file'].': '.$error_message;
            trigger_error($msg);
        } else {
            $status = 'complete';
        }
        $duration = time() - strtotime($this->get('start_time'));
        $this->update([
            'status'=>$status,
            'updates'=>$this->update_count,
            'code'=>$code,
            'end_time'=>date('c'),
            'duration'=>$duration,
        ]);
        $this->getCronjob()->update(['last_started'=>date('c')]);
    }

    protected function getCronjob() {
        if ( empty($this->cronjob) ) {
            $this->cronjob = $this->getObject('cronjob_id');
        }
        return $this->cronjob;
    }
}