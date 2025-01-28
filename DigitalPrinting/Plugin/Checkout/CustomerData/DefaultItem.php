<?php

namespace Wow\DigitalPrinting\Plugin\Checkout\CustomerData;

class DefaultItem
{

    protected $resourceConnection;
    protected $storeManager;

    protected $optionId = "";

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ){
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
    }

    //This custom for minicart item image
    public function aroundGetItemData(
        \Magento\Checkout\CustomerData\AbstractItem $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item $item
    ) {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        // $logger->info("Wow DP\Defaultitem aroundGetItemData");

        $result = $proceed($item);

        $isTwSite = $this->isTwSite();
        
        // $logger->info("isTwSite : $isTwSite");

        if(!$isTwSite){
            return $result;
        }

        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('catalog_product_option_type_value');
        
        $imageAttribute = "";
        $optionId = "";
        $optionTypeId = "";
        
        if($item->getOptions() != NULL){
            foreach ($item->getOptions() as &$option) {

                // $logger->info("option : ".print_r($option->getData(),true));

                if ($option->getCode() == 'option_ids') {
                    $optionId = $option->getValue();
                    if($optionId!=""){
                        $this->optionId = $optionId;
                    }
                }                

                if($option->getCode()=="info_buyRequest"){

                    $optionValues = json_decode($option->getValue(),true);
                    
                    if(isset($optionValues["options"]) || isset($optionValues["options"][$this->optionId])){
                        foreach ($optionValues["options"] as $valueOpt) {
                            if(gettype($valueOpt)=="array"){
                                if(isset($valueOpt[0])){
                                    $optionTypeId = $valueOpt[0];
                                }
                            }else{
                                $optionTypeId = $valueOpt;
                            }                            
                        }
                        

                        if($optionTypeId != ""){
                            $query = "SELECT `image` FROM `" . $table . "` WHERE option_type_id = $optionTypeId ";
                            $resultQuery = $connection->fetchAll($query);
                            if(count($resultQuery) > 0){
                                $imageAttribute = $resultQuery[0]["image"];
                            }
                        }
                    }
                }
            }
        }

        if($imageAttribute!="" && $imageAttribute!=NULL && $optionId != ""){
            $result['product_image']['src'] = $baseUrl."media/catalog/product/file/".$imageAttribute;
        }

        // $logger->info(print_r($result["options"],true));     

        return $result;

    }

    protected function isTwSite(): bool
    {
        $storeCode = $this->storeManager->getStore()->getCode();
        return ($storeCode == "coachtw_tw");
    }
}