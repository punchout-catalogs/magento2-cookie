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
        $params = session_get_cookie_params();
        if (empty($params)) {
            return $this;
        }

        $params['path'] = empty($params['path']) ? '/' : $params['path'];
        if (!$this->canSendCookieSameSiteNone($params['path'])) {
            return $this;
        }

        $params['path'] = $this->attachSameSiteParam($params['path']);

        session_set_cookie_params(
            $params['lifetime'],
            $params['path'],
            $params['domain'],
            !empty($params['secure']),
            !empty($params['httponly'])
        );

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
        
        if (null === $metadata || !$this->canSendCookieSameSiteNone($origPath)) {
            return $metadata;
        }
        
        //Update Path
        $metadata['path'] = $this->attachSameSiteParam($origPath);

        //Update Secure
        if ($this->isForceSecureEnabled()) {
            $metadata[\Magento\Framework\Stdlib\Cookie\CookieMetadata::KEY_SECURE] = true;
        }
        
        return $metadata;
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function canSendCookieSameSiteNone($path)
    {
        if (!$this->isFrontend() || !$this->isSameSiteNoneEnabled()) {
            return false;
        }

        if ($path && strpos(strtolower($path), 'samesite=') !== false) {
            return false;
        }
        
        $agent = $this->headerService->getHttpUserAgent();

        return !($agent && $this->isBlackListed($agent));
    }
    
    public function attachSameSiteParam($path = '/')
    {
        $path .= '; SameSite=None';
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
    
    protected function isSameSiteNoneEnabled()
    {
        return (bool)$this->getConfigValue(static::PATH_USE_SAMESITE_NONE, $this->getStoreId());
    }
    
    protected function isForceSecureEnabled()
    {
        return (bool)$this->getConfigValue(static::PATH_FORCE_SECURE, $this->getStoreId());
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