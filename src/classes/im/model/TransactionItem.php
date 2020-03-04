<?php
namespace im\model;

class TransactionItem extends Base {

    const PRIMARY_KEYS = ['trans_id'];

    /**
     * Create related entry in trans_text table before inserting main DB record
     */
    public function create(array $data=null, bool $exceptionOnError=false) {
        if ( is_array($data) && !isset($data['text_id']) ) {
            if ( empty($data['trans_text']) ) {
                $data['text_id'] = null;
            } else {
                $tt = [
                    'aff_id'    => $data['aff_id'],
                    'reference' => $data['reference'],
                    'trans_text'=> $data['trans_text'],
                ];
                $text = new TransText($this->container);
                if ( $data['text_id'] = $text->create($tt) ) {
                    unset($data['trans_text']);
                }
            }
        }
        return parent::create($data, $exceptionOnError);
    }

    /**
     * Populate imutual id values e.g. clickId by matching new records to original ones by reference
     */
    public function matchOldToNewByReference() {
        $sql = 'SELECT t2.*, t.trans_id as new_id FROM '.static::DB_TABLE.' t, '.static::DB_TABLE.' t2 WHERE t.pstatus_id = 0 AND (t.vtrans_id IS NULL OR t.vtrans_id = 0) AND t.reference IS NOT NULL AND t.reference = t2.reference AND t2.click_id > 0 GROUP BY t.trans_id';
        if ( !$result = $this->db->sql_query($sql) ) throw new Exception($this->db->sql_error()['message'].': '.$sql);
        $rows=$this->db->sql_fetchrowset($result);
        foreach($rows as $r) {
            $sql = 'UPDATE '.static::DB_TABLE.' SET click_id = '.$r['click_id']
            .', vtrans_id = IF(vtrans_id>0,vtrans_id,'.$r['vtrans_id'].')'
            .', site_id = IF(site_id>0,site_id,'.$r['site_id'].')'
            .', user_id = IF(user_id>0,user_id,'.$r['user_id'].')'
            .' WHERE trans_id = '.$r['new_id'];
            if ( !$result = $this->db->sql_query($sql) ) throw new Exception($this->db->sql_error()['message'].': '.$sql);
        }
        return count($rows);
    }
}