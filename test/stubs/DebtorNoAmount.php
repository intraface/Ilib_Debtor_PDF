<?php
class FakeDebtorNoAmount
{
    function __construct($payment_method = 2)
    {
        $this->values = array(
            'id' => 1,
            'locked' => 0,
            'type' => 'invoice',
            'dk_this_date' => '2007-10-10',
            'due_date' => '2007-10-10',
            'dk_due_date' => '2007-10-10',
            'intranet_address_id' => 1,
            'number' => 1,
            'message' => '',
            'round_off' => '',
            'total' => 0,
            'payment_total' => 0,
            'payment_online' => 0,
            'girocode' => '',
            'payment_method' => $payment_method);
    }

    function getItems()
    {
        return array();
    }

    function get($key)
    {
        return $this->values[$key];
    }

    function getIntranetAddress()
    {
        return new Stub_Address();
    }

    function getPaymentInformation()
    {
        return array('bank_name' => 'SparNord', 'bank_reg_number' => '1243', 'bank_account_number' => '12312345678', 'giro_account_number' => '112321321');
    }

    function getContactInformation()
    {
        return array('email' => 'test@intraface.dk', 'contact_name' => 'Lars Olesen');
    }

    function getInvoiceText()
    {
        return 'Ja, det kan du tro, at der er en masse at fortælle.';
    }

    function getCurrency()
    {
        return false;
    }
}
