<?php

namespace Fef\CustomShipping\Model\ResourceModel;

class FefRateResult extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('fef_shipping_rate_result', 'id');
    }

}