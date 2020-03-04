<?php
namespace im\model;

class Category extends Base {

    const DB_TABLE = 'category';
    const PRIMARY_KEYS = ['category_id'];
    const DB_LISTS = [
        1 => ['active'=>'Active', 'inactive'=>'Inactive'],
        2 => ['true'=>'Yes', 'false'=>'No'],
        3 => ['yes'=>'Yes', 'no'=>'No', 'unknown'=>'Unknown', 'travel'=>'Travel'],
    ];
    const DB_MODEL = [
        'category_id'		    => ['type'=>'key'],
        'category'	            => ['type'=>'txt'],
        'status'	            => ['type'=>'txt', 'default'=>'active', 'list' => 1],
        'list_order'            => ['type'=>'num', 'default'=>8000],
        'links'		            => ['type'=>'num'],
        'age_id'	            => ['type'=>'num'],
        'forum_id'	            => ['type'=>'num'],
        'keywords'	            => ['type'=>'txt'],
        'url_tag_id'		    => ['type'=>'num'],
        'url_tag'		        => ['type'=>'txt'],
        'parent_id'		        => ['type'=>'num'],
        'response_id'		    => ['type'=>'num'],
        'can_claim'		        => ['type'=>'txt', 'list' => 2],
        'max_trans_per_user'	=> ['type'=>'num'],
        'max_per_day'		    => ['type'=>'num'],
        'max_per_period_days'	=> ['type'=>'num'],
        'default_reward_label'	=> ['type'=>'txt'],
        'default_national'	    => ['type'=>'txt', 'list' => 3],
        'guarantee'		        => ['type'=>'txt', 'list' => 2],
    ];

    protected $default_reward_label;
    protected $parent;

    public function getParent() {
        return $this->parent;
    }

    protected function load() {
        parent::load();
        $this->parent = new Category($this->container, $this->get('parent_id'));

        // Apply any settings inherited from parent/grandparent
        // Set default reward label
        $this->default_reward_label = 'Purchases';
        if ( $label = $this->get('default_reward_label') ) {
            $this->default_reward_label = $label;
        } elseif ( $this->hasParent() ) {
            if ( $label = $this->parent->get('default_reward_label') ) {
                $this->default_reward_label = $label;
            } elseif ( $this->parent->hasParent() ) {
                if ( $label = $this->parent->parent->get('default_reward_label') ) {
                    $this->default_reward_label = $label;
                }
            }
        }
    }

    public function hasParent() {
        return ($this->parent instanceof Category && $this->parent->isLoaded());
    }

    public function getDefaultRewardLabel() {
        return $this->default_reward_label;
    }

}