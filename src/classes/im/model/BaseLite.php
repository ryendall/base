<?php
namespace im\model;
use \Exception;

class BaseLite {

    protected function sql_query($sql) {

        try {
            $result = $this->db->sql_query($sql);
            return $result;
        } catch (Exception $e) {
            $tmp = debug_backtrace()[0];
            trigger_error('SQL error on line '.$tmp['line'].' of '.$tmp['file'].': '.$this->db->sql_error()['message']);
            trigger_error($sql);
            return false;
        }
    }

    protected function sql_rowset($sql) {
        $result=$this->sql_query($sql);
        $rows = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rows;
    }

    protected function error($msg='',$code=500) {
        $tmp = debug_backtrace()[0];
        trigger_error("Error on line ".$tmp['line'].' of '.$tmp['file'].': '.$msg);
        throw new \Exception($msg,$code);
    }


}