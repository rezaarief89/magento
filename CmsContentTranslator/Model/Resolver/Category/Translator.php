<?php

declare (strict_types = 1);

namespace Wow\CmsContentTranslator\Model\Resolver\Category;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Wow\CmsContentTranslator\Helper\Category as CategoryHelper;
use Wow\PageBuilder\Model\DomProcessor;

/**
 * @inheritdoc
 */
class Translator implements ResolverInterface
{
    
    private $domProcessor;
    private $catHelper;

    public function __construct(
        CategoryHelper $catHelper,
        DomProcessor $domProcessor
    ) {
        $this->catHelper = $catHelper;
        $this->domProcessor = $domProcessor;
    }


    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $pageData = [];

        try {
            // $this->catHelper->writeLog($value);
            
            $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
            $content  = $this->catHelper->getContentById((int)$value['id'],$storeId);
            $appContent = $this->domProcessor->process('app',$content["content"]);
            $value = $this->catHelper->blockConverter($appContent);
            $pageData[] = $value;
        } catch (NoSuchEntityException $e) {
            $this->catHelper->writeLog("NoSuchEntityException : ".$e->getMessage());
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        
        return $pageData;
    }
   
}
