<?php

declare (strict_types = 1);

namespace Wow\CmsContentTranslator\Model\Resolver\CmsPage;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Wow\CmsContentTranslator\Helper\Page as HelperPage;
use Wow\PageBuilder\Model\DomProcessor;

/**
 * @inheritdoc
 */
class Translator implements ResolverInterface
{

    private $domProcessor;

    private $pageHelper;

    public function __construct(
        HelperPage $pageHelper,
        DomProcessor $domProcessor
    ) {
        $this->pageHelper = $pageHelper;
        $this->domProcessor = $domProcessor;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {        
        $pageData = [];        
        try {
            $content = [];
            if (isset($value['page_id'])) {
                $content  = $this->pageHelper->getContentById((int)$value['page_id']);
            } else if (isset($value['identifier'])) {
                $content = $this->pageHelper->getContentByIdentifier(
                    (string)$value['identifier'],
                    (int)$context->getExtensionAttributes()->getStore()->getId()
                );
            }

            

            if (isset($value['previewdate'])) {
                $appContent = $this->domProcessor->process('app',$content["content"], $value['previewdate']);
            }else{
                $appContent = $this->domProcessor->process('app',$content["content"]);
            }

            $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
            $value = $this->pageHelper->blockConverter($appContent);
            $pageData[] = $value;

            

        } catch (NoSuchEntityException $e) {
            $this->pageHelper->writeLog("NoSuchEntityException : ".$e->getMessage());
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        return $pageData;
    }

    private function setDummyContent()
    {
        return '';
    }
   
}
