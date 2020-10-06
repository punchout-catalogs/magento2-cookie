<?php

namespace Punchout\Cookie\Helper;

/**
 * Class Cookie
 */
class Cookie
{
    const PATH_USE_SAMESITE_NONE  = 'web/cookie/cookie_use_same_site_none';
    const PATH_FORCE_SECURE  = 'web/cookie/cookie_force_secure';
    const PATH_SAMESITE_NONE_BLACKLIST  = 'web/cookie/cookie_same_site_blacklist';
    
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

        if ($this->isPhpCookieOptionsSupported()) {
            $params['samesite'] = 'None';

            //an attempt to guess future implementation in M2
            $this->getSessionConfig()->setSamesite('None');
            $this->getSessionConfig()->setSameSite('None');
        } else {
            $params['path'] = empty($params['path']) ? '/' : $params['path'];
            $params['path'] = $this->attachSameSiteParam($params['path']);

            $path = $this->getSessionConfig()->getCookiePath();
            $path = $this->attachSameSiteParam($path ? $path : '/');

            //CE 2.1 fix
            $this->getSessionConfig()->setCookiePath($path);
        }

        //Update Secure
        if ($this->isForceSecureEnabled()) {
            $params['secure'] = true;

            //CE 2.1 fix
            $this->getSessionConfig()->setCookieSecure(true);
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
        if (!$this->isFrontend() || !$this->isSameSiteNoneEnabled()) {
            return false;
        }
        
        $agent = $this->headerService->getHttpUserAgent();

        return !($agent && $this->isBlackListed($agent));
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
     * @return \Magento\Framework\Session\Config\ConfigInterface
     */
    protected function getSessionConfig()
    {
        return $this->objectManager->get(\Magento\Framework\Session\Config\ConfigInterface::class);
    }
    
    protected function isSameSiteNoneEnabled()
    {
        return (bool)$this->getConfigValue(static::PATH_USE_SAMESITE_NONE, $this->getStoreId());
    }
    
    protected function isForceSecureEnabled()
    {
        return (bool)$this->getConfigValue(static::PATH_FORCE_SECURE, $this->getStoreId());
    }
    
    public function isPhpCookieOptionsSupported()
    {
        return version_compare(PHP_VERSION, "7.3.0", ">=");
    }
    
    protected function isBlackListed($input)
    {
        $patterns = (string)$this->getConfigValue(static::PATH_SAMESITE_NONE_BLACKLIST, $this->getStoreId());

        $patterns = explode("\n", $patterns);
        $patterns = array_map("trim", $patterns);
        $patterns = array_filter($patterns);
        
        if (empty($patterns)) {
            return false;
        }
        
        foreach ($patterns as $pattern) {
            $pattern = explode("[AND]", $pattern);
            $pattern = array_map("trim", $pattern);
            $pattern = array_filter($pattern);
            
            $allMatched = true;
            foreach ($pattern as $_pattern) {
                if (!preg_match("~" . $_pattern . "~", $input)) {
                    $allMatched = false;
                    break;
                }
            }
            
            if ($allMatched) {
                return true;
            }
        }
        
        return false;
    }
}
