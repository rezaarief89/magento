<?php

namespace Wow\AsyncApi\Model;

use Wow\AsyncApi\Api\BulkProductUpdateInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

class BulkProductUpdate implements BulkProductUpdateInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        PublisherInterface $publisher
    ) {
        $this->publisher = $publisher;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $products): string
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/product-sync-update.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $returnProducts = [];
        foreach ($products as $productData) {
            try {
                $sku = $productData->getSku();
                $name = $productData->getName();
                $returnProducts[] = array(
                    "sku" => $sku,
                    "name" => $name
                );
            } catch (\Exception $e) {
                $this->logger->error('Error updating product: ' . $e->getMessage());
            }
        }

        $logger->info("products : ".print_r($returnProducts,true));

        $this->publisher->publish('product_bulk_update_consumer',json_encode(array(
            "products"=>$returnProducts
        )));

        return 'Request is being processed asynchronously.';
    }
}
