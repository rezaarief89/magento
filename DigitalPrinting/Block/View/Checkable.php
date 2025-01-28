<?php

declare(strict_types=1);

namespace Wow\DigitalPrinting\Block\View;

use Magento\Framework\App\Filesystem\DirectoryList;

class Checkable extends \Magento\Catalog\Block\Product\View\Options\Type\Select\Checkable
{
    /**
     * @var string
     */
    protected $_template = 'Wow_DigitalPrinting::options/view/checkable.phtml';

    public function getImageAttribute($optionId)
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        // $logger->info("getImageAttribute : ".$optionId);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resourceConnection = $objectManager->get("\Magento\Framework\App\ResourceConnection");
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');

        $isTwSite = $this->isTwSite($storeManager);
        if(!$isTwSite){
            return "";
        }

        $baseUrl = $storeManager->getStore()->getBaseUrl();

        $connection = $resourceConnection->getConnection();
        $table = $connection->getTableName('catalog_product_option_type_value');

        $imageAttribute = "";
        $query = "SELECT `image` FROM `" . $table . "` WHERE option_id = $optionId ";
        $resultQuery = $connection->fetchAll($query);
        if(count($resultQuery) > 0){
            $imageAttribute = $resultQuery[0]["image"];
        }

        return $baseUrl."media/catalog/product/file/$imageAttribute";

    }

    protected function isTwSite($storeManager): bool
    {
        $storeCode = $storeManager->getStore()->getCode();
        return ($storeCode == "coachtw_tw");
    }

}
