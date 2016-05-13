<?php
/**
 * Creates a pdf of a debtor. The class implements the visitor pattern.
 *
 * The debtor must comply with a certain interface.
 *
 * PHP version 5
 *
 * @category Ilib_Debtor_Reports
 * @package  Intraface_Debtor
 * @author   Lars Olesen <lars@legestue.net>
 * @author   Sune Jensen <sj@sunet.dk>
 */

namespace Intraface;

/**
 * Creates a pdf of a debtor. The class implements the visitor pattern.
 *
 * The debtor must comply with a certain interface.
 *
 * @category Ilib_Debtor_Reports
 * @package  Intraface_Debtor
 * @author   Lars Olesen <lars@legestue.net>
 * @author   Sune Jensen <sj@sunet.dk>
 */
class DebtorPdf
{
    protected $file;
    protected $translation;
    protected $doc;
    protected $box_height;
    protected $box_top;
    protected $apointX;
    const BOX_PADDING_TOP = 8;
    const BOX_PADDING_BOTTOM = 9;
    const BOX_WIDTH = 275;

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
        if (!is_object($translation)) {
            throw new \Exception('translation is not an object');
        }

        $this->translation = $translation;
        $this->file = $file;
    }

    /**
     * Creates the document to write
     *
     * @return PdfMaker object
     */
    protected function getDocument()
    {
        $doc = new Pdf();
        return $doc;
    }

    /**
     * Output the debtor
     *
     * @param string $type     Output to type (string or file)
     * @param string $filename Filename
     *
     * @return void
     */
    function output($type = 'string', $filename = 'debtor.pdf', $turn_off_compression = false)
    {
        switch ($type) {
            case 'string':
                return $this->doc->output($turn_off_compression);
            break;
            case 'file':
                $data = $this->doc->output($turn_off_compression);
                return $this->doc->writeDocument($data, $filename);
            break;
            case 'stream':
            default:
                return $this->doc->stream();
            break;
        }
    }

    /**
     * Add headline
     *
     * @param string $title Title to show on the pdf
     *
     * @return void
     */
    function addHeadline($title)
    {
        $this->doc->setX(0);
        $this->doc->addText($this->doc->get('x'), $this->doc->get('y'), $this->doc->get("font_size") + 8, $title);

        $this->doc->setY('-' . $this->doc->get("font_spacing"));

        /*
        if ($this->doc->get('y') < $this->doc->get("margin_bottom") + $this->doc->get("font_spacing") * 2) {
            $this->doc->nextPage(true);
        }
        */
    }

    /**
     * Add data about the debtor
     *
     * @param array $docinfo Info about the debtor, e.g. invoice date and number
     *
     * @return void
     */
    function addDebtorData(array $docinfo)
    {
        if (is_array($docinfo) && count($docinfo) > 0) {
            $this->doc->setY('-10'); // $pointY -= 10;
            $box_small_top = $this->doc->get('y');
            $box_small_height = count($docinfo) * $this->doc->get("font_spacing") + self::BOX_PADDING_TOP + self::BOX_PADDING_BOTTOM;
            $this->doc->setY('-' . self::BOX_PADDING_TOP); // $pointY -= self::BOX_PADDING_TOP;

            foreach ($docinfo as $info) {
                $this->doc->setY('-'.$this->doc->get('font_spacing'));
                $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), $info["label"]);
                $this->doc->addText($this->doc->get("right_margin_position") - 40 - $this->doc->getTextWidth($this->doc->get("font_size"), $info["value"]), $this->doc->get('y'), $this->doc->get("font_size"), $info["value"]);
            }

            $this->doc->setValue('y', $box_small_top - $box_small_height); // Sets exact position
            $this->doc->roundRectangle($this->doc->get('x'), $this->doc->get('y'), $this->doc->get('right_margin_position') - $this->doc->get('x'), $box_small_height, 10);
        } else {
            $this->doc->setY($this->doc->get("font_size") + 12); // $pointY = $this->doc->get("font_size") + 12;
        }
    }

    /**
     * Adds the sender of the invoice
     *
     * @param array $intranet Info about the sender
     *
     * @return void
     */
    function addSender(array $intranet)
    {
        if (is_array($intranet) && count($intranet) > 0) {
            $this->doc->setX(self::BOX_WIDTH + 10);
            $this->doc->setValue('y', $this->box_top); // sets exact position
            $this->doc->setY('-'.$this->doc->get("font_spacing"));
            $this->doc->addText($this->doc->get('right_margin_position') - 40, $this->doc->get('y') + 4, $this->doc->get("font_size") - 4, "Afsender");

            $this->doc->setY('-' . self::BOX_PADDING_TOP); // $pointY -= self::BOX_PADDING_TOP;
            $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), "<b>".$intranet["name"]."</b>");

            $this->doc->setY('-'.$this->doc->get("font_spacing")); // $pointY -= $this->doc->get("font_spacing");
            $line = explode("\r\n", $intranet["address"]);
            $line_count = count($line);
            for ($i = 0; $i < $line_count; $i++) {
                $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), $line[$i]);
                $this->doc->setY('-'.$this->doc->get("font_spacing")); // $pointY -= $this->doc->get("font_spacing");
                if ($i == 2) {
                    $i = count($line);
                }
            }
            $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), $intranet["postcode"]." ".$intranet["city"]);
            $this->doc->setY('-'.($this->doc->get("font_spacing") * 2)); // $pointY -= $this->doc->get("font_spacing") * 2;

            $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), "CVR.:");
            $this->doc->addText($this->doc->get('x') + 10 + 60, $this->doc->get('y'), $this->doc->get("font_size"), $intranet["cvr"]);
            $this->doc->setY('-'.$this->doc->get("font_spacing")); // $pointY -= $this->doc->get("font_spacing");

            if (!empty($intranet["contact_person"]) && $intranet['contact_person'] != $intranet["name"]) {
                $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), "Kontakt:");
                $this->doc->addText($this->doc->get('x') + 10 + 60, $this->doc->get('y'), $this->doc->get("font_size"), $intranet["contact_person"]);
                $this->doc->setY('-'.$this->doc->get("font_spacing")); // $pointY -= $this->doc->get("font_spacing");
            }

            $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), "Telefon:");
            $this->doc->addText($this->doc->get('x') + 10 + 60, $this->doc->get('y'), $this->doc->get("font_size"), $intranet["phone"]);
            $this->doc->setY('-'.$this->doc->get("font_spacing")); // $pointY -= $this->doc->get("font_spacing");

            $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), "E-mail:");
            $this->doc->addText($this->doc->get('x') + 10 + 60, $this->doc->get('y'), $this->doc->get("font_size"), $intranet["email"]);

            if ($this->box_top - $this->doc->get('y') + self::BOX_PADDING_BOTTOM > $this->box_height) {
                $this->box_height = $this->box_top - $this->doc->get('y') + self::BOX_PADDING_BOTTOM;
            }
        }
        $this->doc->setValue('y', $this->box_top - $this->box_height); // sets exact position
        // box around the sender
        $this->doc->roundRectangle($this->doc->get('x'), $this->doc->get('y'), $this->doc->get('right_margin_position') - $this->doc->get('x'), $this->box_height, 10);
    }

    /**
     * Add receiver of the debtor
     *
     * @param array $contact Information about the receiver
     *
     * @return void
     */
    function addReceiver($contact)
    {
        $this->doc->setY('-5');

        $this->box_top = $this->doc->get('y'); // $pointY;

        $this->doc->setY('-' . $this->doc->get("font_spacing"));
        $this->doc->addText($this->doc->get('x') + self::BOX_WIDTH - 40, $this->doc->get('y') + 4, $this->doc->get("font_size") - 4, "Modtager");
        $this->doc->setY('-' . self::BOX_PADDING_TOP);
        $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), "<b>".$contact["name"]."</b>");

        $this->doc->setY('-' . $this->doc->get("font_spacing"));

        if (isset($contact["attention_to"]) && $contact["attention_to"] != "") {
            $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), "Att: ".$contact["attention_to"]);
            $this->doc->setY('-' . $this->doc->get('font_spacing'));
        }

        $line = explode("\r\n", $contact["address"]);
        $line_count = count($line);
        for ($i = 0; $i < $line_count; $i++) {
            $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), $line[$i]);
            $this->doc->setY('-'.$this->doc->get("font_spacing"));

            if ($i == 2) {
                $i = count($line);
            }
        }
        $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), $contact["postcode"]." ".$contact["city"]);
        $this->doc->setY('-'.$this->doc->get("font_spacing"));

        if (isset($contact["country"]) && $contact["country"] != "") {
            $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), $contact["country"]);
            $this->doc->setY('-'.$this->doc->get('font_spacing'));
        }

        $this->doc->setY('-'.$this->doc->get("font_spacing"));

        if ($contact["cvr"] != "") {
            $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), "CVR.:");
            $this->doc->addText($this->doc->get('x') + 10 + 60, $this->doc->get('y'), $this->doc->get("font_size"), $contact["cvr"]);
            $this->doc->setY('-'.$this->doc->get("font_spacing"));
        }
        $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), "Kontaktnr.:");
        $this->doc->addText($this->doc->get('x') + 10 + 60, $this->doc->get('y'), $this->doc->get("font_size"), $contact["number"]);
        if ($contact["ean"]) {
            $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y') - 15, $this->doc->get("font_size"), "EANnr.:");
            $this->doc->addText($this->doc->get('x') + 10 + 60, $this->doc->get('y') - 15, $this->doc->get("font_size"), $contact["ean"]);
        }

        $this->box_height = $this->box_top - $this->doc->get('y') + self::BOX_PADDING_BOTTOM;

        $this->doc->setValue('y', $this->box_top - $this->box_height); // sets exact position

        // box around the receiver
        $this->doc->roundRectangle($this->doc->get("margin_left"), $this->doc->get('y'), self::BOX_WIDTH, $this->box_height, 10);
    }

    /**
     * Adds the payment condition to the document
     *
     * @todo Make the use of payment info better so it will not crash the server
     *       Create some checks.
     *
     * @param integer $payment_method The chosen payment method
     * @param array   $parameter      array("contact" => (object), "payment_text" => (string), "amount" => (double), "due_date" => (string), "girocode" => (string));
     * @param array   $payment_info   The payment information
     *
     * @return void
     */
    function addPaymentCondition($payment_method, $parameter, $payment_info = array())
    {
        if (!is_array($parameter)) {
            throw new \Exception("The 3rd parameter to addPaymentCondition should be an array!");
        }

        if (!is_object($parameter['contact']->address)) {
            throw new \Exception("2nd parameter of array does not contain contact object with Address");
        }

        // adding payments
        $amount = $this->addPayments($parameter['payment'], $parameter['payment_online'], $parameter['amount']);

        // Payment information
        if ($amount <= 0) {
            return;
        }

        $this->doc->setY('-20'); // Distance to payment info

        $payment_line = 26;
        $payment_left = 230;
        $payment_right = $this->doc->get("right_margin_position") - $this->doc->get("margin_left") - $payment_left;

        if ($this->doc->get('y') < $this->doc->get("margin_bottom") + $this->doc->get("font_spacing") + 4 + $payment_line * 3) {
            $this->doc->nextPage(true);
        }

        // Black bar
        $this->doc->setLineStyle(1);
        $this->doc->setColor(0, 0, 0);
        $this->doc->filledRectangle($this->doc->get("margin_left"), $this->doc->get('y') - $this->doc->get("font_spacing") - 4, $this->doc->get("right_margin_position") - $this->doc->get("margin_left"), $this->doc->get("font_spacing") + 4);
        $this->doc->setColor(1, 1, 1);
        $this->doc->setY('-'.($this->doc->get("font_size") + $this->doc->get("font_padding_top") + 2));
        $this->doc->addText($this->doc->get('x') + 4, $this->doc->get('y'), $this->doc->get("font_size") + 2, "Indbetalingsoplysninger");
        $this->doc->setColor(0, 0, 0);
        $this->doc->setY('-'.($this->doc->get("font_padding_bottom") + 2));

        $payment_start = $this->doc->get('y');

        if ($payment_method == 1) {
            $this->addPaymentBankTransfer($payment_line, $payment_left, $payment_right, $parameter, $payment_start, $amount, $payment_info);
        } elseif ($payment_method == 2) {
            $this->addPaymentGiroaccount($payment_line, $payment_left, $payment_right, $parameter, $payment_start, $amount, $payment_info);
        } elseif ($payment_method == 3) {
            $this->addPaymentGiroaccount71($payment_line, $payment_left, $payment_right, $parameter, $payment_start, $amount, $payment_info);
        }
    }

    function addPaymentGiroaccount71($payment_line, $payment_left, $payment_right, $parameter, $payment_start, $amount, $payment_info)
    {
        $this->doc->rectangle($this->doc->get('x'), $this->doc->get('y') - $payment_line * 2, $this->doc->get("right_margin_position") - $this->doc->get("margin_left"), $payment_line * 2);
        $this->doc->line($this->doc->get("margin_left"), $this->doc->get('y') - $payment_line, $this->doc->get("right_margin_position"), $this->doc->get('y') - $payment_line);
        $this->doc->line($this->doc->get('x') + $payment_left, $this->doc->get('y'), $this->doc->get('x') + $payment_left, $this->doc->get('y') - $payment_line);

        $this->doc->setY('-7');

        $this->doc->addText($this->doc->get('x') + 4, $this->doc->get('y'), $this->doc->get("font_size") - 4, "Beløb DKK:");
        $this->doc->setY('-'.($payment_line - 12));
        $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), number_format($amount, 2, ",", "."));

        $this->doc->setValue('y', $payment_start); // Sætter eksakt position
        $this->doc->setY('-7');

        $this->doc->addText($this->doc->get('x') + $payment_left + 4, $this->doc->get('y'), $this->doc->get("font_size") - 4, "Betalingsdato:");
        $this->doc->setY('-'.($payment_line - 12));
        $this->doc->addText($this->doc->get('x') + $payment_left + 10, $this->doc->get('y'), $this->doc->get("font_size"), $parameter["due_date"]);

        $this->doc->setValue('y', $payment_start - $payment_line); // sætter eksakt position
        $this->doc->setY('-7');

        $this->doc->addText($this->doc->get('x') + 4, $this->doc->get('y'), $this->doc->get("font_size") - 4, "Kodelinje: (Ej til maskinel aflæsning)");
        $this->doc->setY('-'.($payment_line - 12));
        // TODO change the - back to <> but it does not work

        $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), "+71- ".str_repeat("0", 15 - strlen($parameter["girocode"])).$parameter["girocode"]." +".$payment_info["giro_account_number"]."-");
    }

    function addPaymentGiroaccount($payment_line, $payment_left, $payment_right, $parameter, $payment_start, $amount, $payment_info)
    {
        $this->doc->rectangle($this->doc->get('x'), $this->doc->get('y') - $payment_line * 3, $this->doc->get("right_margin_position") - $this->doc->get("margin_left"), $payment_line * 3);
        $this->doc->line($this->doc->get('x') + $payment_left, $this->doc->get('y') - $payment_line * 3, $this->doc->get('x') + $payment_left, $this->doc->get('y'));
        $this->doc->line($this->doc->get('x') + $payment_left, $this->doc->get('y') - $payment_line, $this->doc->get("right_margin_position"), $this->doc->get('y') - $payment_line);
        $this->doc->line($this->doc->get('x') + $payment_left, $this->doc->get('y') - $payment_line * 2, $this->doc->get("right_margin_position"), $this->doc->get('y') - $payment_line * 2);
        $this->doc->line($this->doc->get('x') + $payment_left + $payment_right / 2, $this->doc->get('y') - $payment_line * 2, $this->doc->get('x') + $payment_left + $payment_right / 2, $this->doc->get('y') - $payment_line);

        $this->doc->setY('-7');
        $this->doc->addText($this->doc->get('x') + 4, $this->doc->get('y'), $this->doc->get("font_size") - 4, "Indbetaler:");
        $this->doc->setY('-'.$this->doc->get('font_spacing'));

        $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), $parameter["contact"]->address->get("name"));
        $this->doc->setY('-'.$this->doc->get('font_spacing'));
        $line = explode("\r\n", $parameter["contact"]->address->get("address"));
        $line_count = count($line);
        for ($i = 0; $i < $line_count; $i++) {
            $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), $line[$i]);
            $this->doc->setY('-'.$this->doc->get('font_spacing'));
            if ($i == 2) {
                $i = count($line);
            }
        }
        $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), $parameter["contact"]->address->get("postcode") . " " . $parameter["contact"]->address->get("city"));

        $this->doc->setValue('y', $payment_start); // Sets exact position
        $this->doc->setY('-7');

        $this->doc->addText($this->doc->get('x') + $payment_left + 4, $this->doc->get('y'), $this->doc->get("font_size") - 4, "Tekst til modtager:");
        $this->doc->setY('-'.($payment_line - 12));
        $this->doc->addText($this->doc->get('x') + $payment_left + 10, $this->doc->get('y'), $this->doc->get("font_size"), $parameter["payment_text"]);

        $this->doc->setValue('y', $payment_start - $payment_line); // Sets exact position
        $this->doc->setY('-7');

        $this->doc->addText($this->doc->get('x') + $payment_left + 4, $this->doc->get('y'), $this->doc->get("font_size") - 4, "Beløb DKK:");
        $this->doc->setY('-'.($payment_line - 12));
        $this->doc->addText($this->doc->get('x') + $payment_left + 10, $this->doc->get('y'), $this->doc->get("font_size"), number_format($amount, 2, ",", "."));

        $this->doc->setValue('y', $payment_start - $payment_line); // Sets exact position
        $this->doc->setY('-7');

        $this->doc->addText($this->doc->get('x') + $payment_left + $payment_right / 2 + 4, $this->doc->get('y'), $this->doc->get("font_size") - 4, "Betalingsdato:");
        $this->doc->setY('-'.($payment_line - 12));
        $this->doc->addText($this->doc->get('x') + $payment_left + $payment_right / 2 + 10, $this->doc->get('y'), $this->doc->get("font_size"), $parameter["due_date"]);

        $this->doc->setValue('y', $payment_start - $payment_line * 2); // Sets exact position
        $this->doc->setY('-7');

        $this->doc->addText($this->doc->get('x') + $payment_left + 4, $this->doc->get('y'), $this->doc->get("font_size") - 4, "Kodelinje: (Ej til maskinel aflæsning)");
        $this->doc->setY('-'.($payment_line - 12));

        // TODO change the - back to <> but it does not work right now
        $this_text = '+01-'.str_repeat(' ', 20).'+'.$payment_info['giro_account_number'].'-';
        $this->doc->addText($this->doc->get('x') + $payment_left + 10, $this->doc->get('y'), $this->doc->get('font_size'), $this_text);
    }

    function addPaymentBankTransfer($payment_line, $payment_left, $payment_right, $parameter, $payment_start, $amount, $payment_info)
    {
        $this->doc->rectangle($this->doc->get('x'), $this->doc->get('y') - $payment_line * 2, $this->doc->get("right_margin_position") - $this->doc->get("margin_left"), $payment_line * 2);
        $this->doc->line($this->doc->get('x') + $payment_left, $this->doc->get('y') - $payment_line * 2, $this->doc->get('x') + $payment_left, $this->doc->get('y'));
        $this->doc->line($this->doc->get('x'), $this->doc->get('y') - $payment_line, $this->doc->get("right_margin_position"), $this->doc->get('y') - $payment_line);
        $this->doc->line($this->doc->get('x') + $payment_left / 2, $this->doc->get('y') - $payment_line * 2, $this->doc->get('x') + $payment_left / 2, $this->doc->get('y') - $payment_line);

        $this->doc->setY('-7'); // $pointY -= 7;
        $this->doc->addText($this->doc->get('x') + 4, $this->doc->get('y'), $this->doc->get("font_size") - 4, "Bank:");
        $this->doc->setY('-'.($payment_line - 12)); // $pointY -= $payment_line - 12;
        $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), $payment_info["bank_name"]);

        $this->doc->setValue('y', $payment_start); // $pointY = $payment_start;
        $this->doc->setY('-7'); // $pointY -= 7;

        $this->doc->addText($this->doc->get('x') + $payment_left + 4, $this->doc->get('y'), $this->doc->get("font_size") - 4, "Tekst til modtager:");
        $this->doc->setY('-'.($payment_line - 12)); // $pointY -= $payment_line - 12;
        $this->doc->addText($this->doc->get('x') + $payment_left + 10, $this->doc->get('y'), $this->doc->get("font_size"), $parameter["payment_text"]);

        $this->doc->setValue('y', $payment_start - $payment_line); // Sets exact position
        $this->doc->setY('-7'); // $pointY -= 7;

        $this->doc->addText($this->doc->get('x') + 4, $this->doc->get('y'), $this->doc->get("font_size") - 4, "Beløb DKK:");
        $this->doc->setY('-'.($payment_line - 12)); // $this->setY('-'.($payment_line - 12));
        $this->doc->addText($this->doc->get('x') + 10, $this->doc->get('y'), $this->doc->get("font_size"), number_format($amount, 2, ",", "."));

        $this->doc->setValue('y', $payment_start - $payment_line); // Sets exact position
        $this->doc->setY('-7'); // $pointY -= 7;

        $this->doc->addText($this->doc->get('x') + $payment_left / 2 + 4, $this->doc->get('y'), $this->doc->get("font_size") - 4, "Betalingsdato:");
        $this->doc->setY('-'.($payment_line - 12));
        $this->doc->addText($this->doc->get('x') + $payment_left / 2 + 10, $this->doc->get('y'), $this->doc->get("font_size"), $parameter["due_date"]);

         $this->doc->setValue('y', $payment_start - $payment_line); // Sets exact position
         $this->doc->setY('-7');

         $this->doc->addText($this->doc->get('x') + $payment_left + 4, $this->doc->get('y'), $this->doc->get("font_size") - 4, "Regnr.:            Kontonr.:");
         $this->doc->setY('-'.($payment_line - 12));
         $this->doc->addText($this->doc->get('x') + $payment_left + 10, $this->doc->get('y'), $this->doc->get("font_size"), $payment_info["bank_reg_number"] . "       " . $payment_info["bank_account_number"]);
    }

    /**
     * Adds payments
     *
     * @param array $parameter Adds info about payments
     *
     * @return void
     */
    function addPayments($payment = 0, $payment_online = 0, $amount = 0)
    {
        if ($payment != 0 || $payment_online != 0) {
            $this->doc->setY('-20');

            if ($payment != 0) {
                $this->doc->setLineStyle(1.5);
                $this->doc->setColor(0, 0, 0);
                $this->doc->line($this->doc->get("margin_left"), $this->doc->get('y'), $this->doc->get("right_margin_position"), $this->doc->get('y'));
                $this->doc->setY('-'.$this->doc->get("font_padding_top"));
                $this->doc->setY('-'.$this->doc->get("font_size"));
                $this->doc->addText($this->doc->get('x') + 4, $this->doc->get('y'), $this->doc->get("font_size"), "Betalt");
                $this->doc->addText($this->doc->get("right_margin_position") - $this->doc->getTextWidth($this->doc->get("font_size"), number_format($payment, 2, ",", ".")), $this->doc->get('y'), $this->doc->get("font_size"), number_format($payment, 2, ",", "."));
                $this->doc->setY('-'.$this->doc->get("font_padding_bottom"));
            }

            if ($payment_online != 0) {
                $this->doc->setLineStyle(1.5);
                $this->doc->setColor(0, 0, 0);
                $this->doc->line($this->doc->get("margin_left"), $this->doc->get('y'), $this->doc->get("right_margin_position"), $this->doc->get('y'));

                $this->doc->setY('-'.$this->doc->get("font_padding_top"));
                $this->doc->setY('-'.$this->doc->get("font_size"));
                $this->doc->addText($this->doc->get('x') + 4, $this->doc->get('y'), $this->doc->get("font_size"), "Ventende betalinger");
                $this->doc->addText($this->doc->get("right_margin_position") - $this->doc->getTextWidth($this->doc->get("font_size"), number_format($payment_online, 2, ",", ".")), $this->doc->get('y'), $this->doc->get("font_size"), number_format($payment_online, 2, ",", "."));
                $this->doc->setY('-'.$this->doc->get("font_padding_bottom"));
            }

            $this->doc->line($this->doc->get("margin_left"), $this->doc->get('y'), $this->doc->get("right_margin_position"), $this->doc->get('y'));
        }

        return $amount = $amount - $payment_online - $payment;
    }

    /**
     * Add message to PDF
     *
     * @param object $debtor Debtor to retrive message from
     *
     * @return void
     */
    function addMessage($message)
    {
        $text = explode("\r\n", $message);
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

    /**
     * Add product headlines to product table
     *
     * @return array with points
     */
    function addProductListHeadlines()
    {
        $this->doc->setY('-40'); // space to the product list

        $this->apointX["varenr"] = 80;
        $this->apointX["tekst"] = 90;
        $this->apointX["antal"] = $this->doc->get("right_margin_position") - 150;
        $this->apointX["enhed"] = $this->doc->get('right_margin_position') - 145;
        $this->apointX["pris"] = $this->doc->get('right_margin_position') - 60;
        $this->apointX["beloeb"] = $this->doc->get('right_margin_position');
        $this->apointX["tekst_width"] = $this->doc->get('right_margin_position') - $this->doc->get("margin_left") - $this->apointX["tekst"] - 60;
        $this->apointX["tekst_width_small"] = $this->apointX["antal"] - $this->doc->get("margin_left") - $this->apointX["tekst"];

        $this->doc->addText($this->apointX["varenr"] - $this->doc->getTextWidth($this->doc->get("font_size"), "Varenr."), $this->doc->get('y'), $this->doc->get("font_size"), "Varenr.");
        $this->doc->addText($this->apointX["tekst"], $this->doc->get('y'), $this->doc->get("font_size"), "Tekst");
        $this->doc->addText($this->apointX["antal"] - $this->doc->getTextWidth($this->doc->get("font_size"), "Antal"), $this->doc->get('y'), $this->doc->get("font_size"), "Antal");
        $this->doc->addText($this->apointX["pris"] - $this->doc->getTextWidth($this->doc->get("font_size"), "Pris"), $this->doc->get('y'), $this->doc->get("font_size"), "Pris");
        $this->doc->addText($this->apointX["beloeb"] - $this->doc->getTextWidth($this->doc->get("font_size"), "Beløb") -3, $this->doc->get('y'), $this->doc->get("font_size"), "Beløb");

        $this->doc->setY('-'.($this->doc->get("font_spacing") - $this->doc->get("font_size")));

        $this->doc->line($this->doc->get("margin_left"), $this->doc->get('y'), $this->doc->get('right_margin_position'), $this->doc->get('y'));
    }

    /**
     * Add product headlines to product table
     *
     * @return array with points
     */
    function addProductsList($debtor, $items)
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
            $this->doc->addText($this->apointX["varenr"] - $this->doc->getTextWidth($this->doc->get("font_size"), $items[$i]["number"]), $this->doc->get('y'), $this->doc->get("font_size"), $items[$i]["number"]);

            if ($items[$i]["unit"] != "") {
                $this->doc->addText($this->apointX["antal"] - $this->doc->getTextWidth($this->doc->get("font_size"), number_format($items[$i]["quantity"], 2, ",", ".")), $this->doc->get('y'), $this->doc->get("font_size"), number_format($items[$i]["quantity"], 2, ",", "."));
                $this->doc->addText($this->apointX["enhed"], $this->doc->get('y'), $this->doc->get("font_size"), $this->translation->get($items[$i]["unit"], 'product'));
                if ($debtor->getCurrency()) {
                    $this->doc->addText($this->apointX["pris"] - $this->doc->getTextWidth($this->doc->get("font_size"), $items[$i]["price_currency"]->getAsLocale('da_dk', 2)), $this->doc->get('y'), $this->doc->get("font_size"), $items[$i]["price_currency"]->getAsLocale('da_dk', 2));
                } else {
                    $this->doc->addText($this->apointX["pris"] - $this->doc->getTextWidth($this->doc->get("font_size"), $items[$i]["price"]->getAsLocale('da_dk', 2)), $this->doc->get('y'), $this->doc->get("font_size"), $items[$i]["price"]->getAsLocale('da_dk', 2));
                }
            }
            if ($debtor->getCurrency()) {
                $amount = $items[$i]["quantity"] * $items[$i]["price_currency"]->getAsIso(2);
            } else {
                $amount = $items[$i]["quantity"] * $items[$i]["price"]->getAsIso(2);
            }
            $total += $amount;

            $this->doc->addText($this->apointX["beloeb"] - $this->doc->getTextWidth($this->doc->get("font_size"), number_format($amount, 2, ",", ".")), $this->doc->get('y'), $this->doc->get("font_size"), number_format($amount, 2, ",", "."));

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


                $tekst = $this->doc->addTextWrap($this->apointX["tekst"], $this->doc->get('y'), $this->apointX["tekst_width_small"], $this->doc->get("font_size"), $tekst);
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
                foreach ($desc_line as $line) {
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
                            $line = $this->doc->addTextWrap($this->apointX["tekst"], $this->doc->get('y') + 1, $this->apointX["tekst_width"], $this->doc->get("font_size"), $line); // Ups Ups, hvor kommer '+ 1' fra - jo ser du, ellers kappes det nederste af teksten!
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
                $this->doc->addText($this->apointX["tekst"], $this->doc->get('y'), $this->doc->get("font_size"), "<b>25% moms af ".number_format($total, 2, ",", ".")."</b>");
                $this->doc->addText($this->apointX["beloeb"] - $this->doc->getTextWidth($this->doc->get("font_size"), "<b>".number_format($total * 0.25, 2, ",", ".")."</b>"), $this->doc->get('y'), $this->doc->get("font_size"), "<b>".number_format($total * 0.25, 2, ",", ".")."</b>");
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
        $this->addTotalAmount($debtor);
    }

    private function addTotalAmount($debtor)
    {
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
            $this->doc->addText($this->apointX["enhed"], $this->doc->get('y'), $this->doc->get("font_size"), "I alt:");
            $this->doc->addText($this->apointX["beloeb"] - $this->doc->getTextWidth($this->doc->get("font_size"), number_format($total, 2, ",", ".")), $this->doc->get('y'), $this->doc->get("font_size"), number_format($total, 2, ",", "."));
            $this->doc->setY('-'.$this->doc->get("font_padding_bottom"));

            $total_text = "Total afrundet " . $currency_iso_code . ":";
        } else {
            $total_text = "Total " . $currency_iso_code . ":";
        }

        if ($this->doc->get('y') < $this->doc->get("margin_bottom") + $this->doc->get("font_spacing") * 2) {
            $this->doc->nextPage(true);
        }

        $debtor_total = "<b>" . number_format($debtor_total, 2, ",", ".")."</b>";

        $this->doc->setY('-'.($this->doc->get("font_size") + $this->doc->get("font_padding_top")));
        $this->doc->addText($this->apointX["enhed"], $this->doc->get('y'), $this->doc->get("font_size"), "<b>".$total_text."</b>");
        $this->doc->addText($this->apointX["beloeb"] - $this->doc->getTextWidth($this->doc->get("font_size"), $debtor_total), $this->doc->get('y'), $this->doc->get("font_size"), $debtor_total);
        $this->doc->setY('-'.$this->doc->get("font_padding_bottom"));
        $this->doc->line($this->apointX["enhed"], $this->doc->get('y'), $this->doc->get('right_margin_position'), $this->doc->get('y'));
    }
}
