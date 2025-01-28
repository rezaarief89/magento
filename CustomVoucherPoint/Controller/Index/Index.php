<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fef\CustomVoucherPoint\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Index extends \Magento\Checkout\Controller\Index\Index implements HttpGetActionInterface
{
    /**
     * Checkout page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        // $logger->info("co index");

        $this->calculcateOrder();

        /** @var \Magento\Checkout\Helper\Data $checkoutHelper */
        $checkoutHelper = $this->_objectManager->get(\Magento\Checkout\Helper\Data::class);
        if (!$checkoutHelper->canOnepageCheckout()) {
            $this->messageManager->addErrorMessage(__('One-page checkout is turned off.'));
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        $quote = $this->getOnepage()->getQuote();
        if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        if (!$this->_customerSession->isLoggedIn() && !$checkoutHelper->isAllowedGuestCheckout($quote)) {
            $this->messageManager->addErrorMessage(__('Guest checkout is disabled.'));
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        // generate session ID only if connection is unsecure according to issues in session_regenerate_id function.
        // @see http://php.net/manual/en/function.session-regenerate-id.php
        if (!$this->isSecureRequest()) {
            $this->_customerSession->regenerateId();
        }
        $this->_objectManager->get(\Magento\Checkout\Model\Session::class)->setCartWasUpdated(false);
        $this->getOnepage()->initCheckout();
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Checkout'));
        return $resultPage;
    }

    /**
     * Checks if current request uses SSL and referer also is secure.
     *
     * @return bool
     */
    private function isSecureRequest(): bool
    {
        $request = $this->getRequest();

        $referrer = $request->getHeader('referer');
        $secure = false;

        if ($referrer) {
            $scheme = parse_url($referrer, PHP_URL_SCHEME);
            $secure = $scheme === 'https';
        }

        return $secure && $request->isSecure();
    }

    private function calculcateOrder()
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $customerSession = $objectManager->get('\Magento\Customer\Model\Session');
        $customHelper = $objectManager->get('\Fef\CustomVoucherPoint\Helper\Data');
        $voucherPointUsedFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointUsedFactory');
        $zokuRewardQuoteFactory = $objectManager->get('\Zoku\Rewards\Model\ResourceModel\Quote');
        $checkoutSession = $objectManager->create('Magento\Checkout\Model\Session');

        $quote = $checkoutSession->getQuote();

        // $updatedAt = date('U', strtotime($observer->getQuote()->getUpdatedAt()));
        // $now = time();
        if($customerSession->getId() && $quote->getId()){
            $voucherPointUsedCollection = $voucherPointUsedFactory->create()
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerSession->getId())
            ->addFieldToFilter('quote_id', $quote->getId());
            $voucherUsedData = $voucherPointUsedCollection->getData();

            // $logger->info("data : ".print_r($voucherUsedData,true));

            $zokuRewardQuoteCollection = $zokuRewardQuoteFactory->loadByQuoteId($quote->getId());

            $usedVoucher = "";
            if(count($voucherUsedData) > 0 ){
                $usedVoucher = $voucherUsedData[0]["used_voucher"];
            }
            $usedPoints = 0;
            if(!empty($zokuRewardQuoteCollection)){
                $usedPoints = $zokuRewardQuoteCollection["reward_points"];
            }

            try {
                $customHelper->calculateOrder($usedVoucher,$usedPoints);
            } catch (\Exception $ex) {
                $logger->info("ex: ".$ex->getMessage());
            }
        }
    }
}
