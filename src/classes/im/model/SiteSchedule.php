<?php
namespace im\model;
use im\helpers\Dates;

class SiteSchedule extends Base {

    const DB_TABLE = 'site_schedule';
    const PRIMARY_KEYS = ['site_schedule_id'];
    const DB_LISTS = [
        1 => ['true'=>'Yes', 'false'=>'No'],
    ];

    const DB_MODEL = [
        'site_schedule_id'      => ["type"=>"key"],
        'site_id'               => ["type"=>"num", "required"=>true],
        'start_date'            => ["type"=>"dat", "default"=>'{NOW}'],
        'end_date'              => ["type"=>"dat", "default"=>Constants::FOREVER_DATE],
        'program_id'            => ["type"=>"num"],
        'astatus'               => ["type"=>"num", "required"=>true],
        'guarantee'             => ["type"=>"txt", "default"=>"false", "list"=>1],
        'can_claim'             => ["type"=>"txt", "required"=>true, "list"=>1],
        //'aff_id'                => ["type"=>"num"],
        //'merchant_ref'          => ["type"=>"txt"],
        //'ecashier_id'           => ["type"=>"num"],
    ];
    // 'tracking','requires_verification','awin_rule_id'

    public function create(array $data=null, bool $exceptionOnError=false) {
        if ( $id = parent::create($data,$exceptionOnError) ) {
            // Ensure new schedule doesn't overlap any existing ones for this site
            $query = [
                'site_id'               => $this->get('site_id'),
                self::PRIMARY_KEYS[0]   => ['ne'=>$id],
                'start_date'            => ['lt'=>$this->get('end_date')],
                'end_date'              => ['gt'=>$this->get('start_date')],
            ];
            $rows = $this->find($query);
            if ( count($rows) > 0 ) {
                $class = get_class();
                $old = new $class($this->container);
                foreach($rows as $row) {
                    $old->loadDbData($row);
                    // Old and new schedule overlap in one of four possible ways:
                    $newOneStartsLater = Dates::compareDates($this->get('start_date'),$row['start_date'])+1;
                    $newOneEndsLater = Dates::compareDates($this->get('end_date'),$row['end_date'])+1;
                    if ($newOneStartsLater) {
                        if ($newOneEndsLater) {
                            // New straddles end_date of old, bring old end_date forward
                            $old->update(['end_date'=>$this->get('start_date')]);
                        } else {
                            // New entirely inside old, split old into two (before and after new)
                            $old->update(['end_date'=>$this->get('start_date')]);
                            $row['start_date']=$this->get('end_date');
                            unset($row[self::PRIMARY_KEYS[0]]);
                            $old->create($row);
                        }
                    } else { // new one starts before
                        if ($newOneEndsLater) {
                            // New entirely spans old, delete old
                            $old->delete();
                        } else {
                            // New straddles start_date of old, put old start_date back
                            $old->update(['start_date'=>$this->get('end_date')]);
                        }
                    }
                }
            }
        }
        return $id;
    }

    /**
     * Find and load the current entry for the specified site
     */
    public function readCurrent(int $siteId) {
        $sql = 'SELECT * FROM '.self::DB_TABLE.' WHERE site_id = '.$siteId.' AND start_date < NOW() ORDER BY end_date DESC LIMIT 1';
        $result=$this->db->sql_query($sql);
        if ( !$r=$this->db->sql_fetchrow($result) ) {
            throw new \Exception('Schedule not found: '.$sql);
        }
        $this->read($r['site_schedule_id']);
        return $this->get();
    }

}