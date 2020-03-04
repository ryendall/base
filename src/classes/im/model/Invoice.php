<?php
namespace im\model;
use im\helpers\Strings;
use mikehaertl\wkhtmlto\Pdf;

class Invoice extends Base {

    const DB_TABLE = 'invoice';
    const PRIMARY_KEYS = ['invoice_id'];
    const DB_LISTS = [
        1 => ['invoice'=>'Invoice', 'purchase_order'=>'Purchase order', 'insertion_order'=>'Insertion order'],
        2 => [10=>'Draft', 15=>'Generated', 20 => 'Issued', 22=>'Acknowledged', 25=>'Queried', 30 => 'Paid', 40=>'Refused', 45=>'Written off', 50=>'Cancelled'],
    ];

    const DB_MODEL = [
        'invoice_id'   => ["type"=>"key"],
        'aff_id'       => ["type"=>"num", "required"=>true, 'class'=>'Network', 'lookup'=>'getInvoiceableNetworks:title'],
        'invoicee'     => ["type"=>"txt", "required"=>true],
        'supply_from'  => ["type"=>"dat", "required"=>true],
        'supply_to'    => ["type"=>"dat", "required"=>true],
        'subtotal'     => ["type"=>"num", "scale"=>2],
        'vat'          => ["type"=>"num", "scale"=>2],
        'total'        => ["type"=>"num", "scale"=>2],
        'invoice_date' => ["type"=>"dat", "default"=>'{NOW}'],
        'status'       => ["type"=>"num", 'default'=>10, "list"=>2],
        'reference'    => ["type"=>"txt", "required"=>true],
        'description'  => ["type"=>"txt", "required"=>true],
        'address'      => ["type"=>"txt"],
        'email'        => ["type"=>"txt", "required"=>true],
        'notes'        => ["type"=>"txt"],
        'payment_date' => ["type"=>"dat"],
        'quantity'     => ["type"=>"num"],
        'progress'     => ["type"=>"txt"],
        'issued_by'    => ["type"=>"num", "default"=>450],
        'nominal_code' => ["type"=>"num"],
        'type'         => ["type"=>"txt", "default"=>"invoice", "list"=>1],
        'bank_id'      => ["type"=>"num"],
        'message_id'   => ["type"=>"txt"],
    ];
    const FOLDER = '/var/lib/imutual/invoices';

    public function fetchLatestForNetwork(int $aff_id) {
        $sql = 'SELECT invoice_id as id FROM '.self::DB_TABLE.' WHERE aff_id = '.$aff_id.' AND status < 50 ORDER BY supply_to DESC, invoice_date DESC LIMIT 1';
        $result = $this->db->sql_query($sql);
        if ( $row=$this->db->sql_fetchrow($result) ) {
            return $this->read($row['id']);
        } else {
            return false;
        }
    }

    public function createHtml() {
        $net = $this->getObject('aff_id')->get();
        $data = $this->get();
        $data['date'] = date('j M Y',strtotime($data['invoice_date']));
        $data['address'] = str_replace(',',',<br>',$data['address']);

        if ( !$html = file_get_contents(IM_MENU_ROOT.'/templates/invoice.html') ) exit('Invoice template not found');
        $html = Strings::populatePlaceholders($html,$data);
        return $html;
    }

    public function getCreateUrl() {
        return 'newInvoice.php';
    }

    public function getFilename() {
        $this->mustBeLoaded();
        return self::FOLDER.'/invoice'.$this->id().'.pdf';
    }

    public function createPdf() {

        $filename = $this->getFilename();
        $html = $this->createHtml();

        // You can pass a filename, a HTML string, an URL or an options array to the constructor
        $pdf = new Pdf([
            'no-outline',         // Make Chrome not complain
            'margin-top'    => 0,
            'margin-right'  => 0,
            'margin-bottom' => 0,
            'margin-left'   => 0,

            // Default page options
            'disable-smart-shrinking',
        ]);
        $pdf->addPage($this->createHtml());
        if (!$pdf->saveAs($filename)) {
            throw new \Exception('Failed to generate PDF: '.$pdf->getError());
        }
        $this->update(['status'=>15]);
        return true;
    }

    public function send() {
        if ( !$invoiceId = $this->get('invoice_id') ) throw new \Exception('No record loaded');
        if ( $this->get('status') > 15 ) throw new \Exception('Invoice already sent');
        $filename = $this->getFilename();
        if ( !file_exists($filename) ) throw new \Exception('PDF not found');

        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
        $mailer = new \SendGrid\Mail\Mail();
        $mailer->setFrom(EMAIL_SENDER_ADDRESS, EMAIL_SENDER_NAME);
        $mailer->addTo($this->get('email'), $this->get('invoicee'));
        //$mailer->addBcc(EMAIL_SENDER_ADDRESS, EMAIL_SENDER_NAME);
        $mailer->setSubject('Invoice');
        $mailer->addContent("text/plain", 'Please find attached our latest invoice for your attention. For any queries, please contact Richard Yendall on 07827 328669');

        $file_encoded = base64_encode(file_get_contents($filename));
        $mailer->addAttachment(
            $file_encoded,
            "application/pdf",
            'invoice' . $this->get('invoice_id') . '.pdf',
            "attachment"
        );

        $response = $sendgrid->send($mailer);
        if ( empty($response->statusCode()) || $response->statusCode() < 200 || $response->statusCode() >= 300 ) {
            trigger_error($response->statusCode().print_r($response->headers(true),true));
            throw new \Exception('Non-2xx response: '.$response->statusCode());
        }
        $headers = $response->headers(true);
        $this->update(['status'=>20, 'message_id'=>$headers['X-Message-Id']]);
        // destroy instance of mailer
        unset($mailer);
    }

    protected function listFields() {
        return ['aff_id','reference','invoice_date','subtotal','total','status'];
    }

    protected function headerLinks() {
        $links = parent::headerLinks();
        if ( $this->get('status') <= 15 ) {
            $links[] = ['label'=>'Preview','url'=>'invoice.php?id='.$this->get('invoice_id').'&action=preview'];
        } elseif ( $this->get('status') > 15) {
            $links[] = ['label'=>'View PDF','url'=>'invoice.php?id='.$this->get('invoice_id').'&action=viewPdf'];
        }
        return $links;
    }
}