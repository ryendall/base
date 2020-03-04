<?php
namespace im\model;
use im\helpers\Strings;

class ImapFolder extends Base {

    const DB_TABLE = 'imap_folder';
    const PRIMARY_KEYS = ['imap_folder_id'];
    const TITLE_FIELD = 'folder';
    const DB_LISTS = [
        1 => ['active'=>'Active', 'inactive'=>'Inactive', 'monitor'=>'Monitor'],
        2 => ['partners'=>'Partners', 'richard.yendall'=>'Richard.yendall', 'sue.yendall'=>'Sue.yendall', 'michelle.huntley'=>'Michelle.huntley'],
    ];

    const DB_MODEL = [
        'imap_folder_id' => ["type"=>"key"],
        'folder'         => ["type"=>"txt", "required"=>true],
        'status'         => ["type"=>"txt", "default"=>"active", "list"=>1],
        'list_order'     => ["type"=>"num", "default"=>10000],
        'short_name'     => ["type"=>"txt", "required"=>true],
        'group_id'       => ["type"=>"num", "required"=>true],
        'imap_account'   => ["type"=>"txt", "default"=>"partners", "list"=>2],
        'user_id'        => ["type"=>"num", "required"=>true],
    ];

    const IMAP_HOST = 'secure.emailsrvr.com';
    const IMAP_PORT = '143';
    const MID_REGEX = '#[A-Z]*\t*([0-9]+)\)#i';
    const DEFAULT_ACCOUNT = 'partners';
    const INBOX_FOLDER_ID = 4;
    const FILTER_STOPWORDS = ['re:','important','urgent','exposure','tenancy','action required',' placement'];
    // @todo 'opportunities' folder for 'exposure','tenancy',' placement','opportunit'

    protected $user;
    protected $password;
    protected $mbox;
    protected $headers;
    protected $messages = [];
    protected $filters = [];

    protected function midFromHeader($header) {
        preg_match(static::MID_REGEX, $header, $match);
        $mid = intval($match[1] ?? null);
        if ( empty($mid) ) {
            trigger_error('Mid not found in '.json_encode($header));
            return 0;
        } else {
            return $mid;
        }
    }

    public function openMailbox() {
        $this->reset();
        $mboxString = '{'.static::IMAP_HOST.':'.static::IMAP_PORT.'/imap/novalidate-cert/tls}'.$this->encodedFolderName();
        if ( !$this->mbox = imap_open($mboxString,$this->user,$this->password) ) {
            throw new \Exception('Failed to open mailbox '.$mboxString);
        }
        if ( !$this->headers = imap_headers($this->mbox) ) {
            throw new \Exception('Failed to fetch headers for '.$this->get('folder'));
        }
        foreach($this->getHeaders() as $header) {
            if ( !$mid = $this->midFromHeader($header) ) continue;
            $this->addMessage($mid);
        }
        return $this;
    }

    public function closeMailbox() {
        if ( imap_close($this->mbox, CL_EXPUNGE) ) {
            $this->mbox = null;
        }
    }

    public function mailboxIsOpen() {
        return is_resource($this->mbox);
    }

    protected function reset() {
        if ( !$this->isLoaded() ) throw new \Exception('No folder loaded');
        $this->setCredentials();
        $this->messages=[];
        return $this;
    }

    protected function addMessage(int $mid) {
        $h = imap_header($this->getMailbox(),$mid);
        $tmp = imap_mime_header_decode($h->subject);
        $subject = Strings::utf8Encode($tmp[0]->text);
        $tmp=imap_mime_header_decode($h->from[0]->personal ?? null); // from, reply_to, sender
        $sender_name = Strings::utf8Encode($tmp[0]->text ?? null);
        $message = [
            'mid'           => $mid,
            'message_id'    => str_replace('<','',str_replace('>','',trim($h->message_id))),
            'subject'       => $subject,
            'sender_name'   => $sender_name,
            'username'      => $h->from[0]->mailbox,
            'host'          => $h->from[0]->host,
            'email_time'    => date('Y-m-d H:i:s',strtotime($h->date)),
        ];
        $this->messages[]=$message;
    }

