<?php

namespace Wow\Ipay88\Block;

class OrderCreate extends \Magento\Framework\View\Element\Template
{
    /**
     * Construct
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    )
    {
        parent::__construct($context, $data);
    }

    public function getMerchantCode()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->create('Ipay88\Ipay88\Helper\Data');
        $merchantCode = $helper->getConfig('ipay88_app_merchant_code');
        return $merchantCode;
    }

    public function getFormAction()
    {
        // return '/wowipay/repush/action';
        return 'https://payment.ipay88.com.my/ePayment/entry.asp';
    }
}
