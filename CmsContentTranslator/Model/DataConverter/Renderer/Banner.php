<?php

namespace Wow\CmsContentTranslator\Model\DataConverter\Renderer;

use Wow\CmsContentTranslator\Model\DataConverter\AttributesProcessor;
use Wow\CmsContentTranslator\Model\DataConverter\ChildrenRendererPool;
use Wow\CmsContentTranslator\Model\DataConverter\RendererInterface;

/**
 * Class Banner
 */
class Banner implements RendererInterface
{

    /**
     * @var ChildrenRendererPool
     */
    private $childrenRendererPool;

    /**
     * @var AttributesProcessor
     */
    private $attributeProcessor;

    /**
     * Slider constructor.
     *
     * @param AttributesProcessor $attributeProcessor
     * @param ChildrenRendererPool $childrenRendererPool
     */
    public function __construct(
        AttributesProcessor $attributeProcessor,
        ChildrenRendererPool $childrenRendererPool
    ) {
        $this->attributeProcessor = $attributeProcessor;
        $this->childrenRendererPool = $childrenRendererPool;
    }

    /**
     * @inheritdoc
     */
    public function toArray(\DOMDocument $domDocument, \DOMElement $node): array
    {
        $item = $this->attributeProcessor->getAttributes($node);
        $contentType = $this->attributeProcessor->getContentType($node);
        $render = $this->childrenRendererPool->getRenderer($contentType);

        if ($render) {
            $bannerSettings = $render->toArray($domDocument, $node);
            $linkNode = $node->firstChild;
            $linkRender = $this->childrenRendererPool->getRenderer(
                $this->attributeProcessor->getAttributeValue($linkNode, 'data-element')
            );

            if ($linkRender) {
                $settings = $linkRender->toArray($domDocument, $linkNode);
                $bannerSettings['link_settings'] = $settings['link_settings'];
            }

            $item['banner_settings'] = $bannerSettings;
        }

        return $item;
    }

    /**
     * @inheritdoc
     */
    public function processChildren(): bool
    {
        return false;
    }
}
