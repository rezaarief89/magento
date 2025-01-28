<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fef\CustomVoucherPoint\Block\Cart;

use Fef\CustomVoucherPoint\Model\VoucherPointFactory;
use Fef\CustomVoucherPoint\Model\VoucherPointUsedFactory;

/**
 * Block with apply-coupon form.
 *
 * @api
 * @since 100.0.2
 */
class Coupon extends \Magento\Checkout\Block\Cart\Coupon
{

    protected $voucherPointFactory;
    protected $voucherPointUsedFactory;
    protected $checkoutSession;
    protected $customerSession;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        VoucherPointFactory $voucherPointFactory,
        VoucherPointUsedFactory $voucherPointUsedFactory,
        array $data = []
    ) {
        parent::__construct(
            $context, 
            $customerSession, 
            $checkoutSession, 
            $data);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->voucherPointFactory = $voucherPointFactory;
        $this->voucherPointUsedFactory = $voucherPointUsedFactory;
    }

    /**
     * Applied code.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getCouponCode()
    {
        return $this->getQuote()->getCouponCode();
    }

    public function getCouponList()
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        // $logger->info("getCouponList");
        try {
            $customerId = $this->checkoutSession->getQuote()->getCustomerId();

            $voucherPoint = $this->voucherPointFactory->create()
                ->getCollection()
                ->addFieldToSelect("member_voucher_list")
                ->addFieldToFilter('customer_id',array('eq' => $customerId));

            $listVoucherArr = $voucherPoint->getData();

            $newMemberVocListArray = [];
            foreach ($listVoucherArr as $listVoucher) {
                $memberVocList = json_decode($listVoucher["member_voucher_list"],true);
                $newMemberVocListArray = [];
                $countSameVoc = 1;
                foreach ($memberVocList as $value) {
                    
                    if(!isset($newMemberVocListArray[$value["id"]])) {
                        $countSameVoc = 1;
                        $newMemberVocListArray[$value["id"]] = $value;
                    }else{
                        $countSameVoc++;
                    }
                    $newMemberVocListArray[$value["id"]]["count"] = $countSameVoc;
                }
                
            }

            // $logger->info("newMemberVocListArray : ".print_r($newMemberVocListArray,true));
            return $newMemberVocListArray;
        } catch (\Exception $ex) {
            $logger->info("Exception : ".$ex->getMessage());
        }
        
    }

    public function getUsedPoint()
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        // $logger->info("getUsedPoint");

        try {
            $quoteId = $this->checkoutSession->getQuote()->getId();
            $customerId = $this->checkoutSession->getQuote()->getCustomerId();        

            $voucherPointUsed = $this->voucherPointUsedFactory->create()
                ->getCollection()
                ->addFieldToSelect(array("used_voucher","voucher_validity"))
                ->addFieldToFilter('customer_id',array('eq' => $customerId))
                ->addFieldToFilter('quote_id',array('eq' => $quoteId));
            $usedVoucherArr = $voucherPointUsed->getData();

            foreach ($usedVoucherArr as $usedVoucher) {
                if($usedVoucher["voucher_validity"]!="Valid Voucher"){
                    return "";
                }
                return $usedVoucher["used_voucher"];
            }
            return '';
        } catch (\Exception $ex) {
            $logger->info("Exception : ".$ex->getMessage());
        }
    }

    public function isGuest()
    {
        if($this->customerSession->isLoggedIn()) {
            return 0;
        }
        return 1;
    }
}
