<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Wow\Einvoice\Model;

use Magento\Framework\Model\AbstractModel;

class EinvoiceOauth extends AbstractModel
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Wow\Einvoice\Model\ResourceModel\EinvoiceOauth::class);
    }
}

