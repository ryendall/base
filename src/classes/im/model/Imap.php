<?php
namespace im\model;
use im\helpers\Strings;

class Imap extends Base {

    const DB_TABLE = 'imap';
    const PRIMARY_KEYS = ['imap_id'];
    const DB_LISTS = [
        1 => ['open'=>'Open', 'processed'=>'Processed', 'ignore'=>'Ignore', 'archived'=>'Archived', 'reassign'=>'Reassign'],
        2 => ['normal'=>'Normal', 'zipped'=>'Zipped', 'deleted'=>'Deleted', 'other'=>'Other'],
    ];

    const DB_MODEL = [
        'imap_id'          => ["type"=>"key"],
        'message_id'       => ["type"=>"txt", "required"=>true],
        'imap_folder_id'   => ["type"=>"num", "required"=>true],
        'email_time'       => ["type"=>"dat", "required"=>true],
        'sender_name'      => ["type"=>"txt"],
        'email_address_id' => ["type"=>"num", "required"=>true, 'class'=>'EmailAddress'],
        'subject'          => ["type"=>"txt"],
        'attachments'      => ["type"=>"num"],
        'status'           => ["type"=>"txt", "default"=>"open", "list"=>1],
        'site_id'          => ["type"=>"num", 'class'=>'Site'],
        'aff_id'           => ["type"=>"num", 'class'=>'Network'],
        'last_seen'        => ["type"=>"dat", 'default'=>'{NOW}'],
//        'processed_by'     => ["type"=>"num"],
//        'user_id'          => ["type"=>"num"],
        'filter_id'        => ["type"=>"num", 'class'=>'ImapFilter'],
        'last_filtered'    => ["type"=>"dat"],
        'program_id'       => ["type"=>"num", 'class'=>'Program'],
        'message_size'     => ["type"=>"num"],
        'text_status'      => ["type"=>"txt", "default"=>"normal", "list"=>2],
    ];

    const MREF_IN_BODY_REGEX = [
        0 => [
            'Advertiser ID: ([0-9]+)',
        ],
        2 => [
            'program ID ([0-9]+)',
        ],
        6 => [
            'merchant_id=([0-9]+)',
            'mid=([0-9]+)',
            'profile/([0-9]+)/',
            '/awin/merchant/([0-9]+)',
            'aid: ([0-9]+)',
        ],
        9=>[
            'merchantID=([0-9]+)',
        ],
        32=>[
            ' \(([0-9]{6,8})\)',
        ],
        43=>[
            'paidonresults.net/c/25001/1/([0-9]+)',
            'merchant-profile/([0-9]+)'
        ],
        50=>[
            'programID/([0-9]+)',
        ],
        113=>[
            'mid=([0-9]+)',
        ],
        217=>[
            ' \(([0-9]{3,6})\)',
            '14330\/[0-9]+\/([0-9]+)'
        ],
        232=>[
            'camref:([\w]{3,9})',
        ]
    ];

    const PROGRAM_IN_SUBJECT_REGEX = [
        0 => [' at ([\w\.\-\s]+)'],
        208 => [
            'TradeTracker UK \- Campaign [\w\.\-\s]+ \- ([\w\.\-\s]+)',
            ' for imutual.co.uk with ([\w\.\-\s]+)'
        ],
        217 => [
            '([\w\.\-\s]+) Contract',
        ],
    ];

    const MREF_IN_SUBJECT_REGEX = [
        2 => [
            'id ([\d]+),',
        ],
        6 => [
            ' \(([\d]+)\)',
            ' \- ?([\d]+)'
        ],
        32 => [
            '([\d]{6,8})\)',
        ],
    ];

    // TD => id 246809, Affiliate News
    // 50=>array('You have received this message because you are a member of the (.*) affiliate program'),

    protected $folder;
    protected $mid;
    protected $charSet;
    protected $html;
    protected $plain;
    protected $attachments = [];
    protected $fullMessage = null;

    protected function listFields() {
        return ['email_time','subject','email_address_id'];
    }

