<?php

declare(strict_types=1);

namespace Wow\CustomSignupWidget\Model;

use Magento\Framework\Model\AbstractModel;

class Customsignup extends AbstractModel
{
    public function _construct()
    {
        $this->_init(\Wow\CustomSignupWidget\Model\ResourceModel\Customsignup::class);
    }
}

