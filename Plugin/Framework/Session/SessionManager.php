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
     * @param $subject
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
}
