<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Wow\Einvoice\Api\Data;

interface EinvoiceSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get einvoice list.
     * @return \Wow\Einvoice\Api\Data\EinvoiceInterface[]
     */
    public function getItems();

    /**
     * Set pre_payment_amount list.
     * @param \Wow\Einvoice\Api\Data\EinvoiceInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

