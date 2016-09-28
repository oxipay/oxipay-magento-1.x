<?php
use PHPUnit\Framework\TestCase;
require dirname(__FILE__).'\..\app\Mage.php';
require dirname(__FILE__).'\..\app\code\core\Mage\Core\Helper\Abstract.php';
require dirname(__FILE__).'\..\app\code\community\Oxipay\Oxipayments\Helper\Data.php';
class_alias('Oxipay_Oxipayments_Helper_Data', 'systemUnderTest');

class Oxipay_Oxipayments_Helper_DataTest extends TestCase
{
    private $apiKey = 'May the pharce be with you.';
    private $expectedSignature = '9No9TnAif2jqR2Rz4x3AdevlTEbc07loUMhaFYg3eI=';

    public function test_generateSignature_correctlyGeneratesSignature()
    {
        $query = array(
            'this' => 'that',
            'then' => 'now'
        );
        $actual = systemUnderTest::generateSignature($query, $this->apiKey);

        $this->assertEquals($this->expectedSignature, $actual);
    }

    public function test_isValidSignature_correctlyChecksSignature()
    {
        $query = array(
            'this' => 'that',
            'then' => 'now',
            'x_signature' => $this->expectedSignature
        );

        $actual = systemUnderTest::isValidSignature($query, $this->apiKey);
        $this->assertTrue($actual);
    }

    public function test_getCheckoutUrl_returnsCorrectResult() {
        //echo Mage::getStoreConfig('payment/oxipayments/gateway_base_url');
        //$actual = systemUnderTest::getCheckoutUrl();
        //$this->assertEquals("asdasd", $actual);
    }
}
