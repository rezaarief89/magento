<?php

declare(strict_types=1);

namespace Wow\CustomSignupWidget\Model\ResourceModel\Customsignup;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'signup_id';

    protected function _construct()
    {
        $this->_init(
            \Wow\CustomSignupWidget\Model\Customsignup::class,
            \Wow\CustomSignupWidget\Model\ResourceModel\Customsignup::class
        );
    }
}