    public function listData(array $options=[]) {
        if ( empty($options['where']) ) {
            $options['where'] = [
                'status' => 'open',
            ];
        }
        return parent::listData($options);
    }

    public function setFolder(ImapFolder $folder) {
        $this->folder = $folder;
    }

    /**
     * Create new database record using imap data
     */
    public function createFromImap(array $message) {
        $message['imap_folder_id'] = $this->getFolder()->get('imap_folder_id');
        $this->setMid($message['mid']);
        $data = $this->addSenderIds($message);
        $imap_id = $this->create($data,true);
        $this->fetchMessageFromImap();
    }

    // grab message details
    public function fetchHeaderAndBody() {
        $this->setMid();
        return imap_fetchheader($this->getFolder()->getMailbox(), $this->getMid())
         . imap_body($this->getFolder()->getMailbox(), $this->getMid());
    }

    /**
     * Move message between imap folders and update database accordingly
     * @param int $newFolderId
     * @param ImapFilter $filter optional
     * Second param indicates if move results from filter match
     */
    public function move(int $newFolderId, ImapFilter $filter = null) {
        $this->mustBeLoaded();
        $this->setMid();
        $this->setNewFolder($newFolderId);
        $this->markAsUnread();
        if ( !imap_mail_move(
            $this->getFolder()->getMailbox(),
            $this->getMid(),
            $this->getNewFolder()->encodedFolderName()
        ) ) {
            throw new \Exception('imap_mail_move failed');
        }
        $data = ['imap_folder_id'=>$newFolderId];
        if ( $filter ) {
            // Message being moved as a result of filter match
            if ( $filter->get('last_email_time') < $this->get('email_time') ) {
                $filter->update(['last_email_time'=>$this->get('email_time')]);
            }
            $data += ['filter_id'=>$filter->id(), 'last_filtered'=>$this->now];
        }
        return $this->update($data);
    }

    public function changeStatus($status) {
        if ( $this->update(['status'=>$status]) ) {
            if ( in_array($status,['processed','ignore','archived']) ) {
                $this->imapDelete();
            }
            return true;
        }
        return false;
    }

    public function markAsUnread() {
        return imap_clearflag_full($this->getFolder()->getMailbox(),$this->getMid(),'\\Seen');
    }

    public function senderEmail() {
        return $this->getObject('email_address_id')->emailAddress();
    }

    public function imapDelete() {
        $this->setMid();
        return imap_delete($this->getFolder()->getMailbox(),$this->getMid());
    }

    protected function setNewFolder(int $newFolderId) {
        $this->newFolder = new ImapFolder($this->container,$newFolderId);
    }

    protected function getNewFolder() {
        return $this->newFolder;
    }

    /**
     * Use imap sender info to find foreign keys
     * Return original array with added fields
     */
    public function addSenderIds(array $message) {
        if ( empty($message['host']) ) return $message;
        $this->traceLog('Determine aff_id/site_id using '.$message['username'].'@'.$message['host']);
        $domain = new Domain($this->container);
        $email = new EmailAddress($this->container);
        $id = $domain->findOrCreate(['domain'=>$message['host']]);
        $id = $email->findOrCreate(['domain_id'=>$domain->get('domain_id'), 'username'=>$message['username']]);
        if ( $domain->get('site_id') ) $this->traceLog('Matched to site_id '.$domain->get('site_id').' by '.$domain->name(),true);
        elseif ( $email->get('site_id') ) $this->traceLog('Matched to site_id '.$domain->get('site_id').' by email address', true);

        $message['email_address_id'] = $email->get('id');
        $message['aff_id'] = $email->get('aff_id') ?? $domain->get('aff_id') ?? null;
        if ( $message['aff_id'] ) $this->traceLog('sender indicates aff_id='.$message['aff_id'], true);
        $message['site_id'] = $email->get('site_id') ?? $domain->get('site_id') ?? $this->matchSiteBySenderName($message['sender_name']);
        if ( $message['site_id'] ) $this->traceLog('sender indicates site_id='.$message['site_id'], true);
        return $message;
    }

