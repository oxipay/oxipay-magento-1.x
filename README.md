# ezipay-magento-1.x [![Build status](https://ci.appveyor.com/api/projects/status/t71e6r0lvsfriwm0/branch/master?svg=true)](https://ci.appveyor.com/project/ezipay/ezipay-magento-1-x/branch/master)

## Installation

To deploy the plugin, clone this repo, and copy the following plugin files and folders into the corresponding folder under the Magento root directory.

```bash
/app/code/community/Ezipay/
/app/design/frontend/base/default/template/ezipayments/
/app/design/adminhtml/base/default/template/ezipayments/
/app/etc/modules/Ezipay_Ezipayments.xml

/skin/frontend/base/default/images/Ezipay/
/skin/adminhtml/base/default/images/Ezipay/
```

Once copied - you should be able to see the ezipay plugin loaded in magento (note this may require a cache flush/site reload)

Please find more details from 
http://docs.ezipay.com.au/platforms/magento_1/  (for Australia)
http://docs.ezipay.co.nz/platforms/magento_1/  (for New Zealand)

## Varnish cache exclusions

A rule must be added to varnish configuration for any magento installation running behind a varnish backend. (Or any other proxy cache) to invalidate any payment controller action.

Must exclude: `.*ezipayments.`* from all caching.
