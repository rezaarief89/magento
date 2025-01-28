<?php

namespace Fef\CustomVoucherPoint\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Zoku\Rewards\Model\Config\Source\RedemptionLimitTypes;

class CheckoutPointManagement implements \Fef\CustomVoucherPoint\Api\CheckoutPointInterface 
{
    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Zoku\Rewards\Model\Config
     */
    private $config;

    /**
     * @var \Zoku\Rewards\Model\Quote
     */
    private $rewardsQuote;

    /**
     * @var \Zoku\Rewards\Helper\Data
     */
    private $helper;

    /**
     * @var \Zoku\Rewards\Api\RewardsRepositoryInterface
     */
    private $rewardsRepository;

    public function __construct(
        \Zoku\Rewards\Model\Config $config,
        \Zoku\Rewards\Model\Quote $rewardsQuote,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Zoku\Rewards\Api\RewardsRepositoryInterface $rewardsRepository,
        \Zoku\Rewards\Helper\Data $helper
    ) {
        $this->config = $config;
        $this->rewardsQuote = $rewardsQuote;
        $this->quoteRepository = $quoteRepository;
        $this->rewardsRepository = $rewardsRepository;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function set($cartId, $usedPoints)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("set point for cartId : $cartId");

        if (!$usedPoints || $usedPoints < 0) {
            throw new LocalizedException(__('Points "%1" not valid.', $usedPoints));
        }

        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $minPoints = $this->config->getMinPointsRequirement($quote->getStoreId());

        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }

        $pointsLeft = $this->rewardsRepository->getCustomerRewardBalance($quote->getCustomerId());

        // $logger->info("usedPoints : $usedPoints, minPoints : $minPoints, pointsLeft : $pointsLeft");

        if ($minPoints && $pointsLeft < $minPoints) {
            throw new LocalizedException(
                __('You need at least %1 points to pay for the order with reward points.', $minPoints)
            );
        }

        try {
            if ($usedPoints > $pointsLeft) {
                throw new LocalizedException(__('Too much point(s) used.'));
            }

            // $logger->info("quote ".$quote->getId()." grand total 1 : ".$quote->getGrandTotal());

            $pointsData = $this->limitValidate($quote, $usedPoints);
            $usedPoints = abs($pointsData['allowed_points']);
            $itemsCount = $quote->getItemsCount();

            if ($itemsCount) {

                $resp = $this->calculcateOrder($quote->getId(), $usedPoints);
                // $logger->info("resp : ".print_r($resp,true));
                $newUsedPoint = 0;
                $newUsedAmount = 0;

                if(isset($resp["message"]["details"])){
                    $details = $resp["message"]["details"];
                    foreach ($details as $resDetails) {
                        if(isset($resDetails["pointsDiscount"])){
                            $newUsedPoint += $resDetails["pointsDiscount"]["used"];
                            $newUsedAmount += $resDetails["pointsDiscount"]["nettAmount"];
                        }        
                    }
                }
                if($newUsedPoint==0){
                    $newUsedPoint = $usedPoints;
                }

                // $logger->info("newUsedPoint : ".$newUsedPoint." || $newUsedAmount");

                // $this->collectCurrentTotals($quote, $usedPoints);
                $this->collectCurrentTotals($quote, $newUsedPoint, $newUsedAmount);

                // $logger->info("quote grand total 2 : ".$quote->getGrandTotal());
                $this->rewardsQuote->addReward(
                    $quote->getId(),
                    $quote->getData('zokurewards_point')
                );
            }
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        
        // $resp = $this->calculcateOrder($quote->getId(),$quote->getData('zokurewards_point'));
        // $logger->info("resp : ".print_r($resp,true));

        if($resp["success"]=="false"){
            throw new NoSuchEntityException(__('%1', $resp["message"]));
        }else{
            $pointsData['allowed_points'] = $quote->getData('zokurewards_point');
            $usedNotice = __('You used %1 point(s).', $pointsData['allowed_points']);
            $pointsData['notice'] = $pointsData['notice'] . ' ' . $usedNotice;
        }
        return $pointsData;
    }

    /**
     * {@inheritdoc}
     */
    public function collectCurrentTotals(\Magento\Quote\Model\Quote $quote, $usedPoints, $usedAmount = 0)
    {
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->setData('zokurewards_point', $usedPoints);
        if($usedAmount!=0){
            $quote->setData('zokurewards_amount', $usedAmount);
        }
        $quote->setDataChanges(true);
        $quote->collectTotals();
        $this->quoteRepository->save($quote);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param float $usedPoints
     *
     * @return array
     */
    private function limitValidate(\Magento\Quote\Model\Quote $quote, $usedPoints)
    {
        $pointsData['allowed_points'] = $usedPoints;
        $pointsData['notice'] = '';
        $isEnableLimit = $this->config->isEnableLimit($quote->getStoreId());

        if ($isEnableLimit == RedemptionLimitTypes::LIMIT_AMOUNT) {
            $limitAmount = $this->config->getRewardAmountLimit($quote->getStoreId());

            if ($usedPoints > $limitAmount) {
                $pointsData['allowed_points'] = $limitAmount;
                $pointsData['notice'] =
                    __('Number of redeemed reward points cannot exceed %1 for this order.', $limitAmount);
            }
        } elseif ($isEnableLimit == RedemptionLimitTypes::LIMIT_PERCENT) {
            $limitPercent = $this->config->getRewardPercentLimit($quote->getStoreId());
            $subtotal = $quote->getSubtotal();
            $allowedPercent = round(($subtotal / 100 * $limitPercent) / $quote->getBaseToQuoteRate(), 2);
            $rate = $this->helper->getPointsRate();
            $basePoints = $usedPoints / $rate;

            if ($basePoints > $allowedPercent) {
                $pointsData['allowed_points'] = $allowedPercent * $rate;
                $pointsData['notice'] =
                    __('Number of redeemed reward points cannot exceed %1 '
                        . '% of cart subtotal excluding tax for this order.', $limitPercent);
            }
        }

        return $pointsData;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($cartId)
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        
        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }

        $itemsCount = $quote->getItemsCount();

        if ($itemsCount) {
            $this->collectCurrentTotals($quote, 0);
        }

        $this->rewardsQuote->addReward(
            $quote->getId(),
            0
        );
        $this->calculcateOrder($quote->getId(), 0);
    }

    private function calculcateOrder($quoteId, $usedPoints)
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        // $logger->info("calculcateOrder : $usedPoints points");

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $customerSession = $objectManager->get('\Magento\Customer\Model\Session');
        $customHelper = $objectManager->get('\Fef\CustomVoucherPoint\Helper\Data');
        
        // $voucherPointUsedFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointUsedFactory');
        // $voucherPointUsedCollection = $voucherPointUsedFactory->create()
        // ->getCollection()
        // ->addFieldToFilter('customer_id', $customerSession->getId())
        // ->addFieldToFilter('quote_id', $quoteId);
        // $voucherUsedData = $voucherPointUsedCollection->getData();

        $voucherUsedData = $customHelper->getUsedVoucherPointData($customerSession->getId(), $quoteId);

        $usedVoucher = "";
        if(count($voucherUsedData) > 0 ){
            $usedVoucher = $voucherUsedData[0]["used_voucher"];
        }

        try {
            $resp = $customHelper->calculateOrder($usedVoucher,$usedPoints);
            return $resp;
        } catch (\Exception $ex) {
            
        }
    }
}
