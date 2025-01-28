<?php

namespace Fef\CustomVoucherPoint\Model\ResourceModel\VoucherPoint;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define collecion model
     */
    protected function _construct() {
        $this->_init(
        	'Fef\CustomVoucherPoint\Model\VoucherPoint',
        	'Fef\CustomVoucherPoint\Model\ResourceModel\VoucherPoint'
        	);
    }
}