<?php

namespace Punchout\Cookie\Plugin\Framework\Data\Form\FormKey;

class Validator
{
    /**
     * @param \Magento\Framework\Data\Form\FormKey\Validator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return bool
     */
    public function aroundValidate($subject, \Closure $proceed, $request)
    {
        if ($this->shouldIgnoreFormKey()) {
            return true;
        }
        return $proceed($request);
    }

    /**
     * @return bool
     */
    public function shouldIgnoreFormKey()
    {
        return $this->getCustomerSession()->getIsPunchoutSession();
    }

    /**
     * @return \Magento\Customer\Model\Session
     */
    protected function getCustomerSession()
    {
        return $this->getOm()->get(\Magento\Customer\Model\Session::class);
    }
    
    /**
     * @return \Magento\Framework\App\ObjectManager
     */
    protected function getOm()
    {
        return \Magento\Framework\App\ObjectManager::getInstance();
    }
}
