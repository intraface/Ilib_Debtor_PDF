<?php

use Intraface\DebtorPdf;
use Intraface\DebtorVisitorPdf;

class DebtorPdfTest extends \PHPUnit_Framework_TestCase
{
    protected $path_to_debtor;

    public function setup()
    {
        $this->path_to_debtor = TEST_PATH_TEMP . '/' . $this->getName() . '.pdf';
        $this->path_to_expected_debtor = dirname(__FILE__) .'/expected/' . $this->getName() . '.pdf';
        $this->tearDown();
    }

    public function tearDown()
    {
        if (file_exists($this->path_to_debtor)) {
            unlink($this->path_to_debtor);
        }
    }

    protected function createPdf()
    {
        return new DebtorVisitorPdf(new Stub_Translation);
    }

    protected function createDebtor($payment_method = 2)
    {
        $debtor = new FakeDebtor($payment_method);
        $debtor->contact = new FakeContact;
        $debtor->contact->address = new Stub_Address;
        $debtor->contact_person = new FakeContactPerson;
        return $debtor;
    }

    protected function createDebtorLongProductText()
    {
        $debtor = new FakeDebtorLongProductText();
        $debtor->contact = new FakeContact;
        $debtor->contact->address = new Stub_Address;
        $debtor->contact_person = new FakeContactPerson;
        return $debtor;
    }

    protected function createDebtorWithManyProducts()
    {
        $debtor = new FakeDebtorManyProducts();
        $debtor->contact = new FakeContact;
        $debtor->contact->address = new Stub_Address;
        $debtor->contact_person = new FakeContactPerson;
        return $debtor;
    }

    protected function createDebtorWithLongMessage()
    {
        $debtor = new FakeDebtorLongMessage();
        $debtor->contact = new FakeContact;
        $debtor->contact->address = new Stub_Address;
        $debtor->contact_person = new FakeContactPerson;
        return $debtor;
    }

    protected function createDebtorWithNoAmount()
    {
        $debtor = new FakeDebtorNoAmount();
        $debtor->contact = new FakeContact;
        $debtor->contact->address = new Stub_Address;
        $debtor->contact_person = new FakeContactPerson;
        return $debtor;
    }

    protected function assertGeneratedPdfHasCorrectLength($pdf, $length)
    {
        // As we cannot be certain that function_exists('gzcompress'), we turn off compression
        $turn_off_compression = true;
        // this is for comparing the files.
        $pdf->output('file', $this->path_to_debtor, $turn_off_compression);
        $expected = file_get_contents($this->path_to_expected_debtor, 1);
        $actual = file_get_contents($this->path_to_debtor);
        $this->assertEquals(strlen($expected), strlen($actual));

        // This is for simply comparing the string.
        /*
        $content = $pdf->output('string', '', $turn_off_compression);
        $this->assertEquals($length, strlen($content));
        */
    }

    /////////////////////////////////////////////////////////////////////////

    public function testConstruct()
    {
        $pdf = $this->createPdf();
        $this->assertTrue(is_object($pdf));
    }

    public function testVisit()
    {
        $pdf = $this->createPdf();
        $debtor = $this->createDebtor();
        $pdf->visit($debtor);
        $this->assertGeneratedPdfHasCorrectLength($pdf, 10811);
    }

    public function testVisitWithPayment()
    {
        $pdf = $this->createPdf();
        $debtor = $this->createDebtor();
        $debtor->values['payment_total'] = 2125;
        $pdf->visit($debtor);
        $this->assertGeneratedPdfHasCorrectLength($pdf, 9915);
    }

    public function testVisitWithLongProductText()
    {
        $pdf = $this->createPdf();
        $debtor = $this->createDebtorLongProductText();
        $pdf->visit($debtor);
        $this->assertGeneratedPdfHasCorrectLength($pdf, 11565);
    }

    public function testVisitWithBankTransferPayment()
    {
        $pdf = $this->createPdf();
        $debtor = $this->createDebtor(1);
        $pdf->visit($debtor);
        $this->assertGeneratedPdfHasCorrectLength($pdf, 10612);
    }

    public function testDebtorWithManyProducts()
    {
        $pdf = $this->createPdf();
        $debtor = $this->createDebtorWithManyProducts();
        $pdf->visit($debtor);
        $this->assertGeneratedPdfHasCorrectLength($pdf, 16980);
    }

    public function testDebtorNoAmount()
    {
        $pdf = $this->createPdf();
        $debtor = $this->createDebtorWithNoAmount();
        $pdf->visit($debtor);
        $this->assertGeneratedPdfHasCorrectLength($pdf, 8725);
    }

    public function testDebtorWithLongText()
    {
        $pdf = $this->createPdf();
        $debtor = $this->createDebtorWithLongMessage();
        $pdf->visit($debtor);
        $this->assertGeneratedPdfHasCorrectLength($pdf, 22980);
    }

    public function testVisitWithOnlinePayment()
    {
        $this->markTestSkipped('Not implemented properly yet');
        /*
        $pdf = $this->createPdf();
        $debtor = $this->createDebtor();
        $debtor->values['payment_online'] = 2125;
        $pdf->visit($debtor);
        $pdf->output('file', $this->path_to_debtor);
        $expected = file_get_contents($this->path_to_expected_debtor, 1);
        $actual = file_get_contents($this->path_to_debtor);

        $this->assertEquals(strlen($expected), strlen($actual));
        */
    }

}
