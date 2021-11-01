<?php

namespace Punchout\Cookie\Plugin\Framework\Session;

class SessionManager
{
    /**
     * @var \Punchout\Cookie\Helper\Cookie
     */
    protected $cookieHelper;

    /**
     * SessionManager constructor.
     *
     * @param \Punchout\Cookie\Helper\Cookie $cookieHelper
     */
    public function __construct(\Punchout\Cookie\Helper\Cookie $cookieHelper)
    {
        $this->cookieHelper = $cookieHelper;
    }

    /**
     * @param \Magento\Framework\Session\SessionManager $subject
     *
     * @return array
     */
    public function beforeStart($subject)
    {
        if (!$subject->isSessionExists()) {
            $this->cookieHelper->updateCookieParams();
        }
        return [];
    }

    /**
     * @param \Magento\Framework\Session\SessionManager $subject
     *
     * @return array
     */
    public function beforeRegenerateId($subject)
    {
        if (!$subject->isSessionExists()) {
            $this->cookieHelper->updateCookieParams();
        }
        return [];
    }

    /**
     * @param \Magento\Framework\Session\SessionManager $subject
     * @param \Closure $proceed
     * @param string $path
     *
     * @return bool
     */
    public function aroundIsValidForPath($subject, \Closure $proceed, $path)
    {
        if (strpos($subject->getCookiePath(), 'SameSite=None') === false) {
            return $proceed($path);
        }
        return $this->_isValidForPath($path, $subject->getCookiePath());
    }

    /**
     * Check if session is valid for given path
     *
     * @param string $path
     * @param $cookiePath
     * @return bool
     */
    public function _isValidForPath($path, $cookiePath)
    {
        $cookiePath = str_replace($cookiePath, 'SameSite=None', '');//
        $cookiePath = trim($cookiePath);//
        $cookiePath = trim($cookiePath, ';');//
        $cookiePath = trim($cookiePath, '/') . '/';//

        //$cookiePath = trim($this->getCookiePath(), '/') . '/';
        if ($cookiePath == '/') {
            return true;
        }

        $urlPath = trim($path, '/') . '/';
        return strpos($urlPath, $cookiePath) === 0;
    }
}
