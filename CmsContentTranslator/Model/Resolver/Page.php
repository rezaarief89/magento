<?php

declare (strict_types = 1);

namespace Wow\CmsContentTranslator\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\CmsGraphQl\Model\Resolver\DataProvider\Page as PageDataProvider;
use Wow\CmsContentTranslator\Helper\Page as HelperPage;
use Wow\PageBuilder\Model\DomProcessor;

/**
 * @inheritdoc
 */
class Page extends \Magento\CmsGraphQl\Model\Resolver\Page
{

    private $domProcessor;

    private $pageHelper;

    private $pageDataProvider;

    public function __construct(
        HelperPage $pageHelper,
        DomProcessor $domProcessor,
        PageDataProvider $pageDataProvider
    ) {
        $this->pageHelper = $pageHelper;
        $this->domProcessor = $domProcessor;
        $this->pageDataProvider = $pageDataProvider;
        parent::__construct($pageDataProvider);
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {        
        if (!isset($args['id']) && !isset($args['identifier'])  && !isset($args['previewdate'])) {
            throw new GraphQlInputException(__('"Page id/identifier/previewdate should be specified'));
        }

        $pageData = [];

        try {
            if (isset($args['id'])) {
                $pageData = $this->pageDataProvider->getDataByPageId((int)$args['id']);
            } elseif (isset($args['identifier'])) {
                $pageData = $this->pageDataProvider->getDataByPageIdentifier(
                    (string)$args['identifier'],
                    (int)$context->getExtensionAttributes()->getStore()->getId()
                );
            }
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }

        if (isset($args['previewdate'])) {
            $pageData["previewdate"] = $args['previewdate'];
        }

        return $pageData;
    }

    private function setDummyContent()
    {
        return '';
    }
   
}
