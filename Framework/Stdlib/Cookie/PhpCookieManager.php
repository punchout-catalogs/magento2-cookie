<?php

namespace Punchout\Cookie\Framework\Stdlib\Cookie;

use Magento\Framework\Stdlib\Cookie\CookieMetadata;

class PhpCookieManager
    extends \Magento\Framework\Stdlib\Cookie\PhpCookieManager
{
    protected function setCookie($name, $value, array $metadataArray)
    {
        $metadataArray = $this->getCookieHelper()->updateMetadata($metadataArray);

        if (!$this->getCookieHelper()->isPhpCookieOptionsSupported()) {
            return parent::setCookie($name, $value, $metadataArray);
        }

        $options = $this->toCookieOptions($metadataArray);

        $phpSetcookieSuccess = setcookie($name, $value, $options);

        if (!$phpSetcookieSuccess) {
            $params['name'] = $name;
            if ($value == '') {
                throw new FailureToSendException(
                    new Phrase('The cookie with "%name" cookieName couldn\'t be deleted.', $params)
                );
            } else {
                throw new FailureToSendException(
                    new Phrase('The cookie with "%name" cookieName couldn\'t be sent. Please try again later.', $params)
                );
            }
        }

        return $this;
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

    protected function toCookieOptions(array $metadataArray)
    {
        $options = array (
            'expires' => $this->computeExpirationTime($metadataArray),
            'path' => $this->extractValue(CookieMetadata::KEY_PATH, $metadataArray, ''),
            'domain' => $this->extractValue(CookieMetadata::KEY_DOMAIN, $metadataArray, ''),
            'secure' => $this->extractValue(CookieMetadata::KEY_SECURE, $metadataArray, false),
            'httponly' => $this->extractValue(CookieMetadata::KEY_HTTP_ONLY, $metadataArray, false),
        );

        $samesite = $this->extractValue('samesite', $metadataArray, '');
        if ($samesite) {
            $options['samesite'] = $samesite;
        }

        return $options;
    }

    /**
     * Make protected, which is private in the parent class
     *
     * Determines the expiration time of a cookie.
     *
     * @param array $metadataArray
     * @return int in seconds since the Unix epoch.
     */
    protected function computeExpirationTime(array $metadataArray)
    {
        if (isset($metadataArray[PhpCookieManager::KEY_EXPIRE_TIME])
            && $metadataArray[PhpCookieManager::KEY_EXPIRE_TIME] < time()
        ) {
            $expireTime = $metadataArray[PhpCookieManager::KEY_EXPIRE_TIME];
        } else {
            if (isset($metadataArray[CookieMetadata::KEY_DURATION])) {
                $expireTime = $metadataArray[CookieMetadata::KEY_DURATION] + time();
            } else {
                $expireTime = PhpCookieManager::EXPIRE_AT_END_OF_SESSION_TIME;
            }
        }

        return $expireTime;
    }

    /**
     * Make protected, which is private in the parent class
     *
     * Determines the value to be used as a $parameter.
     * If $metadataArray[$parameter] is not set, returns the $defaultValue.
     *
     * @param string $parameter
     * @param array $metadataArray
     * @param string|boolean|int|null $defaultValue
     * @return string|boolean|int|null
     */
    protected function extractValue($parameter, array $metadataArray, $defaultValue)
    {
        if (array_key_exists($parameter, $metadataArray)) {
            $v = $metadataArray[$parameter];
        } else {
            $v = $defaultValue;
        }

        //----------------------------------------------------------------------//
        //hot-fix for: Warning: Cookie paths cannot contain any of the following ',; \\t\\r\\n\\013\\014'
        if ($parameter === CookieMetadata::KEY_PATH) {
            $v = $this->sanitizeValue($v);
        }
        //----------------------------------------------------------------------//

        return  $v;
    }

    protected $fixSymbols = array(
        '014', '013', 't', 'r', 'n'
    );

    /**
     * Hot-fix for `Cookie paths cannot contain any of the following ',; \\t\\r\\n\\013\\014'`
     * @param null $v
     *
     * @return mixed|string|null
     */
    protected function sanitizeValue($v = null)
    {
        if (!is_string($v)) {
            return $v;
        }

        foreach ($this->fixSymbols as $s) {
            $v = str_replace(['\\\\' . $s, '\\' . $s], '', $v);
        }

        return trim($v);
    }
}
