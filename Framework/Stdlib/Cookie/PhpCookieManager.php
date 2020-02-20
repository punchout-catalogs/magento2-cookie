<?php

namespace Punchout\Cookie\Framework\Stdlib\Cookie;

class PhpCookieManager
    extends \Magento\Framework\Stdlib\Cookie\PhpCookieManager
{
    protected function setCookie($name, $value, array $metadataArray)
    {
        $metadataArray = $this->getCookieHelper()->updateMetadata($metadataArray);

        parent::setCookie($name, $value, $metadataArray);
    }
    
    /**
     * @return \Punchout\Cookie\Helper\Cookie
     */
    protected function getCookieHelper()
    {
        return $this->getOm()->get(\Punchout\Cookie\Helper\Cookie::class);
    }

    /**
     * @return \Magento\Framework\App\ObjectManager
     */
    protected function getOm()
    {
        return \Magento\Framework\App\ObjectManager::getInstance();
    }
}
