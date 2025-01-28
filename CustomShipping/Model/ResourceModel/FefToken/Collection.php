<?php

namespace Fef\CustomShipping\Model\ResourceModel\FefToken;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define collecion model
     */
    protected function _construct() {
        $this->_init(
        	'Fef\CustomShipping\Model\FefToken',
        	'Fef\CustomShipping\Model\ResourceModel\FefToken'
        	);
    }
}