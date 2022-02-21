Punchout_Cookie module allows Magento2 untill `2.4.3` version to work in iframe by setting `SameSite=Note` parameter to cookies.


Installation:

composer require punchout-catalogs/magento2-cookie dev-master
OR
composer require punchout-catalogs/magento2-cookie release-version

Example:
composer require punchout-catalogs/magento2-cookie 0.15.0


Known Issues:

1)
There are issues with Safari - as it failed SameSite=None implementation for older Safari versions.
It is possible to avoid the issue by Disabling the "Prevent Cross-Site Tracking" setting.
