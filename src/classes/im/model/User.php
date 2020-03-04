<?php
namespace im\model;

class User extends Base {

    const DB_TABLE = DB_FRONTEND_NAME.'.user';
    const PRIMARY_KEYS = ['user_id'];
    const DB_LISTS = [
        1 => ['false'=>'No', 'true'=>'Yes'],
        2 => ['male'=>'Male', 'female'=>'Female'],
    ];

    const DB_MODEL = [
        'user_id'         => ["type"=>"key"],
        'username'        => ["type"=>"txt"],
        'email_address'   => ["type"=>"txt", "required"=>true],
        'password'        => ["type"=>"txt"],
        'reg_date'        => ["type"=>"dat", "default"=>'{NOW}'],
        'bb_user_id'      => ["type"=>"num"],
        'referrer_id'     => ["type"=>"num"],
        'email_verified'  => ["type"=>"txt", "default"=>"false", "list"=>1],
        'autologin_token' => ["type"=>"txt"],
        'password_hash'   => ["type"=>"txt"],
        'firstname'       => ["type"=>"txt"],
        'full_name'       => ["type"=>"txt"],
        'facebook_id'     => ["type"=>"num"],
        'hometown'        => ["type"=>"txt"],
        'gender'          => ["type"=>"txt", "list"=>2],
        'postcode'        => ["type"=>"txt"],
        'address'         => ["type"=>"txt"],
    ];

    protected $bbusers;
    protected $bbusersx;
    protected $forumUser;

    protected function load() {
        parent::load();
        $this->bbusers = new Bbusers($this->container, $this->get('user_id'));
        $this->bbusersx = new Bbusersx($this->container, $this->get('user_id'));
        $this->forumUser = new ForumUser($this->container, $this->get('bb_user_id'));
    }

    public function suspend(array $options) {
        $options['user_id'] =  $this->get('user_id');
        $options['redeem_ban'] = ( $options['redeem_ban'] ) ? 'true' : 'false';
        $options['click_ban'] = ( $options['click_ban'] ) ? 'true' : 'false';
        $options['content_ban'] = ( $options['content_ban'] ) ? 'true' : 'false';
        $suspension = new Suspension($this->container);
        $suspended_id = $suspension->create($options);
        $this->getBbusersx()->update(['suspended_id'=>$suspended_id, 'user_remove'=>3]);
        $this->getBbusers()->update(['user_remove'=>3]);
        $this->cancelPendingPayments();
        if ( $options['content_ban'] === 'true' ) {
            $this->addToGroup(Group::SUSPENDED);
        }
        return true;
        // @todo cancel claims
    }

    public function unsuspend() {
        $suspension = new Suspension($this->container, $this->getBbusersx()->get('suspended_id'));
        $this->getBbusersx()->update(['suspended_id'=>null, 'user_remove'=>0]);
        $this->getBbusers()->update(['user_remove'=>0]);
        $this->removeFromGroup(Group::SUSPENDED);
        if ( $suspension->get('user_id') == $this->get('user_id') ) {
            $suspension->update(['status'=>'lifted', 'last_updated'=>date('c')]);
        }
        return true;
    }

    public function addToGroup(int $group_id) {
        $userGroup = new UserGroup($this->container);
        $userGroup->create(['group_id'=>$group_id, 'user_id'=>$this->get('bb_user_id')]);
        $userGroupLog = new UserGroupLog($this->container);
        $userGroupLog->create(['action'=>'add', 'group_id'=>$group_id, 'user_id'=>$this->get('user_id')]);
        $this->getForumUser()->resetPermissions();
    }

    public function removeFromGroup(int $group_id) {
        $userGroup = new UserGroup($this->container);
        $userGroup->delete(['group_id'=>$group_id, 'user_id'=>$this->get('bb_user_id')]);
        $userGroupLog = new UserGroupLog($this->container);
        $userGroupLog->create(['action'=>'remove', 'group_id'=>$group_id, 'user_id'=>$this->get('user_id')]);
        $this->getForumUser()->resetPermissions();
    }

    public function cancelPendingPayments() {
        $payment = new Payment($this->container);
        $data = ['user_id'=>$this->get('user_id')];
        $statii = [Payment::STATUS['pending'], Payment::STATUS['in_progress']];
        foreach($statii as $status) {
            if ( $payment->findOne($data+['status'=>$status]) ) {
                $payment->update(['status'=>Payment::STATUS['cancelled']]);
                return true;
            }
        }
        return false;
    }

    public function getBbusers() {
        return $this->bbusers;
    }

    public function getBbusersx() {
        return $this->bbusersx;
    }

    public function getForumUser() {
        return $this->forumUser;
    }

    public function getFullname() {

    }

    public function getAddress() {

    }

}