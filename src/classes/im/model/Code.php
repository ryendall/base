<?php
namespace im\model;

class Code extends Base {

    const DB_TABLE = 'code';
    const PRIMARY_KEYS = ['id'];
    const DB_LISTS = [
        1 => ['pending'=>'Pending', 'no site'=>'No site', 'error'=>'Error', 'processed'=>'Processed', 'ignored'=>'Ignored', 'expired'=>'Expired'],
        2 => ['url2'=>'Url2', 'url'=>'Url', 'link'=>'Link', 'url_tag'=>'UrlTag', 'title'=>'Title', 'description'=>'Description'],
    ];

    const DB_MODEL = [
        'id'           => ["type"=>"key"],
        'promotion_id' => ["type"=>"num", "required"=>true],
        'aff_id'       => ["type"=>"num", "required"=>true],
        'code'         => ["type"=>"txt", "required"=>true],
        'start_date'   => ["type"=>"dat", "default"=>'{NOW}'],
        'end_date'     => ["type"=>"dat", "required"=>true],
        'title'        => ["type"=>"txt"],
        'description'  => ["type"=>"txt"],
        'terms'        => ["type"=>"txt"],
        'other'        => ["type"=>"txt"],
        'status'       => ["type"=>"txt", "default"=>"pending", "list"=>1],
        'url'          => ["type"=>"txt"],
        'url2'         => ["type"=>"txt"],
        'merchant_id'  => ["type"=>"num"],
        'site_id'      => ["type"=>"num"],
        'error'        => ["type"=>"txt", "list"=>2],
        'url_tag_id'   => ["type"=>"num"],
    ];

    public function unprocessedCodes() {
        $sql = 'select c.id, p.program_id, p.site_id, s.astatus'
        .' FROM '.self::DB_TABLE.' c'
        .' LEFT JOIN '.Program::DB_TABLE.' p ON c.aff_id = p.aff_id AND c.merchant_id = p.network_ref'
        .' LEFT JOIN '.Site::DB_TABLE.' s ON p.program_id = s.program_id'
        .' WHERE c.status = "pending" AND c.start_date < NOW()'
        .' ORDER BY c.aff_id, c.merchant_id, c.id DESC LIMIT 50';
        $result = $this->db->sql_query($sql);
        return $this->db->sql_fetchrowset($result);
    }

    public function codesByStartDate(string $date) {
        if ( !$time = strtotime($date) ) throw new Exception('Invalid date string:'.$date);
        $start = date('Y-m-d',$time);
        $end = $start.' 23:59:59';
        $sql = 'select o.url_tag, s.url_tag as site_url_tag, s.title as sitename, o.title, LENGTH(o.title) as title_length'
        .' FROM '.self::DB_TABLE.' c, '.Offer::DB_TABLE.' o, '.Site::DB_TABLE.' s'
        .' WHERE c.status = "processed" AND c.start_date BETWEEN "'.$start.'" AND "'.$end.'"'
        .' AND c.url_tag_id = o.url_tag_id AND o.status = "approved"'
        .' AND o.site_id = s.site_id'
        .' ORDER BY s.qtr_revenue DESC, s.title, title_length';
        $result = $this->db->sql_query($sql);
        return $this->db->sql_fetchrowset($result);
    }

    public function expireCodes() {
        $sql = 'select id'
        .' FROM '.self::DB_TABLE
        .' WHERE status != "expired" AND end_date < NOW()';
        $result = $this->db->sql_query($sql);
        $rows = $this->db->sql_fetchrowset($result);
        foreach($rows as $r) {
            $this->read($r['id']);
            $this->update(['status'=>'expired']);
        }
        return count($rows);
    }

    public function activeCount() {
        $sql = 'select count(*) as num'
        .' FROM '.self::DB_TABLE
        .' WHERE status = "processed" AND end_date > NOW()';
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        return $row['num'] ?? 0;
    }

    public function sanitiseTitle() {
        $title = strip_tags($this->get('title'));
        $code = addslashes($this->get('code'));
        $regex = '#(-? *(?>use|using|with|,|.) *(?>the|this)? *(?>voucher|coupon)? *(?>code|'.$code.') *:? *\'?(?>'.$code.')?\'?)#i';
        if ( preg_match($regex,$title,$match) ) {
            $title = str_replace($match[1],'',$this->get('title'));
        }
        if ( $title !== $this->get('title') ) {
            $this->set(['title'=>$title]);
            return true;
        }
        return false;
        /* @todo
        Simply use the code below
        */
    }

    public function titleOk() {
        // @todo Influencer Blocked Affiliate
        if ( stripos($this->get('title'),$this->get('code')) !== false ) return false; // title contains code
        $length = strlen($this->get('title'));
        return ( $length > 5 && $length < 80 ); // acceptable length?
    }

    public function makeOfferDescription() {
        $text = 'Code: '.$this->get('code');
        $text .= "\n".'Valid until: '.date('j M H:i',strtotime($this->get('end_date')));
        if ( strlen($this->get('description')) > 5 && $this->get('description') != $this->get('title') ) $text .= "\n\n".strip_tags($this->get('description'));
        if ( strlen($this->get('terms')) > 10 && $this->get('terms') != $this->get('title') ) $text .= "\n\n".strip_tags($this->get('terms'));
        $length = strlen($text);
        if ( $length > 1000 ) return false;
        return $text;
    }

    protected function headerLinks() {
        $links = parent::headerLinks();
        if ( $this->get('status') == 'error' ) {
            $links[] = ['label'=>'Reset status','url'=>'update.php?class=Code&key='.$this->id().'&data[status]=pending&data[error]='];
        }
        return $links;
    }
}