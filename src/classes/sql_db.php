<?php

class sql_db {

    var $db_connect_id;
    var $query_result;
    var $row = array();
    var $rowset = array();
    var $num_queries = 0;

    //
    // Constructor
    //
    function __construct($sqlserver, $sqluser, $sqlpassword, $database) {

        $this->user = $sqluser;
        $this->password = $sqlpassword;
        $this->server = $sqlserver;
        $this->dbname = $database;

        $this->db_connect_id = mysqli_connect($this->server, $this->user, $this->password);
        if($this->db_connect_id)
        {
            if($database != "")
            {
                $this->dbname = $database;
                $dbselect = @mysqli_select_db($this->db_connect_id, $this->dbname);
                if(!$dbselect)
                {
                    @mysqli_close($this->db_connect_id);
                    $this->db_connect_id = $dbselect;
                }
            }
            return $this->db_connect_id;
        }
        else
        {
            throw new Exception('Failed to connect to database');
        }
    }

    //
    // Other base methods
    //
    function sql_close()
    {
        if($this->db_connect_id)
        {
            if($this->query_result)
            {
                @mysqli_free_result($this->query_result);
            }
            $result = @mysqli_close($this->db_connect_id);
            return $result;
        }
        else
        {
            return false;
        }
    }

    //
    // Base query method
    //
    function sql_query($query = "", $transaction = FALSE)
    {

        // Remove any pre-existing queries
        unset($this->query_result);
        if($query != "")
        {
            $this->num_queries++;
            $start = time();
            $this->query_result = mysqli_query($this->db_connect_id, $query);
            if ( !$this->query_result ) {
                trigger_error($query."\n".@mysqli_error($this->db_connect_id));
            } else {
                $finish = time();
                $duration = $finish - $start;
                if ( $duration > 10 ) {
                    $tmp = debug_backtrace()[0];
                    file_put_contents('/tmp/slow'.date('Ym').'.log',"\n".$duration.'s: LN'.$tmp['line'].' of '.$tmp['file'].' on '.date('Y-m-d H:i'),FILE_APPEND);
                    file_put_contents('/tmp/slow'.date('Ym').'.log',"\n".$duration.'s: '.$query,FILE_APPEND);
                }
            }
        }
        return $this->query_result ?? false;
    }

    //
    // Other query methods
    //
    function sql_numrows($query_id = 0)
    {
        if(!$query_id)
        {
            $query_id = $this->query_result;
        }
        if($query_id)
        {
            $result = mysqli_num_rows($query_id);
            if ( $result === null ) {
                $trace = debug_backtrace()[0];
                trigger_error($trace['file'].' line:'.$trace['line'].' result:'.json_encode($result));
            }
            return $result;
        }
        else
        {
            return false;
        }
    }
    function sql_affectedrows()
    {
        if($this->db_connect_id)
        {
            $result = mysqli_affected_rows($this->db_connect_id);
            return $result;
        }
        else
        {
            return false;
        }
    }
    function sql_numfields($query_id = 0)
    {
        if(!$query_id)
        {
            $query_id = $this->query_result;
        }
        if($query_id)
        {
            $result = mysqli_field_count($query_id);
            return $result;
        }
        else
        {
            return false;
        }
    }
    function sql_fieldname($offset, $query_id = 0)
    {
        if(!$query_id)
        {
            $query_id = $this->query_result;
        }
        if($query_id)
        {
            $fieldInfo = mysqli_fetch_field_direct($query_id, $offset);
            return $fieldInfo->name;
        }
        else
        {
            return false;
        }
    }
    function sql_fieldtype($offset, $query_id = 0)
    {
        if(!$query_id)
        {
            $query_id = $this->query_result;
        }
        if($query_id)
        {
            $type_id = mysqli_fetch_field_direct($query_id, $offset)->type;
            $types = array();
            $constants = get_defined_constants(true);
            foreach ($constants['mysqli'] as $c => $n)
             if (preg_match('/^MYSQLI_TYPE_(.*)/', $c, $m))
              $types[$n] = $m[1];
            $resultType = array_key_exists( $type_id, $types ) ? $types[$type_id] : NULL;
            return resultType;
        }
        else
        {
            return false;
        }
    }
    function sql_fetchrow($result)
    {
        if ( $this->query_result != $result ) {
            $this->query_result = $result;
        }
        if ( !is_object($this->query_result) || get_class($this->query_result) != 'mysqli_result' ) {
            $trace = debug_backtrace()[0];
            trigger_error($trace['file'].' line:'.$trace['line'].' not mysqli_result:'.json_encode($this->query_result));
            return false;
        }
        $row = mysqli_fetch_array($this->query_result);
        return $row;
    }
    function sql_fetchrowset($query_result, $assoc = true)
    {
        $tmp = debug_backtrace()[0];
        $msg = "\nLN".$tmp['line'].' of '.$tmp['file'];
        $result=[];
        if($query_result)
        {
            $function = ($assoc) ? 'mysqli_fetch_assoc' : 'mysqli_fetch_array';
            while($row = $function($query_result))
            {
                $result[] = $row;
            }
        }
        return $result;
    }
    function sql_fetchfield($field, $rownum = -1, $query_id = 0)
    {
        throw new Exception('sql_fetchfield not redone for mysqli');
        if(!$query_id)
        {
            $query_id = $this->query_result;
        }
        if($query_id)
        {
            if($rownum > -1)
            {
                $result = @mysql_result($query_id, $rownum, $field);
            }
            else
            {
                if(empty($this->row[$query_id]) && empty($this->rowset[$query_id]))
                {
                    if($this->sql_fetchrow())
                    {
                        $result = $this->row[$query_id][$field];
                    }
                }
                else
                {
                    if($this->rowset[$query_id])
                    {
                        $result = $this->rowset[$query_id][$field];
                    }
                    else if($this->row[$query_id])
                    {
                        $result = $this->row[$query_id][$field];
                    }
                }
            }
            return $result;
        }
        else
        {
            return false;
        }
    }
    function sql_rowseek($rownum, $query_id = 0){
        if(!$query_id)
        {
            $query_id = $this->query_result;
        }
        if($query_id)
        {
            $result = @mysqli_data_seek($query_id, $rownum);
            return $result;
        }
        else
        {
            return false;
        }
    }
    function sql_nextid(){
        if($this->db_connect_id)
        {
            $result = @mysqli_insert_id($this->db_connect_id);
            return $result;
        }
        else
        {
            return false;
        }
    }
    function sql_freeresult($query_id = 0){
        if(!$query_id)
        {
            $query_id = $this->query_result;
        }

        if ( $query_id )
        {
            /*
            if ( isset($this->row) && isset($this->row[$query_id]) ) {
                unset($this->row[$query_id]);
            }
            if ( isset($this->rowset) && isset($this->rowset[$query_id]) ) {
                unset($this->rowset[$query_id]);
            }
            */
            if ( get_class($query_id) == 'mysqli_result' ) {
                @mysqli_free_result($query_id);
            }

            return true;
        }
        else
        {
            return false;
        }
    }
    function sql_error($query_id = 0)
    {
        $result["message"] = @mysqli_error($this->db_connect_id);
        $result["code"] = @mysqli_errno($this->db_connect_id);

        return $result;
    }

    function sql_escape_string($string) {
        return mysqli_real_escape_string($this->db_connect_id, $string);
    }
} // class sql_db
