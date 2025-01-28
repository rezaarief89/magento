<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fef\CustomVoucherPoint\Controller\Cart;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CouponPost extends \Magento\Checkout\Controller\Cart implements HttpPostActionInterface
{
    /**
     * Sales quote repository
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Coupon factory
     *
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    protected $couponFactory;
    protected $checkoutSession;

    protected $customVoucherHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\SalesRule\Model\CouponFactory $couponFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\SalesRule\Model\CouponFactory $couponFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Fef\CustomVoucherPoint\Helper\Data $customVoucherHelper
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
        $this->couponFactory = $couponFactory;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->customVoucherHelper = $customVoucherHelper;
    }

    /**
     * Initialize coupon
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $voucherPointUsedFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointUsedFactory');

        //set kolom used_voucher dari table proseller_voucher_point_used
        // $logger->info(print_r($this->getRequest()->getParams(),true));
        $couponCodeParams = $this->getRequest()->getParam('remove') == 1
            ? ''
            : trim($this->getRequest()->getParam('proseller_voucher'));

        $logger->info("params : ".print_r($this->getRequest()->getParams(),true));

        $cartQuote = $this->cart->getQuote();
        $logger->info("cartQuoteId : ".$cartQuote->getId());
        $couponCodeArr = explode('|',$couponCodeParams);
        $couponCode= $couponCodeArr[0];
        $couponName= isset($couponCodeArr[1]) ? $couponCodeArr[1] : "";
        $couponValue= isset($couponCodeArr[2]) ? $couponCodeArr[2] : 0;
        $couponType= isset($couponCodeArr[3]) ? $couponCodeArr[3] : "";
        $cartQuote = $this->cart->getQuote();
        $oldCouponCode = $cartQuote->getCouponCode();
        $arrVoucherInfo = array(
            "voucher_name" =>$couponName,
            "voucher_amount" =>$couponValue,
            "voucher_type" =>$couponType
        );

        $codeLength = strlen($couponCode);

        $customerId = $cartQuote->getCustomerId();
        

        try {

            $voucherPointUsed = $voucherPointUsedFactory->create();
            $voucherPointUsedCollection = $voucherPointUsed->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('quote_id', $cartQuote->getId());

            $dataCollection = $voucherPointUsedCollection->getData();

                      

            if($this->getRequest()->getParam('remove') == 0){
                if(count($dataCollection) > 0){
                    foreach ($dataCollection as $key => $collection) {
                        $id = $collection["id"];
                        $postUpdate = $voucherPointUsed->load($id);
                        $this->saveData($postUpdate,$couponCode,$arrVoucherInfo);
                    }
                }else{
                    $this->saveData($voucherPointUsed,$couponCode,$arrVoucherInfo);
                }    

                $result = $this->customVoucherHelper->applyVoucher($couponCode);

                // $logger->info(print_r($result,true));
                
                if($result["success"]=="false"){
                    $couponCode = "";
                    $cartQuote->setCouponCode("")->save();
                    $this->messageManager->addErrorMessage(__($result["message"]));
                }else{
                    $escaper = $this->_objectManager->get(\Magento\Framework\Escaper::class);
                    $cartQuote->setCouponCode($couponCode)->save();
                    $this->messageManager->addSuccessMessage(
                        __(
                            'You used voucher "%1".',
                            $escaper->escapeHtml($couponName)
                        )
                    );
                }
            }else{
                // $quoteItems = $cartQuote->getAllItems();
                // foreach ($quoteItems as $quoteItem) {
                //     $itemId = $quoteItem->getItemId();
                //     $logger->info("itemId : ".$itemId);
                //     $item = $cartQuote->getItemById($itemId);
                //     $item->setDiscountAmount(0);
                //     $item->save();
                    
                // }

                $this->customVoucherHelper->unapplyVoucher($couponCode);
                $cartQuote->setCouponCode("")->save();
                $this->messageManager->addSuccessMessage(__('You canceled the voucher.'));
            }

            if(count($dataCollection) > 0){
                foreach ($dataCollection as $key => $collection) {
                    $id = $collection["id"];
                    $postUpdate = $voucherPointUsed->load($id);
                    $this->saveData($postUpdate,$couponCode,$arrVoucherInfo);
                }
            }else{
                $this->saveData($voucherPointUsed,$couponCode,$arrVoucherInfo);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('We cannot apply the voucher.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
        }

        return $this->_goBack();
    }

    private function saveData($voucherPointUsed,$couponCode,$arrVoucherInfo){

        $voucherPointUsed->setCustomerId($this->checkoutSession->getQuote()->getCustomerId());
        $voucherPointUsed->setQuoteId($this->checkoutSession->getQuote()->getId());
        $voucherPointUsed->setUsedVoucher($couponCode);
        if($couponCode!=""){
            $voucherPointUsed->setVoucherName($arrVoucherInfo["voucher_name"]);
            $voucherPointUsed->setVoucherAmount($arrVoucherInfo["voucher_amount"]);
            $voucherPointUsed->setVoucherType($arrVoucherInfo["voucher_type"]);
        }else{
            $voucherPointUsed->setVoucherName("");
            $voucherPointUsed->setVoucherAmount("");
            $voucherPointUsed->setVoucherType("");
            $voucherPointUsed->setVoucherValidity("");
        }
        $voucherPointUsed->save();
    }
    
}
