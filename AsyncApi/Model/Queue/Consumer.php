<?php

namespace Wow\AsyncApi\Model\Queue;

use Magento\Catalog\Api\ProductRepositoryInterface;

class Consumer extends \Magento\Framework\MessageQueue\ConsumerConfiguration
{

    public function __construct(
        ProductRepositoryInterface $productRepository,
    ) {
        $this->productRepository = $productRepository;
    }

    /**
     * @param array $products
     *
     * @throws LocalizedException
     */
    public function process(string $products)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/product-sync-update.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        
        $productData = json_decode($products,true);

        $logger->info($productData);

        foreach ($productData["products"] as $key => $products) {
            try {
                $product = $this->productRepository->get($products["sku"]);
                $product->setName($products["name"]);
                $this->productRepository->save($product);
                return true;
            } catch (\Exception $ex) {
                $message = __('Sorry, something went wrong during add product '.$products["sku"].' to queue. Please see log for details.');
                $logger->info($message);
                $logger->info($ex->getMessage());
            }
        }

        return true;
    }

}