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
 * @package  Intraface_Debtor
 * @author   Lars Olesen <lars@legestue.net>
 * @author   Sune Jensen <sj@sunet.dk>
 * @category Ilib_Debtor_Reports
 * @license  GPL
 */
require_once dirname(__FILE__) . '/DebtorPdf.php'; 

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
 * @package  Intraface_Debtor
 * @author   Lars Olesen <lars@legestue.net>
 * @author   Sune Jensen <sj@sunet.dk>
 * @category Ilib_Debtor_Reports
 * @license  GPL
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
     * @param object $debtor The debtor to be written
     *
     * @return void
     */
    function visit($debtor, $onlinepayment = null)
    {
        $this->doc = $this->getDocument();

        // Header.
        if (!empty($this->file) AND $this->file->get('id') > 0) {
            $this->doc->addHeader($this->file->get('file_uri_pdf'));
        }

        // WHAT IS THIS FOR?
        $this->doc->setY('-5');

        // Sender and Reciever.
        $this->addSenderAndRecieverVisit($debtor);

        // WHAT IS THIS FOR?
        $this->doc->setY('-'.$this->doc->get("font_spacing"));

        // WHAT IS THIS FOR?
        if ($this->doc->get('y') < $this->doc->get("margin_bottom") + $this->doc->get("font_spacing") * 2) {
            $this->doc->nextPage(true);
        }

        if ($debtor->get('message')) {
            $this->addMessage($debtor);
        }

        // Products.
        $apointX = $this->addProductListHeadlines();
        $items = $debtor->getItems();
        $this->addProductsList($debtor, $items, $apointX);
        
        // Payment condition.
        if ($debtor->get("type") == "invoice" || $debtor->get("type") == "order") {
            $this->addPaymentConditionVisit($debtor, $onlinepayment);
        }
    }

    function addMessage($debtor)
    {
        $text = explode("\r\n", $debtor->get('message'));
        foreach ($text AS $line) {
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
    
    function addSenderAndRecieverVisit($debtor)
    {
        $contact = $debtor->contact->address->get();
        if (is_object($debtor->contact_person)) {
            $contact["attention_to"] = $debtor->contact_person->get("name");
        }
        $contact['number'] = $debtor->contact->get('number');

        $intranet_address = $debtor->getIntranetAddress();
        $intranet = $intranet_address->get();
        $intranet = array_merge($intranet, $debtor->getContactInformation());

        $this->docinfo[0]["label"] = $this->translation->get($debtor->get('type').' number').":";
        $this->docinfo[0]["value"] = $debtor->get("number");
        $this->docinfo[1]["label"] = "Dato:";
        $this->docinfo[1]["value"] = $debtor->get("dk_this_date");
        if ($debtor->get("type") != "credit_note" && $debtor->get("due_date") != "0000-00-00") {
            $this->docinfo[2]["label"] = $this->translation->get($debtor->get('type').' due date').":";
            $this->docinfo[2]["value"] = $debtor->get("dk_due_date");
        }

        $title = $this->translation->get($debtor->get('type'));

        $this->addRecieverAndSender($contact, $intranet, $title, $this->docinfo);    
    }
    
    function addProductListHeadlines()
    {
        $this->doc->setY('-40'); // space to the product list

        $apointX["varenr"] = 80;
        $apointX["tekst"] = 90;
        $apointX["antal"] = $this->doc->get("right_margin_position") - 150;
        $apointX["enhed"] = $this->doc->get('right_margin_position') - 145;
        $apointX["pris"] = $this->doc->get('right_margin_position') - 60;
        $apointX["beloeb"] = $this->doc->get('right_margin_position');
        $apointX["tekst_width"] = $this->doc->get('right_margin_position') - $this->doc->get("margin_left") - $apointX["tekst"] - 60;
        $apointX["tekst_width_small"] = $apointX["antal"] - $this->doc->get("margin_left") - $apointX["tekst"];

        $this->doc->addText($apointX["varenr"] - $this->doc->getTextWidth($this->doc->get("font_size"), "Varenr."), $this->doc->get('y'), $this->doc->get("font_size"), "Varenr.");
        $this->doc->addText($apointX["tekst"], $this->doc->get('y'), $this->doc->get("font_size"), "Tekst");
        $this->doc->addText($apointX["antal"] - $this->doc->getTextWidth($this->doc->get("font_size"), "Antal"), $this->doc->get('y'), $this->doc->get("font_size"), "Antal");
        $this->doc->addText($apointX["pris"] - $this->doc->getTextWidth($this->doc->get("font_size"), "Pris"), $this->doc->get('y'), $this->doc->get("font_size"), "Pris");
        $this->doc->addText($apointX["beloeb"] - $this->doc->getTextWidth($this->doc->get("font_size"), "Beløb") -3, $this->doc->get('y'), $this->doc->get("font_size"), "Beløb");

        $this->doc->setY('-'.($this->doc->get("font_spacing") - $this->doc->get("font_size")));

        $this->doc->line($this->doc->get("margin_left"), $this->doc->get('y'), $this->doc->get('right_margin_position'), $this->doc->get('y'));
    
        return $apointX;
    }
    
    function addProductsList($debtor, $items, $apointX)
    {
        $total = 0;
        if ($debtor->getCurrency()) {
            $total_currency = 0;
        }

        if (isset($items[0]["vat"])) {
            $vat = $items[0]["vat"];
        } else {
            $vat = 0;
        }

        $bg_color = 0;

        for ($i = 0, $max = count($items); $i <  $max; $i++) {
            $vat = $items[$i]["vat"];

            if ($bg_color == 1) {
                $this->doc->setColor(0.8, 0.8, 0.8);
                $this->doc->filledRectangle($this->doc->get("margin_left"), $this->doc->get('y') - $this->doc->get("font_spacing"), $this->doc->get('right_margin_position') - $this->doc->get("margin_left"), $this->doc->get("font_spacing"));
                $this->doc->setColor(0, 0, 0);
            }

            $this->doc->setY('-'.($this->doc->get("font_padding_top") + $this->doc->get("font_size")));
            $this->doc->addText($apointX["varenr"] - $this->doc->getTextWidth($this->doc->get("font_size"), $items[$i]["number"]), $this->doc->get('y'), $this->doc->get("font_size"), $items[$i]["number"]);

            if ($items[$i]["unit"] != "") {
                $this->doc->addText($apointX["antal"] - $this->doc->getTextWidth($this->doc->get("font_size"), number_format($items[$i]["quantity"], 2, ",", ".")), $this->doc->get('y'), $this->doc->get("font_size"), number_format($items[$i]["quantity"], 2, ",", "."));
                $this->doc->addText($apointX["enhed"], $this->doc->get('y'), $this->doc->get("font_size"), $this->translation->get($items[$i]["unit"], 'product'));
                if ($debtor->getCurrency()) {
                    $this->doc->addText($apointX["pris"] - $this->doc->getTextWidth($this->doc->get("font_size"), $items[$i]["price_currency"]->getAsLocale('da_dk', 2)), $this->doc->get('y'), $this->doc->get("font_size"), $items[$i]["price_currency"]->getAsLocale('da_dk', 2));
                } else {
                    $this->doc->addText($apointX["pris"] - $this->doc->getTextWidth($this->doc->get("font_size"), $items[$i]["price"]->getAsLocale('da_dk', 2)), $this->doc->get('y'), $this->doc->get("font_size"), $items[$i]["price"]->getAsLocale('da_dk', 2));
                }
            }
            if ($debtor->getCurrency()) {
                $amount = $items[$i]["quantity"] * $items[$i]["price_currency"]->getAsIso(2);
            } else {
                $amount = $items[$i]["quantity"] * $items[$i]["price"]->getAsIso(2);
            }
            $total += $amount;

            $this->doc->addText($apointX["beloeb"] - $this->doc->getTextWidth($this->doc->get("font_size"), number_format($amount, 2, ",", ".")), $this->doc->get('y'), $this->doc->get("font_size"), number_format($amount, 2, ",", "."));

            $tekst = $items[$i]["name"];
            $first = true;

            while ($tekst != "") {

                if (!$first) {
                    // first line has already got coloured
                    if ($bg_color == 1) {
                        $this->doc->setColor(0.8, 0.8, 0.8);
                        $this->doc->filledRectangle($this->doc->get("margin_left"), $this->doc->get('y') - $this->doc->get("font_spacing"), $this->doc->get('right_margin_position') - $this->doc->get("margin_left"), $this->doc->get("font_spacing"));
                        $this->doc->setColor(0, 0, 0);
                    }
                    $this->doc->setY('-'.($this->doc->get("font_padding_top") + $this->doc->get("font_size")));
                }
                $first = false;


                $tekst = $this->doc->addTextWrap($apointX["tekst"], $this->doc->get('y'), $apointX["tekst_width_small"], $this->doc->get("font_size"), $tekst);
                $this->doc->setY('-'.$this->doc->get("font_padding_bottom"));
                if ($this->doc->get('y') < $this->doc->get("margin_bottom") + $this->doc->get("font_spacing") * 2) {
                    $this->doc->nextPage(true);
                }
            }

            if ($items[$i]["description"] != "") {
                // space to the text
                $this->doc->setY('-'.($this->doc->get("font_spacing")/2));
                if ($bg_color == 1) {
                    $this->doc->setColor(0.8, 0.8, 0.8);
                    $this->doc->filledRectangle($this->doc->get("margin_left"), $this->doc->get('y'), $this->doc->get('right_margin_position') - $this->doc->get("margin_left"), $this->doc->get("font_spacing")/2);
                    $this->doc->setColor(0, 0, 0);
                }

                $desc_line = explode("\r\n", $items[$i]["description"]);
                foreach ($desc_line AS $line) {
                    if ($line == "") {
                        if ($bg_color == 1) {
                            $this->doc->setColor(0.8, 0.8, 0.8);
                            $this->doc->filledRectangle($this->doc->get("margin_left"), $this->doc->get('y') - $this->doc->get("font_spacing"), $this->doc->get('right_margin_position') - $this->doc->get("margin_left"), $this->doc->get("font_spacing"));
                            $this->doc->setColor(0, 0, 0);
                        }
                        $this->doc->setY('-'.$this->doc->get("font_spacing"));
                        if ($this->doc->get('y') < $this->doc->get("margin_bottom") + $this->doc->get("font_spacing") * 2) {
                            $this->doc->nextPage(true);
                        }
                    } else {
                        while ($line != "") {

                            if ($bg_color == 1) {
                                $this->doc->setColor(0.8, 0.8, 0.8);
                                $this->doc->filledRectangle($this->doc->get("margin_left"), $this->doc->get('y') - $this->doc->get("font_spacing"), $this->doc->get('right_margin_position') - $this->doc->get("margin_left"), $this->doc->get("font_spacing"));
                                $this->doc->setColor(0, 0, 0);
                            }

                            $this->doc->setY('-'.($this->doc->get("font_padding_top") + $this->doc->get("font_size")));
                            $line = $this->doc->addTextWrap($apointX["tekst"], $this->doc->get('y') + 1, $apointX["tekst_width"], $this->doc->get("font_size"), $line); // Ups Ups, hvor kommer '+ 1' fra - jo ser du, ellers kappes det nederste af teksten!
                            $this->doc->setY('-'.$this->doc->get("font_padding_bottom"));

                            if ($this->doc->get('y') < $this->doc->get("margin_bottom") + $this->doc->get("font_spacing") * 2) {
                                $this->doc->nextPage(true);
                            }
                        }
                    }
                }

            }

            if ($this->doc->get('y') < $this->doc->get("margin_bottom") + $this->doc->get("font_spacing") * 2) {
                $this->doc->nextPage(true);
            }

            // If products with VAT and next post is without we add VAT.
            if (($vat == 1 && isset($items[$i+1]["vat"]) && $items[$i+1]["vat"] == 0) || ($vat == 1 && $i+1 >= $max)) {
                // If VAT on current product, but next has no VAT OR if VAT and last product

                ($bg_color == 1) ? $bg_color = 0 : $bg_color = 1;

                if ($bg_color == 1) {
                    $this->doc->setColor(0.8, 0.8, 0.8);
                    $this->doc->filledRectangle($this->doc->get("margin_left"), $this->doc->get('y') - $this->doc->get("font_spacing"), $this->doc->get('right_margin_position') - $this->doc->get("margin_left"), $this->doc->get("font_spacing"));
                    $this->doc->setColor(0, 0, 0);
                }

                $this->doc->setLineStyle(0.5);
                $this->doc->line($this->doc->get("margin_left"), $this->doc->get('y'), $this->doc->get('right_margin_position'), $this->doc->get('y'));
                $this->doc->setY('-'.($this->doc->get("font_size") + $this->doc->get("font_padding_top")));
                $this->doc->addText($apointX["tekst"], $this->doc->get('y'), $this->doc->get("font_size"), "<b>25% moms af ".number_format($total, 2, ",", ".")."</b>");
                $this->doc->addText($apointX["beloeb"] - $this->doc->getTextWidth($this->doc->get("font_size"), "<b>".number_format($total * 0.25, 2, ",", ".")."</b>"), $this->doc->get('y'), $this->doc->get("font_size"), "<b>".number_format($total * 0.25, 2, ",", ".")."</b>");
                $total = $total * 1.25;
                $this->doc->setY('-'.$this->doc->get("font_padding_bottom"));
                $this->doc->line($this->doc->get("margin_left"), $this->doc->get('y'), $this->doc->get('right_margin_position'), $this->doc->get('y'));
                $this->doc->setLineStyle(1);
                $this->doc->setY('-1');
            }

            ($bg_color == 1) ? $bg_color = 0 : $bg_color = 1;
        }


        if ($this->doc->get('y') < $this->doc->get("margin_bottom") + $this->doc->get("font_spacing") * 2) {
            $this->doc->nextPage();
        }

        $this->doc->setLineStyle(1);
        $this->doc->line($this->doc->get("margin_left"), $this->doc->get('y'), $this->doc->get('right_margin_position'), $this->doc->get('y'));
        if ($debtor->getCurrency()) {
            $currency_iso_code = $debtor->getCurrency()->getType()->getIsoCode();
            $debtor_total = $debtor->get("total_currency");
        } else {
            $currency_iso_code = 'DKK';
            $debtor_total = $debtor->get("total");
        }

        if ($debtor->get("round_off") == 1 && $debtor->get("type") == "invoice" && $total != $debtor_total) {
            $this->doc->setY('-'.($this->doc->get("font_size") + $this->doc->get("font_padding_top")));
            $this->doc->addText($apointX["enhed"], $this->doc->get('y'), $this->doc->get("font_size"), "I alt:");
            $this->doc->addText($apointX["beloeb"] - $this->doc->getTextWidth($this->doc->get("font_size"), number_format($total, 2, ",", ".")), $this->doc->get('y'), $this->doc->get("font_size"), number_format($total, 2, ",", "."));
            $this->doc->setY('-'.$this->doc->get("font_padding_bottom"));

            $total_text = "Total afrundet ".$currency_iso_code.":";
        } else {
            $total_text = "Total ".$currency_iso_code.":";
        }

        if ($this->doc->get('y') < $this->doc->get("margin_bottom") + $this->doc->get("font_spacing") * 2) {
            $this->doc->nextPage(true);
        }

        $this->doc->setY('-'.($this->doc->get("font_size") + $this->doc->get("font_padding_top")));
        $this->doc->addText($apointX["enhed"], $this->doc->get('y'), $this->doc->get("font_size"), "<b>".$total_text."</b>");
        $this->doc->addText($apointX["beloeb"] - $this->doc->getTextWidth($this->doc->get("font_size"), "<b>".number_format($debtor_total, 2, ",", ".")."</b>"), $this->doc->get('y'), $this->doc->get("font_size"), "<b>".number_format($debtor_total, 2, ",", ".")."</b>");
        $this->doc->setY('-'.$this->doc->get("font_padding_bottom"));
        $this->doc->line($apointX["enhed"], $this->doc->get('y'), $this->doc->get('right_margin_position'), $this->doc->get('y'));    
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
            foreach ($onlinepayment->getlist() AS $p) {
                $parameter['payment_online'] += $p["amount"];
            }
        }

        $this->addPaymentCondition($debtor->get("payment_method"), $parameter, $debtor->getPaymentInformation());

        $this->doc->setY('-'.$this->doc->get("font_spacing"));

        if ($this->doc->get('y') < $this->doc->get("margin_bottom") + $this->doc->get("font_spacing") * 2) {
            $this->doc->nextPage(true);
        }

        $text = explode("\r\n", $debtor->getInvoiceText());
        foreach ($text AS $line) {
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
