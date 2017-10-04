# oxipay-magento-1.x [![Build status](https://ci.appveyor.com/api/projects/status/t71e6r0lvsfriwm0/branch/master?svg=true)](https://ci.appveyor.com/project/oxipay/oxipay-magento-1-x/branch/master)

## Installation

To deploy the plugin, clone this repo, and copy the following plugin files and folders into the corresponding folder on the `app` folder on the Magento root directory.

```bash
/app/code/community/Oxipay
/app/design/frontend/base/default/template/oxipayments
/app/design/adminhtml/base/default/template/oxipayments
/app/etc/modules/Oxipay_Oxipayments.xml
```

Once copied - you should be able to see the oxipay plugin loaded in magento (note this may require a cache flush/site reload)

Please find more details from 
http://docs.oxipay.com.au/platforms/magento_1/  (for Australia)
http://docs.oxipay.com.au/platforms/magento_1/  (for New Zealand)

## Varnish cache exclusions

A rule must be added to varnish configuration for any magento installation running behind a varnish backend. (Or any other proxy cache) to invalidate any payment controller action.

Must exclude: `.*oxipayments.`* from all caching.
