<?php
use PHPUnit\Framework\TestCase;
require dirname(__FILE__).'\..\app\Mage.php';
require dirname(__FILE__).'\..\app\code\core\Mage\Core\Helper\Abstract.php';
require dirname(__FILE__).'\..\app\code\community\Oxipay\Oxipayments\Helper\Crypto.php';

class_alias('Oxipay_Oxipayments_Helper_Crypto', 'systemUnderTest');

class Oxipay_Oxipayments_Helper_Crypto_Test extends TestCase
{
    private $apiKey = 'May the phorce be with you.';
    private $expectedSignature = 'u3CfH0b/3qybMHK9h52zUdtvhRXKCEQaribfoVPYaAA=';

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
}
