<?php
namespace im\model;

class Offer extends Base {

    const DB_TABLE = 'offer';
    const PRIMARY_KEYS = ['offer_id'];
    const TITLE_FIELD = 'title';
    const DB_LISTS = [
        1 => ['pending'=>'Pending', 'approved'=>'Approved', 'rejected'=>'Rejected', 'expired'=>'Expired', 'cancelled'=>'Cancelled'],
    ];

    const DB_MODEL = [
        'offer_id'       => ["type"=>"key"],
        'title'          => ["type"=>"txt", "required"=>true],
        'description'    => ["type"=>"txt", "required"=>true],
        'url_tag'        => ["type"=>"txt", "required"=>true],
        'url_tag_id'     => ["type"=>"num", "required"=>true],
        'url'            => ["type"=>"txt"],
        'url2'           => ["type"=>"txt"],
        'site_id'        => ["type"=>"num", "required"=>true],
        'category_id'    => ["type"=>"num", "required"=>true],
        'post_id'        => ["type"=>"num"],
        'user_id'        => ["type"=>"num"],
        'submitted'      => ["type"=>"dat", "default"=>'{NOW}'],
        'status'         => ["type"=>"txt", "default"=>"pending", "list"=>1],
        'expiry'         => ["type"=>"dat", "required"=>true],
        'imap_id'        => ["type"=>"num"],
        'image'          => ["type"=>"txt"],
        'image_width'    => ["type"=>"num"],
        'image_height'   => ["type"=>"num"],
        'product_id'     => ["type"=>"num"],
        'price'          => ["type"=>"num", "scale"=>2],
        //'item_id'        => ["type"=>"num"],
        //'link_id'        => ["type"=>"txt", "required"=>true],
        //'subcategory_id' => ["type"=>"num", "required"=>true],
        //'clicks'         => ["type"=>"num"],
        //'clickers'       => ["type"=>"num"],
        //'transactions'   => ["type"=>"num"],
        //'shares'         => ["type"=>"num"],
        //'latest_click'   => ["type"=>"dat"],
        //'ip'             => ["type"=>"num"],
    ];

    public static function offersSummary(array $rows) {
        $text = $line = '';
        $current_site = null;
        $other_count=0;
        foreach($rows as $r) {
            if ( $r['site_url_tag'] != $current_site ) {
                // Start of a new site
                if ( !empty($line) ) {
                    // Add previous site to post text
                    if ( $other_count > 0 ) {
                        $noun = ($other_count == 1) ? 'offer' : 'offers';
                        $line .= ' + '.$other_count.' other '.$noun;
                    }
                    $text .= "\n".$line;
                }
                $line = '<a href="/'.$r['url_tag'].'">'.htmlspecialchars($r['title']).'</a> @ <b><a href="/'.$r['site_url_tag'].'">'.htmlspecialchars($r['sitename']).'</a></b>';

                // Reset vars
                $current_site = $r['site_url_tag'];
                $other_count=0;
            } else {
                // Additional offer for same site
                $other_count++;
            }
        }
        $text .= "\n".$line; // add final line
        return $text;
    }
}