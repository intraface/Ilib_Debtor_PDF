<?php
/**
 * Creates a pdf of a debtor. The class implements the visitor pattern.
 *
 * The debtor must comply with a certain interface.
 *
 * PHP version 5
 *
 * TODO Put in the doc instead of having it started up.
 *
 * <code>
 * $file = new FileHandler($file_id);
 * $report = new Debtor_Report_Pdf($file);
 * $report->visit($debtor);
 * </code>
 *
 * @category Ilib_Debtor_Reports
 * @package  Intraface_Debtor
 * @author   Lars Olesen <lars@legestue.net>
 * @author   Sune Jensen <sj@sunet.dk>
 */

namespace Intraface;

use Intraface\DebtorPdf;

/**
 * Creates a pdf of a debtor. The class implements the visitor pattern.
 *
 * The debtor must comply with a certain interface.
 *
 * <code>
 * $file = new FileHandler($file_id);
 * $report = new Debtor_Report_Pdf($file);
 * $report->visit($debtor);
 * </code>
 *
 * @category Ilib_Debtor_Reports
 * @package  Intraface_Debtor
 * @author   Lars Olesen <lars@legestue.net>
 * @author   Sune Jensen <sj@sunet.dk>
 */
class DebtorVisitorPdf extends DebtorPdf
{
    /**
     * Constructor
     *
     * @param object $translation Used to do the translation in the class
     * @param object $file        File to use for the header
     *
     * @return void
     */
    function __construct($translation, $file = null)
    {
        parent::__construct($translation, $file);
    }

    /**
     * Visitor for the debtor
     *
     * @param object $debtor        The debtor to be written PDF
     * @param object $onlinepayment Optional onlinepayment
     *
     * @return void
     */
    function visit($debtor, $onlinepayment = null)
    {
        $this->doc = $this->getDocument();

        // Header.
        if (!empty($this->file) && $this->file->get('id') > 0) {
            $this->doc->addHeader($this->file->get('file_uri_pdf'));
        }

        // Reciever.
        $contact = $debtor->contact->address->get();
        if (is_object($debtor->contact_person)) {
            $contact["attention_to"] = $debtor->contact_person->get("name");
        }
        $contact['number'] = $debtor->contact->get('number');

        $this->addReceiver($contact);

        // Sender.
        $intranet_address = $debtor->getIntranetAddress();
        $intranet = $intranet_address->get();
        $intranet = array_merge($intranet, $debtor->getContactInformation());

        $this->addSender($intranet);

        // Add debtor data.
        $this->docinfo[0]["label"] = $this->translation->get($debtor->get('type').' number').":";
        $this->docinfo[0]["value"] = $debtor->get("number");
        $this->docinfo[1]["label"] = "Dato:";
        $this->docinfo[1]["value"] = $debtor->get("dk_this_date");
        if ($debtor->get("type") != "credit_note" && $debtor->get("due_date") != "0000-00-00") {
            $this->docinfo[2]["label"] = $this->translation->get($debtor->get('type').' due date').":";
            $this->docinfo[2]["value"] = $debtor->get("dk_due_date");
        }

        $this->addDebtorData($this->docinfo);

        // Add headline
        $title = $this->translation->get($debtor->get('type'));
        $this->addHeadline($title);

        // Add Message.
        if ($debtor->get('message')) {
            $this->addMessage($debtor->get('message'));
        }

        // Products.
        $this->addProductListHeadlines();
        $this->addProductsList($debtor, $debtor->getItems());

        // Payment condition.
        if ($debtor->get("type") == "invoice" || $debtor->get("type") == "order") {
            $this->addPaymentConditionVisit($debtor, $onlinepayment);
        }
    }

    function addPaymentConditionVisit($debtor, $onlinepayment)
    {
        $parameter = array(
            "contact" => $debtor->contact,
            "payment_text" => ucfirst($this->translation->get($debtor->get('type')))." ".$debtor->get("number"),
            "amount" => $debtor->get("total"),
            "payment" => $debtor->get('payment_total'),
            "payment_online" => 0,
            "due_date" => $debtor->get("dk_due_date"),
            "girocode" => $debtor->get("girocode"));

        if (is_object($onlinepayment)) {
            $onlinepayment->getDBQuery()->setFilter('belong_to', $debtor->get("type"));
            $onlinepayment->getDBQuery()->setFilter('belong_to_id', $debtor->get('id'));
            $onlinepayment->getDBQuery()->setFilter('status', 2);
            foreach ($onlinepayment->getlist() as $p) {
                $parameter['payment_online'] += $p["amount"];
            }
        }

        $this->addPaymentCondition($debtor->get("payment_method"), $parameter, $debtor->getPaymentInformation());

        $this->doc->setY('-'.$this->doc->get("font_spacing"));

        if ($this->doc->get('y') < $this->doc->get("margin_bottom") + $this->doc->get("font_spacing") * 2) {
            $this->doc->nextPage(true);
        }

        $text = explode("\r\n", $debtor->getInvoiceText());
        foreach ($text as $line) {
            if ($line == "") {
                $this->doc->setY('-'.$this->doc->get("font_spacing"));
                if ($this->doc->get('y') < $this->doc->get("margin_bottom") + $this->doc->get("font_spacing") * 2) {
                    $this->doc->nextPage(true);
                }
            } else {
                while ($line != "") {
                    $this->doc->setY('-'.($this->doc->get("font_padding_top") + $this->doc->get("font_size")));
                    $line = $this->doc->addTextWrap($this->doc->get('margin_left'), $this->doc->get('y'), $this->doc->get('content_width'), $this->doc->get("font_size"), $line);
                    $this->doc->setY('-'.$this->doc->get("font_padding_bottom"));
                    if ($this->doc->get('y') < $this->doc->get("margin_bottom") + $this->doc->get("font_spacing") * 2) {
                        $this->doc->nextPage(true);
                    }
                }
            }
        }
    }
}
