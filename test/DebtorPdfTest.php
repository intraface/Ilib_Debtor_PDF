<?php
class DebtorPdfTest extends PHPUnit_Framework_TestCase
{
    protected $path_to_debtor;
    
    function setup()
    {
        $this->path_to_debtor = TEST_PATH_TEMP . '/debtor.pdf';
        $this->tearDown();
    }

    function tearDown()
    {
        if (file_exists($this->path_to_debtor)) {
            unlink($this->path_to_debtor);
        }
    }

    function createPdf()
    {
        return new DebtorVisitorPdf(new Stub_Translation);
    }

    function createDebtor()
    {
        $debtor = new FakeDebtor();
        $debtor->contact = new FakeContact;
        $debtor->contact->address = new Stub_Address;
        $debtor->contact_person = new FakeContactPerson;
        return $debtor;
    }

    function createDebtorLongProductText()
    {
        $debtor = new FakeDebtorLongProductText();
        $debtor->contact = new FakeContact;
        $debtor->contact->address = new Stub_Address;
        $debtor->contact_person = new FakeContactPerson;
        return $debtor;
    }
    
    /////////////////////////////////////////////////////////////////////////

    function testConstruct()
    {
        $pdf = $this->createPdf();
        $this->assertTrue(is_object($pdf));
    }

    function testVisit()
    {
        $pdf = $this->createPdf();
        $debtor = $this->createDebtor();
        $pdf->visit($debtor);
        $pdf->output('file', $this->path_to_debtor);
        $expected = file_get_contents(dirname(__FILE__) .'/expected_debtor.pdf', 1);
        $actual = file_get_contents($this->path_to_debtor);

        $this->assertEquals(strlen($expected), strlen($actual));
    }

    function testVisitWithPayment()
    {
        $pdf = $this->createPdf();
        $debtor = $this->createDebtor();
        $debtor->values['payment_total'] = 2125;
        $pdf->visit($debtor);
        $pdf->output('file', $this->path_to_debtor);
        $expected = file_get_contents(dirname(__FILE__) .'/expected_debtor_with_payment.pdf', 1);
        $actual = file_get_contents($this->path_to_debtor);

        $this->assertEquals(strlen($expected), strlen($actual));
    }

    function testVisitWithLongProductText()
    {
        $pdf = $this->createPdf();
        $debtor = $this->createDebtorLongProductText();
        $pdf->visit($debtor);
        $pdf->output('file', $this->path_to_debtor);
        $expected = file_get_contents(dirname(__FILE__) .'/expected_debtor_with_long_text.pdf', 1);
        $actual = file_get_contents($this->path_to_debtor);

        $this->assertEquals(strlen($expected), strlen($actual));
    }

    /*
    function testVisitWithOnlinePayment()
    {
        $pdf = $this->createPdf();
        $debtor = $this->createDebtor();
        $debtor->values['payment_online'] = 2125;
        $pdf->visit($debtor);
        $pdf->output('file', $this->path_to_debtor);
        $expected = file_get_contents('tests/unit/debtor/expected_debtor_with_payment.pdf', 1);
        $actual = file_get_contents($this->path_to_debtor);

        $this->assertEquals(strlen($expected), strlen($actual));
    }
    */
}

