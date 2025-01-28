<?php
namespace Fef\CustomVoucherPoint\Block\Order;

class Totals extends \Smartosc\Checkout\Block\Order\Totals
{
    /**
     * @var \Magento\Catalog\Helper\Data
     */
    protected $taxHelper;

    /**
     * @var \Smartosc\Checkout\Model\Quote\CustomPricingFactory
     */
    protected $customPricingFactory;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Helper\Data $taxHelper,
        \Magento\Checkout\Model\Cart $cart,
        \Smartosc\Checkout\Model\Quote\CustomPricingFactory $customPricingFactory,
        array $data = []
    ) {
        $this->taxHelper = $taxHelper;
        $this->customPricingFactory = $customPricingFactory;
        $this->cart = $cart;
        parent::__construct($context, $registry, $taxHelper, $cart, $customPricingFactory, $data);
    }

    /**
     * @return false|float|int
     */
    public function getOldPrice()
    {
        return $this->customPricingFactory->create()->setCart($this->cart)->getBaseOriginalPrice();
    }
    public function getTotalSaving()
    {
        return $this->customPricingFactory->create()->setCart($this->cart)->getTotalSaving();
    }
    /**
     * @return Totals|string
     */
    public function _initTotals()
    {
        $source = $this->getSource();

        $this->_totals = [];
        $this->_totals['subtotal'] = new \Magento\Framework\DataObject(
            ['code' => 'subtotal', 'value' => $source->getSubtotal(), 'label' => __('Subtotal')]
        );

        /**
         * Add discount
         */

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $customerSession = $objectManager->get('\Magento\Customer\Model\Session');
        $customHelper = $objectManager->get('\Fef\CustomVoucherPoint\Helper\Data');
        $voucherPointUsedFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointUsedFactory');
        $CalculateTempFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\CalculateTempFactory');
        $urlInterface = $objectManager->get('\Magento\Framework\UrlInterface');

        $customerId = $this->getSource()->getCustomerId();
        $quoteId = $this->getSource()->getQuoteId();

        $tempResult = $CalculateTempFactory->create();
        $tempResultCollection = $tempResult
        ->getCollection()
        ->addFieldToFilter('customer_id', $customerId)
        ->addFieldToFilter('quote_id', $quoteId);
        $tempDataCollections = $tempResultCollection->getData();

        // $logger->info("count : ".count($dataCollection));
        // $logger->info("customerId : ".$customerId.", getQuoteId : ".$quoteId);

        if(count($tempDataCollections) > 0){
            foreach ($tempDataCollections as $tempData) {
                $calculateResult = json_decode($tempData["calculate_result"],true);
                $detailsList = $calculateResult["details"];
                // $logger->info(print_r($calculateResult,true));
                $totalVoucherDiscAmount = 0;
                $voucherDiscName = "";
                $totalPointsAmount = 0;
                $totalPointsUsed = 0;
                $totalMembershipDiscountAmount = 0;

                foreach ($detailsList as $details) {
                    // $logger->info(print_r($details,true));
                    $totalVoucherDiscAmount += $details["voucherDiscAmount"];

                    if(isset($details["vouchersDiscount"])){
                        $voucherDiscName = $details["vouchersDiscount"][0]["name"];
                    }

                    if(isset($details["pointsDiscount"])){
                        $totalPointsAmount += $details["pointsDiscount"]["value"];
                        $totalPointsUsed += $details["pointsDiscount"]["used"];
                    }
                }
                if(isset($calculateResult["totalMembershipDiscountAmount"])){
                    $totalMembershipDiscountAmount = $calculateResult["totalMembershipDiscountAmount"];
                }
            }

            if ((double)$totalMembershipDiscountAmount != 0) {
                $this->_totals['discount_membership'] = new \Magento\Framework\DataObject(
                    [
                        'code' => 'discount',
                        'field' => 'discount_amount',
                        'value' => $totalMembershipDiscountAmount,
                        'label' => __('Discount (%1)', "Membership"),
                        'style' => "red"
                    ]
                );
            }

            if ((double)$totalVoucherDiscAmount != 0) {
                $this->_totals['discount_voucher'] = new \Magento\Framework\DataObject(
                    [
                        'code' => 'discount',
                        'field' => 'discount_amount',
                        'value' => $totalVoucherDiscAmount,
                        'label' => __('Discount (%1)', $voucherDiscName),
                        'style' => "red"
                    ]
                );
            }

            
            if ((double)$totalPointsAmount != 0) {
                $this->_totals['discount_points'] = new \Magento\Framework\DataObject(
                    [
                        'code' => 'discount',
                        'field' => 'discount_amount',
                        'value' => $totalPointsAmount,
                        'label' => __('Discount (%1)', $totalPointsUsed." Points Redeemed"),
                        'style' => "red"
                    ]
                );
            }

        }else{
            if ((double)$this->getSource()->getDiscountAmount() != 0) {
                if ($this->getSource()->getDiscountDescription()) {
                    $discountLabel = __('Discount (%1)', $source->getDiscountDescription());
                } else {
                    $discountLabel = __('Discount');
                }
                $this->_totals['discount'] = new \Magento\Framework\DataObject(
                    [
                        'code' => 'discount',
                        'field' => 'discount_amount',
                        'value' => $source->getDiscountAmount(),
                        'label' => $discountLabel,
                        'style' => yes
                    ]
                );
            } 
        }

        // $logger->info("totalVoucherDiscAmount : $totalVoucherDiscAmount,  totalPointsAmount :  $totalPointsAmount, totalMembershipDiscountAmount : $totalMembershipDiscountAmount, totalPointsUsed : $totalPointsUsed, voucherDiscName : $voucherDiscName");
        


        /**
         * Add shipping
         */
        if (!$source->getIsVirtual() && ((double)$source->getShippingAmount() || $source->getShippingDescription())) {
            $label = __('Delivery Charges');
            if ($this->getSource()->getCouponCode() && !isset($this->_totals['discount'])) {
                $label = __('Delivery Charges (%1)', $this->getSource()->getCouponCode());
            }

            $this->_totals['shipping'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'shipping',
                    'field' => 'shipping_amount',
                    'value' => $this->getSource()->getShippingInclTax(),
                    'label' => $label,
                ]
            );
        }

        $this->_totals['grand_total'] = new \Magento\Framework\DataObject(
            [
                'code' => 'grand_total',
                'field' => 'grand_total',
                'strong' => true,
                'value' => $source->getGrandTotal(),
                'label' => __('Grand Total (Incl. GST)'),
            ]
        );

//        $this->_totals['tax_amount'] = new \Magento\Framework\DataObject(
//            [
//                'code' => 'tax_amount',
//                'field' => 'tax_amount',
//                'strong' => true,
//                'value' => $source->getTaxAmount(),
//                'label' => __('GST (7%)'),
//            ]
//        );
        $this->_totals['grand_total_excl'] = new \Magento\Framework\DataObject(
            [
                'code' => 'grand_total_excl',
                'field' => 'grand_total_excl',
                'value' => $source->getGrandTotal()-$source->getTaxAmount(),
                'label' => __('Grand Total (Excl. GST)'),
            ]
        );

        /**
         * Base grandtotal
         */
        if ($this->getOrder()->isCurrencyDifferent()) {
            $this->_totals['base_grandtotal'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'base_grandtotal',
                    'value' => $this->getOrder()->formatBasePrice($source->getBaseGrandTotal()),
                    'label' => __('Grand Total to be Charged'),
                    'is_formated' => true,
                ]
            );
        }
        return $this;
    }
}
