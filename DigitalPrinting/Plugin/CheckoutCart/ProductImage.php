<?php

namespace Wow\DigitalPrinting\Plugin\CheckoutCart;

class ProductImage

{
    protected $resourceConnection;
    protected $storeManager;
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
    }

    //This custom for cart page
    public function afterGetImage($item, $result)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        if (!$this->isTwSite()) {
            return $result;
        }

        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('catalog_product_option_type_value');

        $imageAttribute = "";
        $optionId = "";

        if ($item !== null) {
            try {
                $product = $item->getProduct();

                if ($item->getProductOptions() != NULL) {
                    $options = $item->getProductOptions();

                    foreach ($options as $option) {
                        if (isset($option["option_id"]) && $option["option_id"] != "") {
                            $skuOption = explode("-", $product->getSku())[1];
                            $optionId = $option["option_id"];
                            $query = "SELECT sku, `image` FROM `" . $table . "` WHERE option_id = $optionId and sku = '" . $skuOption . "'";
                            $resultQuery = $connection->fetchAll($query);
                            if (count($resultQuery) > 0) {
                                $imageAttribute = $resultQuery[0]["image"];
                            }
                        }
                    }
                }

                if ($imageAttribute != "" && $imageAttribute != NULL && $optionId != "") {
                    $result->setImageUrl($baseUrl . "media/catalog/product/file/" . $imageAttribute);
                }
            } catch (\Exception $e) {
                $logger->info($e->getMessage());
                $logger->info('item info' . json_encode($item->getData()));
                return $result;
            }


            return $result;
        }
    }

    protected function isTwSite(): bool
    {
        $storeCode = $this->storeManager->getStore()->getCode();
        return ($storeCode == "coachtw_tw");
    }
}
