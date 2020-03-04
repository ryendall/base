<?php
namespace im\model;

class UrlTag extends Base {

    const DB_TABLE = 'url_tag';
    const PRIMARY_KEYS = ['url_tag_id'];
    const TITLE_FIELD = 'url_tag';
    const MINIMUM_LENGTH = 3;
    const TYPE_SITE = 1;
    const TYPE_CATEGORY = 2;
    const TYPE_FAQ = 3;
    const TYPE_OFFER = 4;
    const TYPE_NATIONAL = 5;
    const TYPE_LOCAL = 6;
    const DB_LISTS = [
        1 => ['active'=>'Active', 'inactive'=>'Inactive', 'hidden'=>'Hidden'],
        2 => [
            self::TYPE_SITE     =>'Site',
            self::TYPE_CATEGORY =>'Category',
            self::TYPE_FAQ      =>'FAQ',
            self::TYPE_OFFER    =>'Offer',
            self::TYPE_NATIONAL =>'National',
            self::TYPE_LOCAL    =>'Local',
        ],
    ];

    const DB_MODEL = [
        'url_tag_id'         => ["type"=>"key"],
        'url_tag'            => ["type"=>"txt", "required"=>true],
        'type_id'            => ["type"=>"num", "required"=>true, "list"=>2],
        'site_id'            => ["type"=>"num"],
        'category_id'        => ["type"=>"num"],
        'faq_id'             => ["type"=>"num"],
        'offer_id'           => ["type"=>"num"],
        'product_id'         => ["type"=>"num"],
        'keywords'           => ["type"=>"txt"],
        'title'              => ["type"=>"txt"],
        'link_status'        => ["type"=>"txt", "default"=>"active", "list"=>1],
        'parent_url_tag_id'  => ["type"=>"num"],
        'current_url_tag_id' => ["type"=>"num"],
        'subtitle'           => ["type"=>"txt"],
        'clicks'             => ["type"=>"num"],
    ];

    /**
     * Return false if record with supplied tag already exists
     * @param string $proposedTag
     * @return bool $tagIsUnique
     */
    public function isUnique(string $proposedTag) {
        if ( empty($proposedTag) ) throw new \Exception('Empty tag supplied');
        if ( $this->findOne(['url_tag'=>$proposedTag]) ) {
            return false;
        }
        return true;
    }

    /**
     * Create new url_tag based on name and update site record accordingly
     * @var Site $site
     * @return bool
     */
    public function generateSiteTag(Site $site) {
        if ( !$site->id() ) throw new \Exception('No site record loaded');
        $tag = $this->makeTagFromText($site->name());
        if ( $tag == $site->get('url_tag') ) return; // site already has this tag
        $tag = $this->makeUnique($tag);
        $link_status = ( $site->isLive() ) ? 'active' : 'inactive';
        $this->create([
            'url_tag'            => $tag,
            'type_id'            => UrlTag::TYPE_SITE,
            'site_id'            => $site->id(),
            'category_id'        => $site->get('category_id'),
            'title'              => $site->name(),
            'link_status'        => $link_status,
        ],true);
        $site->update(['url_tag_id'=>$this->id(),'url_tag'=>$tag]);
        return true;
    }

    protected function makeTagFromText(string $text) {
        $tag=trim(strip_tags($text));
        $tag = htmlspecialchars_decode($tag);
        $tag = strtolower($tag);
        $tag=str_replace(['.co.uk','.com'],'',$tag);
        $tag=str_replace(['.','_',' '],'-',$tag);
        $tag = str_replace('&','and',$tag);
        $tag = preg_replace('/[^a-z0-9\-]/','',$tag); // remove invalid characters
        if ( strlen($tag) < self::MINIMUM_LENGTH ) throw new \Exception('Tag text too short');
        return $tag;
    }

    protected function makeUnique(string $tag) {
        if ( !$this->isUnique($tag) ) {
            for($i=1;$i<100;$i++) {
                $tagVariation = $tag.'-'.$i;
                if ($this->isUnique($tagVariation)) {
                    $tag = $tagVariation;
                    break;
                }
            }
        }
        return $tag;
    }
}