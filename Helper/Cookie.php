<?php

namespace Punchout\Cookie\Helper;

/**
 * Class Cookie
 */
class Cookie
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\HTTP\Header
     */
    protected $headerService;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\HTTP\Header $headerService,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->objectManager = $objectManager;
        $this->headerService = $headerService;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    public function updateCookieParams()
    {
        if (!$this->canSendCookieSameSiteNone()) {
            return $this;
        }

        $params = session_get_cookie_params();
        if (empty($params)) {
            return $this;
        }

        //Update Secure
        if ($this->isForceSecureEnabled()) {
            $params['secure'] = true;

            //CE 2.1 fix
            $this->getSessionConfig()->setCookieSecure(true);
        }

        if ($this->isPhpCookieOptionsSupported()) {
            $params['samesite'] = 'None';

            //Support for Magento version 2.4.3
            if (method_exists($this->getSessionConfig(), 'setCookieSameSite')) {
                $this->getSessionConfig()->setCookieSameSite('None');
            }
        } else {
            $params['path'] = empty($params['path']) ? '/' : $params['path'];
            $params['path'] = $this->attachSameSiteParam($params['path']);

            $path = $this->getSessionConfig()->getCookiePath();
            $path = $this->attachSameSiteParam($path ? $path : '/');

            //CE 2.1 fix
            $this->getSessionConfig()->setCookiePath($path);
        }

        return $this->_updateCookieParams($params);
    }

    protected function _updateCookieParams(array $params)
    {
        try {
            if ($this->isPhpCookieOptionsSupported()) {
                session_set_cookie_params($params);
            } else {
                session_set_cookie_params(
                    $params['lifetime'],
                    $params['path'],
                    $params['domain'],
                    !empty($params['secure']),
                    !empty($params['httponly'])
                );
            }

            //Support for Magento version 2.4.3
            if (method_exists($this->getSessionConfig(), 'setCookieSameSite')) {
                $this->getSessionConfig()->setOption('session.cookie_samesite', $params['samesite']);
            }
        } catch (\Exception $e) {
            //silently catch an error
        }

        return $this;
    }

    /**
     * Hot-fix for Chrome + other new versions of diff. browsers
     *
     * @param array $metadata
     * @return array|null
     */
    public function updateMetadata(array $metadata = array())
    {
        $origPath = !empty($metadata[\Magento\Framework\Stdlib\Cookie\CookieMetadata::KEY_PATH])
            ? $metadata[\Magento\Framework\Stdlib\Cookie\CookieMetadata::KEY_PATH]
            : '/';

        if (null === $metadata || !$this->canSendCookieSameSiteNone()) {
            return $metadata;
        }

        //Update Path
        if ($this->isPhpCookieOptionsSupported()) {
            $metadata['samesite'] = 'None';
        } else {
            $metadata['path'] = $this->attachSameSiteParam($origPath);
        }

        //Update Secure
        if ($this->isForceSecureEnabled()) {
            $metadata[\Magento\Framework\Stdlib\Cookie\CookieMetadata::KEY_SECURE] = true;
        }

        return $metadata;
    }

    /**
     * @return bool
     */
    public function canSendCookieSameSiteNone()
    {
        return ($this->isFrontend() && $this->isSameSiteNoneEnabled());
    }

    public function attachSameSiteParam($path = '/')
    {
        if (strpos($path, 'SameSite') === false) {
            $path.= '; SameSite=None';
        }
        return $path;
    }

    protected function getConfigValue($path, $storeId = null)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    protected function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    protected function isFrontend()
    {
        try {
            return $this->getAppState()->getAreaCode() == \Magento\Framework\App\Area::AREA_FRONTEND;
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * @return \Magento\Framework\App\State
     */
    protected function getAppState()
    {
        return $this->objectManager->get(\Magento\Framework\App\State::class);
    }

    /**
     * @return \Magento\Framework\Session\Config
     */
    protected function getSessionConfig()
    {
        return $this->objectManager->get(\Magento\Framework\Session\Config\ConfigInterface::class);
    }

    protected function isSameSiteNoneEnabled()
    {
        return true;
    }

    protected function isForceSecureEnabled()
    {
        return true;
    }

    public function isPhpCookieOptionsSupported()
    {
        return version_compare(PHP_VERSION, "7.3.0", ">=");
    }
}
