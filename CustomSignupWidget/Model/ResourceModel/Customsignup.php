<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Wow\CustomSignupWidget\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Customsignup extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('wow_custom_signup', 'signup_id');
    }
}

