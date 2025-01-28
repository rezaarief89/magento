<?php

namespace Wow\DigitalPrinting\Observer\Checkout;

use Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;


class CheckoutSuccess implements ObserverInterface
{

    protected $objectManager;
    protected $orderRepository;
    protected $jsonHelper;
    protected $resourceConnection;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->objectManager = $objectManager;
        $this->logger = $logger;
        $this->orderRepository = $orderRepositoryInterface;
        $this->jsonHelper = $jsonHelper;
        $this->resourceConnection = $resourceConnection;
    }
    public function execute(Observer $observer) {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $order = $observer->getEvent()->getOrder();
        $visibleItems = $this->getAllVisibleItems($order);
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('catalog_product_option_type_value');
        $tablePrice = $connection->getTableName('catalog_product_option_type_price');

        $storeId = $order->getStoreId();

        try {
            foreach ($visibleItems as $item) {
                $imageAttribute = "";
                $priceAttribute = NULL;
                $options = $item->getProductOptions();
                if(isset($options['options'])) {
                    
                    // $logger->info("options : ".print_r($options['options'],true));

                    foreach ($options['options'] as $option) {
                        if (isset($option["option_id"]) && $option["option_id"] != "") {
                            $skuOption = explode("-",$item->getSku())[1];
                            
                            $optionId = $option["option_id"];
                            $optionTypeId = $option["option_value"];
                            
                            $query = "SELECT sku, `image` FROM `" . $table . "` WHERE option_id = $optionId and sku = '".$skuOption."'";
                            $resultQuery = $connection->fetchAll($query);
                            if(count($resultQuery) > 0){
                                $imageAttribute = $resultQuery[0]["image"];
                            }

                            $queryPrice = "SELECT `price` FROM `" . $tablePrice . "` WHERE option_type_id = $optionTypeId and store_id = ".$storeId;
                            $resultQueryPrice = $connection->fetchAll($queryPrice);
                            if(count($resultQueryPrice) > 0){
                                $priceAttribute = $resultQueryPrice[0]["price"];
                            }
                        }
                    }
                }
                if($imageAttribute!=""){
                    $item->setOptionImage($imageAttribute);
                }
                if($priceAttribute!=""){
                    $item->setOptionPrice($priceAttribute);
                }
            }
            $order->save();
        } catch (\Exception $ex) {
            $logger->info("Exception : ".$ex->getMessage());
        }
        return $this;
    }

    /**
     * Retrieves visible products of the order, omitting its children (this is different than Magento's method)
     *
     * @param Order $order
     * @return array
     */
    protected function getAllVisibleItems($order)
    {
        $items = [];
        foreach ($order->getItems() as $item) {
            if (!$item->isDeleted() && !$item->getParentItem()) {
                $items[] = $item;
            }
        }

        return $items;
    }
}