    protected function matchSiteBySenderName(string $sender_name=null) {
        $name = $sender_name ?? $this->get('sender_name');
        if ( $name ) {
            $this->traceLog('Try to match by sender name '.$name);
            $obj = new SenderName($this->container);
            if ( $obj->findOne(['sender_name'=>$name]) ) {
                $this->traceLog('Matched sender name '.$name.' to site_id '.$obj->get('site_id'), true);
                return $obj->get('site_id');
            }
        }
        return null;
    }


    protected function getFolder() {
        return $this->folder;
    }

    public function getMailbox() {
        return $this->getFolder()->getMailbox();
    }

    /**
     * Get message body from imap account
     * Populate html,plain,charset,attachments
     * @return self
     */
    public function fetchMessageFromImap() {
        $this->resetMessageBody()
            ->fetchStructure()
            ->parseStructure()
            ->formatMessage()
            ->writeMessageToFile();
        $this->set(['message_size' => $this->messageSize,'attachments'  => count($this->attachments)]);
        if ( empty($this->get('site_id')) ) {
            $this->guessInfoFromMessage();
        }
        $this->update();
        return $this;
    }

    /**
     * Determine site_id by searching subject and body for references
     * Return true as soon as a site is identified - must run update()
     * Else return false
     */
    public function guessInfoFromMessage() {
        if ( empty($this->get('site_id')) ) {
            // Check subject first
            if ( $this->get('aff_id') ) {
                if ( $this->guessSiteByNetworkRefInSubject() ) return true;
            }
            if ( $this->guessSiteByProgramInSubject() ) return true;
            // If nothing found in subject, now check in message body
            $this->getMessage();
            if ( $this->guessSiteByNetworkRefInBody() ) return true;
            if ( $this->guessSiteByDomainInBody() ) return true; // @todo do earlier to id network?
            if ( in_array($this->get('aff_id'),[32,113]) ) {
                if ( $this->guessSiteByProgramInSenderName() ) return true;
            }
        }
        return false;
    }

    protected function guessSiteByNetworkRefInBody() {
        $regexs = static::MREF_IN_BODY_REGEX[$this->get('aff_id') ?? 0] ?? [];
        $this->traceLog('Check '.count($regexs).' pattern(s) using '.__FUNCTION__);
        foreach($regexs as $regex) {
            if ( $this->matchProgramUsingRegex($regex,$this->fullMessage,'network_ref') ) return true;
        }
        return false;
    }

    protected function guessSiteByProgramInSubject() {
        $regexs = static::PROGRAM_IN_SUBJECT_REGEX[$this->get('aff_id') ?? 0] ?? [];
        $this->traceLog('Check '.count($regexs).' pattern(s) using '.__FUNCTION__);
        foreach($regexs as $regex) {
            if ( $this->matchProgramUsingRegex($regex,$this->get('subject'),'program') ) return true;
        }
        return false;
    }

    protected function guessSiteByNetworkRefInSubject() {
        $regexs = static::MREF_IN_SUBJECT_REGEX[$this->get('aff_id')] ?? [];
        $this->traceLog('Check '.count($regexs).' pattern(s) using '.__FUNCTION__);
        foreach($regexs as $regex) {
            if ( $this->matchProgramUsingRegex($regex,$this->get('subject'),'network_ref') ) return true;
        }
        return false;
    }

