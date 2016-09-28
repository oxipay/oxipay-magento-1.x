<?php
use PHPUnit\Framework\TestCase;
require dirname(__FILE__).'\..\app\code\core\Mage\Core\Helper\Abstract.php';
require dirname(__FILE__).'\..\app\code\community\Oxipay\Oxipayments\Helper\Data.php';

class Oxipay_Oxipayments_Helper_DataTest extends TestCase
{
    private $apiKey = 'May the pharce be with you.';
    private $expectedSignature = '9No9TnAif2jqR2Rz4x3AdevlTEbc07loUMhaFYg3eI=';

    public function testGenerateSignature()
    {
        $query = array(
            'this' => 'that',
            'then' => 'now'
        );
        $actual = Oxipay_Oxipayments_Helper_Data::generateSignature($query, $this->apiKey);

        $this->assertEquals($this->expectedSignature, $actual);
    }

    public function testIsValidSignature()
    {
        $query = array(
            'this' => 'that',
            'then' => 'now',
            'x_signature' => $this->expectedSignature
        );

        $actual = Oxipay_Oxipayments_Helper_Data::isValidSignature($query, $this->apiKey);
        $this->assertTrue($actual);
    }
}
