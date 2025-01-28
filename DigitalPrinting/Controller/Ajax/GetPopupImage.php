<?php

namespace Wow\DigitalPrinting\Controller\Ajax;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Action\Context;

class GetPopupImage extends \Magento\Framework\App\Action\Action
{


    /**
     * @var \Magento\Framework\App\Action\Contex
     */
    private $context;
    private $request;


    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        Context $context,
        Http $request,
    ) {
        parent::__construct($context);
        $this->context = $context;
        $this->request = $request;
    }
    
    /**
     * @return json
     */
    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resourceConnection = $objectManager->get("\Magento\Framework\App\ResourceConnection");
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');

        $isTwSite = $this->isTwSite($storeManager);
        if(!$isTwSite){
            $resultJson->setData([
                "message" => "Popup just available on TW store",
                "success" => false
            ]);
            return $resultJson;
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $baseUrl = $storeManager->getStore()->getBaseUrl();
        
        try {

            $optionValue = $this->request->getParam('option_value');
            // $logger->info("optionValue : $optionValue");

            $connection = $resourceConnection->getConnection();
            $table = $connection->getTableName('catalog_product_option_type_value');

            $imageUrl = "";
            $query = "SELECT `image` FROM `" . $table . "` WHERE option_type_id = $optionValue ";
            $resultQuery = $connection->fetchAll($query);
            if(count($resultQuery) > 0){
                $imageUrl = $baseUrl."media/catalog/product/file/".$resultQuery[0]["image"];
            }

            $resultJson->setData([
                "message" => "success",
                "success" => true,
                "image" => $imageUrl
            ]);
            
        } catch (\Exception $ex) {
            $resultJson->setData([
                "message" => ($ex->getMessage()), 
                "success" => false
            ]);
        }
        

        return $resultJson;
    }

    protected function isTwSite($storeManager): bool
    {
        $storeCode = $storeManager->getStore()->getCode();
        return ($storeCode == "coachtw_tw");
    }
    
}