    /**
     * Find site(s) from domain name(s) found in message body
     * If we find exactly one site, return true
     * Else return false
     */
    protected function guessSiteByDomainInBody() {
        $site_id = null;
        $body = \Helpers::textAfterPhrase($this->fullMessage,'</head>'); // exclude head section
        $body = preg_replace('/src="[^"]+"/','',$body); // remove image domains
        $hosts = array_unique(\Helpers::getHostsFromText($body));
        if ( !empty($hosts) ) {
            $this->traceLog('Unique domains in message body: '.implode(', ',$hosts));
            if (($key = array_search('imutual.co.uk', $hosts)) !== false) {
                unset($hosts[$key]);
            }
            $domain = new Domain($this->container);
            foreach($hosts as $host) {
                $this->traceLog('Check database for '.$host);
                if ( $domain->findOne(['domain'=>$host]) ) {
                    if ( $domain->get('aff_id') && !$this->get('aff_id') ) {
                        $this->traceLog($host.' matches affID '.$domain->get('aff_id'), true);
                        $this->set(['aff_id'=>$domain->get('aff_id')]);
                    }
                    if ( $domain->get('site_id') ) {
                        $this->traceLog($host.' matches siteID '.$domain->get('site_id'), true);
                        if ( $site_id && $domain->get('site_id') != $site_id ) {
                            $this->traceLog('Already matched to '.$site_id, true);
                            return false; // multiple site matches
                        }
                        else $site_id = $domain->get('site_id');
                    }
                }
            }
        }
        if ( $site_id ) {
            $this->set(['site_id'=>$site_id]);
            return true;
        }
        return false;
    }

    protected function matchProgramUsingRegex($regex,$text,$field) {
        $regex = '#'.$regex.'#i';
        $this->traceLog('Apply '.$regex.' to '.strlen($text).' chr string looking for '.$field);
        if ( preg_match($regex,$text,$matches) > 0 ) {
            // We may have found a network reference, search for matching program
            $value = trim($matches[1]);
            $this->traceLog('Found '.$value.' in '.$matches[0],true);
            $program = new Program($this->container);
            if ( $program->findOne(array_filter(['aff_id'=>$this->get('aff_id'), $field=>$value])) ) {
                $this->set([
                    'program_id'=>$program->get('program_id'),
                    'site_id'=>$program->get('site_id'),
                ]);
                $this->traceLog('Matches '.$program->name(), true);
                return true;
            } else {
                $this->traceLog('No prog where '.$field.'='.$value);
            }
        }
        return false;
    }

    protected function guessSiteByProgramInSenderName() {
        $name = $this->get('sender_name');
        $this->traceLog('Look for program matching sender name: '.$name);
        $stopwords = ['@','Notification','Rakuten','Linkshare'];
        foreach($stopwords as $word) {
            if ( strpos($name,$word) !== false ) {
                $this->traceLog('Sender name contains "'.$word.'". Abandon search');
                return false;
            }
        }
        // Remove common suffixes
        if ( $pos = strpos($name,"'s Affiliate") ) $name = substr($name,0,$pos);
        if ( $pos = strpos($name," Affiliate") ) $name = substr($name,0,$pos);
        if ( $name != $this->get('sender_name') ) {
            // Name shortened. Remove common prefixes
            if ( substr($name,0,4) == 'The ' ) $name = substr($name,4);
            if ( $pos = strpos($name,' the ') ) $name = substr($name,$pos+5);
            $this->traceLog('Search name shortened to: '.$name);
        }
        if ( empty($name) ) return false;
        $program = new Program($this->container);
        if ( $program->findOne(['aff_id'=>$this->get('aff_id'), 'program'=>$name]) ) {
            $this->set([
                'program_id'=>$program->get('program_id'),
                'site_id'=>$program->get('site_id'),
            ]);
            $this->traceLog('Matches program_id '.$program->id(), true);
            return true;
        }
        return false;
    }

    protected function fetchStructure() {
        $this->setMid();
        $this->structure = imap_fetchstructure($this->getMailbox(),$this->getMid());
        return $this;
    }

    protected function parseStructure() {
        if ( $parts=$this->getParts() ) {
            // multipart: iterate through each part
            foreach ($parts as $partno0=>$p) {
                $this->getpart($p,$partno0+1);
            }
        } else {
            $this->getpart($this->structure,0);  // no part-number, so pass 0
        }
        return $this;
    }

    protected function formatMessage() {
        $message = ( strlen($this->html) > strlen($this->plain) ) ? $this->html : nl2br($this->plain);
        if ( !$this->isUtf8() ) $message = Strings::utf8Encode($message);
        $max_length = 1000000;
        $this->messageSize = strlen($message);
        if ( $this->messageSize > $max_length ) $message = mb_substr($message,0,$max_length,'UTF-8');
        $this->fullMessage = $message;
        return $this;
    }

