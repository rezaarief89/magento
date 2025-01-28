<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Wow\Einvoice\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class EinvoiceStatus extends AbstractDb
{

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('wow_einvoice_status', 'einvoicestatus_id');
    }
}

