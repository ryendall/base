<?php
namespace im\model;

class Group extends Base {

    const DB_TABLE = DB_FORUM_NAME.'.phpbb_groups';
    const PRIMARY_KEYS = ['group_id'];

    const SHAREHOLDERS = 14;
    const SUSPENDED = 16;
    /*
|       10 | Advocates          |
|       11 | Insider dealers    |
|       13 | Quarantined users  |
|       14 | Shareholders+      |
|       16 | Suspended          |
|       17 | Portal owners      |
|       18 | Testers            |
|       19 | Revshare referrers |
|       20 | CIC members        |
|       21 | CIC officers       |
*/

    const DB_MODEL = [
        'group_id'      => ["type"=>"key"],
        'group_name'    => ["type"=>"txt"],
    ];

}