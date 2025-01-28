<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Wow\Einvoice\Model\ResourceModel\Einvoice;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'einvoice_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Wow\Einvoice\Model\Einvoice::class,
            \Wow\Einvoice\Model\ResourceModel\Einvoice::class
        );
    }
}

