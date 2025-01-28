<?php

namespace Wow\AsyncApi\Api;

interface BulkProductUpdateInterface
{
     /**
     * Bulk update product names asynchronously.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface[] $products
     * @return string
     */
    public function execute(array $products): string;
}
