<?php

namespace Wow\DigitalPrinting\Observer\Adminhtml;

use Magento\Framework\Event\ObserverInterface;

class ProductSaveBefore implements ObserverInterface
{
    protected $resourceConnection;

    protected $request;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $connection = $this->resourceConnection->getConnection();
        $productData = $this->request->getPost();

        $productDataOptionsForms = [];
        if(isset($productData["product"]["options"])){
            $productDataOptionsForms = $productData["product"]["options"];
            foreach ($productDataOptionsForms as $keyForm => $valuesForm) {
                if(isset($productDataOptionsForms[$keyForm]["values"])){
                    $productDataOptionsFormsValues = $productDataOptionsForms[$keyForm]["values"];
                    foreach ($productDataOptionsFormsValues as $keyFormValues => $valFormValues) {
                        if(isset($valFormValues["file"])){
                            $query = "UPDATE catalog_product_option_type_value SET `image`= '".$valFormValues["file"][0]["name"]."' WHERE option_type_id = ".$valFormValues["option_type_id"];
                            $connection->query($query);
                        }
                    }
                }
            }
        }
        
    }
}