    public function getMailbox() {
        return $this->mbox;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function getMessages() {
        return $this->messages;
    }

    /**
     * Find current folder index number (mid) for given message_id
     * @var string $messageId
     * @return int $mid
     */
    public function findMid($messageId) {
        $key = array_search($messageId,array_column($this->messages, 'message_id'));
        return $this->messages[$key]['mid'] ?? false;
    }

    protected function setCredentials() {
        $account = $this->get('imap_account') ?? static::DEFAULT_ACCOUNT;
        $var = 'IMAP_PASSWORD_'.strtoupper($account);
        $pwd = getenv($var);
        if ( empty($pwd) ) trigger_error('No envvar for '.$var.': '.$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);
        $this->user = $account.'@imutual.co.uk';
        $this->password = $pwd;
        return $this;
    }

    public function encodedFolderName() {
        $this->mustBeLoaded();
        return imap_utf7_encode($this->get('folder'));
    }

    /**
     * For all unfiltered messages in folder:
     * - Apply list of active filters
     * - See if any filters match subject text
     * - Move to new folder as specified by filter
     */
    public function runFilters(int $imapId = null) {
        $imap = new Imap($this->container);
        $moveCount=0;
        $search = ( $imapId ) ? ['imap_id'=>$imapId] : ['imap_folder_id'=>$this->id(),'last_filtered'=>null];
        $msgs = $imap->find($search);
        if ( count($msgs) > 0 ) {
            $this->setFilters();
            $genericFilters = $this->getFilters();
            foreach($msgs as $msg) {
                $imap->read($msg['imap_id']);
                $subject_lower = strtolower($imap->get('subject'));
                $this->traceLog('Filter imap_id '.$imap->id().' on subject: '.$subject_lower);

                foreach(static::FILTER_STOPWORDS as $word) {
                    if ( strpos($subject_lower,$word) !== false ) {
                        $this->traceLog('Subject contains "'.$word.'". Do not filter');
                        break;
                    }
                }

                $filter=$newFolder=null;
                if ( $aff_id = $imap->get('aff_id') ) {
                    if ( $networkFilters = $this->getFilters($aff_id) ) {
                        $this->traceLog('Apply network-specific filters');
                        $filter = $this->parseFilters($subject_lower,$networkFilters);
                    }
                }
                if ( !$filter && $genericFilters ) {
                    $this->traceLog('Apply generic filters');
                    $filter = $this->parseFilters($subject_lower,$genericFilters);
                }
                if ( !$filter ) {
                    $this->traceLog('Try hard-coded regexs');
                    if ( strpos($subject_lower,'!') !== false ) {
                        $this->traceLog('Has exclamation mark. Assume promotion',true);
                        $newFolder = 1;
                    } elseif ( strpos($subject_lower,'£') !== false ) {
                        // Has pound sign
                        if (preg_match('/£[\d\.]+ off/',$subject_lower)) {
                            $this->traceLog('Contains "£xx off". Assume promotion',true);
                            $newFolder = 1;
                        } elseif ( preg_match('/(\w+) £/',$subject_lower,$match) ) {
                            $pound_prefixes = ['just','only','under','from','for','save'];
                            if ( in_array($match[1],$pound_prefixes) ) {
                                $this->traceLog('Contains "'.$match[1].' £". Assume promotion',true);
                                $newFolder = 1;
                            }
                        }
                    }
                } else {
                    $newFolder = $filter->get('to_folder_id');
                }

                if ( $newFolder ) {
                    // We matched a filter to the message
                    if ( $newFolder != $imap->get('imap_folder_id') ) {
                        // Filter triggers move to another folder
                        $this->traceLog('Move to folder_id '.$newFolder,true);
                        if ( !$this->mailboxIsOpen() ) {
                            $this->openMailbox();
                        }
                        $imap->setFolder($this);
                        $imap->move($newFolder, $filter);
                        $moveCount++;
                    }
                } else {
                    $imap->update(['last_filtered'=>$this->now]);
                }
            }
        }
        return $moveCount;
    }

    /**
     * Apply a list of filters to the text string
     * Return first matching filter
     * @param string $text
     * @param array $filters
     * @return im\model\ImapFilter
     *
     */
    protected function parseFilters($text, array $filters) {
        $traceLog = 'Match "<i>'.$text.'</i>" to:';
        foreach($filters as $filter) {
            $traceLog .= ' "'.$filter['text_match'].'"';
            if ( strpos($text,$filter['text_match']) !== false ) {
                $this->traceLog($traceLog.' => <b>FOUND</b>');
                return new ImapFilter($this->container, $filter['filter_id']);
            }
        }
        $this->traceLog($traceLog);
        return null;
    }

    protected function setFilters() {
        $filters=[];
        $imapFilter = new ImapFilter($this->container);
        $data = $imapFilter->find(
            ['status'=>'active', 'from_folder_id'=>$this->id()],
            ['order'=>['priority'=>1]]
        );
        foreach($data as $f) {
            $aff_id = intval($f['aff_id']);
            if ( !isset($filters[$aff_id]) ) {
                $filters[$aff_id] = [$f];
            } else {
                $filters[$aff_id][] = $f;
            }
        }
        $this->filters = $filters;
    }

    protected function getFilters(int $aff_id=0) {
        return $this->filters[intval($aff_id)] ?? false;
    }

    public function folderList(string $account = self::DEFAULT_ACCOUNT) {
        $folders=[];
        $rows = $this->find(['imap_account'=>$account,'status'=>'active']);
        foreach($rows as $row) {
            $folders[$row['imap_folder_id']]=$row['folder'];
        }
        return $folders;
    }

}