<?php
error_reporting(E_ALL);
if (!file_exists($GLOBALS['test_path_temp'])) {
    mkdir($GLOBALS['test_path_temp']);
}
define('TEST_PATH_TEMP', $GLOBALS['test_path_temp']);
require_once 'Document/Cpdf.php';
require_once dirname(__FILE__) . '/../src/DebtorVisitorPdf.php';
require_once dirname(__FILE__) .'/stubs/Debtor.php';
require_once dirname(__FILE__) .'/stubs/DebtorLongProductText.php';
require_once dirname(__FILE__) .'/stubs/DebtorManyProducts.php';
require_once dirname(__FILE__) .'/stubs/DebtorLongMessage.php';
require_once dirname(__FILE__) .'/stubs/DebtorNoAmount.php';
require_once dirname(__FILE__) .'/stubs/Contact.php';
require_once dirname(__FILE__) .'/stubs/ContactPerson.php';
require_once dirname(__FILE__) .'/stubs/Translation.php';
require_once dirname(__FILE__) .'/stubs/Address.php';
require_once dirname(__FILE__) .'/stubs/VariableFloat.php';
require_once 'Ilib/Variable.php';