    protected function writeMessageToFile() {
        file_put_contents($this->messageFilename(),$this->fullMessage);
        return $this;
    }

    protected function readMessageFromFile() {
        $filename = $this->messageFilename();
        if ( substr($filename,-3) == '.gz' ) {
            $fp = gzopen($filename, "r");
            $this->fullMessage = stream_get_contents($fp);
            fclose($fp);
        } else {
            $this->fullMessage = file_get_contents($filename);
        }
        return $this;
    }

    public function getMessage() {
        if ( $this->fullMessage === null ) $this->readMessageFromFile();
        return $this->fullMessage;
    }

    public function messageFilename() {
        $this->mustBeLoaded();
        if ( $this->get('text_status') == 'zipped' ) {
            $filename = IMAP_FOLDER.'archive/'.$this->id().'.txt.gz';
        } else {
            $filename = IMAP_FOLDER.$this->id().'.txt';
        }
        return $filename;
    }

    protected function isUtf8() {
        return ( strtolower($this->charset) != 'utf-8' );
    }

    protected function getParts() {
        return $this->structure->parts ?? null;
    }

    protected function resetMessageBody() {
        $this->structure = $this->html = $this->plain = $this->charset = $this->messageSize = $this->fullMessage = null;
        $this->attachments = [];
        return $this;
    }

    protected function getpart($p,$partno) {
        // $partno = '1', '2', '2.1', '2.1.3', etc if multipart, 0 if not multipart

        // DECODE DATA
        $data = ($partno)?
            imap_fetchbody($this->getMailbox(),$this->getMid(),$partno):  // multipart
            imap_body($this->getMailbox(),$this->getMid());  // not multipart
        // Any part may be encoded, even plain text messages, so check everything.
        if ($p->encoding==4)
            $data = quoted_printable_decode($data);
        elseif ($p->encoding==3)
            $data = base64_decode($data);
        // no need to decode 7-bit, 8-bit, or binary

        // PARAMETERS
        // get all parameters, like charset, filenames of attachments, etc.
        $params = array();
        if ($p->parameters)
            foreach ($p->parameters as $x)
                $params[ strtolower( $x->attribute ) ] = $x->value;
        if ( isset($p->dparameters) && is_array($p->dparameters) )
            foreach ($p->dparameters as $x)
                $params[ strtolower( $x->attribute ) ] = $x->value;

        // ATTACHMENT
        // Any part with a filename is an attachment,
        // so an attached text file (type 0) is not mistaken as the message.
        if ( isset($params['filename']) || isset($params['name']) ) {
            // filename may be given as 'Filename' or 'Name' or both
            $filename = $params['filename'] ?? $params['name'];
            // filename may be encoded, so see imap_mime_header_decode()
            $this->attachments[$filename] = $data;  // this is a problem if two files have same name
        }

        // TEXT
        elseif ($p->type==0 && $data) {
            // Messages may be split in different parts because of inline attachments,
            // so append parts together with blank row.
            if (strtolower($p->subtype)=='plain')
                $this->plain .= trim($data) ."\n\n";
            else
                $this->html .= $data ."<br><br>";
            $this->charset = $params['charset'];  // assume all parts are same charset
        }

        // EMBEDDED MESSAGE
        // Many bounce notifications embed the original message as type 2,
        // but AOL uses type 1 (multipart), which is not handled here.
        // There are no PHP functions to parse embedded messages,
        // so this just appends the raw source to the main message.
        elseif ($p->type==2 && $data) {
            $this->plain .= trim($data) ."\n\n";
        }

        // SUBPART RECURSION
        if ( isset($p->parts) && is_array($p->parts) ) {
            foreach ($p->parts as $partno0=>$p2)
                $this->getpart($p2,$partno.'.'.($partno0+1));  // 1.2, 1.2.1, etc.
        }
    }

    protected function setMid(int $mid = null) {
        if ( $mid === null ) $mid = $this->getFolder()->findMid($this->get('message_id'));
        $this->mid = $mid;
    }

    protected function getMid() {
        return $this->mid;
    }
}
