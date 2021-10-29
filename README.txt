Punchout_Cookie module allows Magento2 to work in iframe by setting SameSite=Note to an each cookie.


Installation:

composer require punchout-catalogs/magento2-cookie dev-master
OR
composer require punchout-catalogs/magento2-cookie release-version

Example:
composer require punchout-catalogs/magento2-cookie 0.12.0


Known Issues:

1)
There are issues with Safari - as it failed SameSite=None implementation for older Safari versions.
It is possible to avoid the issue by Disabling the "Prevent Cross-Site Tracking" setting.
