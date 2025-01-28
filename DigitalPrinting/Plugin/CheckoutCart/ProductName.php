<?php

namespace Wow\DigitalPrinting\Plugin\CheckoutCart;

class ProductName

{
    protected $resourceConnection;
    protected $storeManager;
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ){
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
    }

    //This custom for cart page
    public function afterGetProductName($item, $result)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("afterGetProductName : ".$result);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        if(!$this->isTwSite()){
            return $result;
        }

        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('catalog_product_option_type_value');
        $tablePrice = $connection->getTableName('catalog_product_option_type_price');
        
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode(); 
        $currency = $objectManager->create('Magento\Directory\Model\CurrencyFactory')->create()->load($currencyCode); 
        //$currencySymbol = trim($currency->getCurrencySymbol());
		$currencySymbol = ($currency->getCurrencySymbol()!="") ? trim($currency->getCurrencySymbol()) : "";
		// $logger->info("currencyCode : $currencyCode => $currencySymbol");

        
        $storeId = $this->storeManager->getStore()->getId();
        $priceAttribute = "";
        $optionId = "";

        $product = $item->getProduct();
        
        if($item->getProductOptions() != NULL){
            $options = $item->getProductOptions();
            // $logger->info("options : ".print_r($options,true));
            foreach ($options as $option) {
                
                if (isset($option["option_id"]) && $option["option_id"] != "") {
                    $skuOption = explode("-",$product->getSku())[1];
                    $optionId = $option["option_id"];
                    $query = "SELECT option_type_id, sku, `image` FROM `" . $table . "` WHERE option_id = $optionId and sku = '".$skuOption."'";
                    $resultQuery = $connection->fetchAll($query);
                    if(count($resultQuery) > 0){
                        $optionTypeId = $resultQuery[0]["option_type_id"];
                        $queryPrice = "SELECT `price` FROM `" . $tablePrice . "` WHERE option_type_id = $optionTypeId and store_id = ".$storeId;
                        $resultQueryPrice = $connection->fetchAll($queryPrice);
                        if(count($resultQueryPrice) > 0){
                            $priceAttribute = (int)$resultQueryPrice[0]["price"];
                        }
                    }
                }
            }
        }

        if($priceAttribute!=""){
            $result = $product->getName()." ($currencySymbol$priceAttribute)";
        }

        return $result;
    }

    protected function isTwSite(): bool
    {
        $storeCode = $this->storeManager->getStore()->getCode();
        return ($storeCode == "coachtw_tw");
    }

}