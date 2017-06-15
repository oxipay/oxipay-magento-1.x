<?php

use Magento\Directory\Model\Config\Source\Country;

namespace Oxipay\OxipayPaymentGateway\Model\Config\Source;

class RestrictedCountry extends Mage_Payment_Model_Method_Abstract {

    public function __construct(\Magento\Directory\Model\ResourceModel\Country\Collection $countryCollection)
    {
        $countryCollection->addCountryIdFilter(array('AU', 'NZ'));

        parent::__construct($countryCollection);
    }
}

 ?>
