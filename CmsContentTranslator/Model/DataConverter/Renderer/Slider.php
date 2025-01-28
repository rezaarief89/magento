<?php

namespace Wow\CmsContentTranslator\Model\DataConverter\Renderer;

use Wow\CmsContentTranslator\Model\DataConverter\AttributesProcessor;
use Wow\CmsContentTranslator\Model\DataConverter\ChildrenRendererPool;
use Wow\CmsContentTranslator\Model\DataConverter\RendererInterface;

/**
 * Class Slider
 */
class Slider implements RendererInterface
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
        $childItems = [];

        /** @var \DOMElement $slideNode */
        foreach ($node->childNodes as $slideNode) {
            $contentType = $this->attributeProcessor->getContentType($slideNode);
            $render = $this->childrenRendererPool->getRenderer($contentType);

            if ($render) {
                $slideSettings = $render->toArray($domDocument, $slideNode);
                $linkNode = $slideNode->firstChild;
                $linkRender = $this->childrenRendererPool->getRenderer(
                    $this->attributeProcessor->getAttributeValue($linkNode, 'data-element')
                );

                $settings = [];

                if ($linkRender) {
                    $settings = $linkRender->toArray($domDocument, $linkNode);
                }

                $slideSettings = array_merge($settings, $slideSettings);
                $childItems[] = $slideSettings;
            }
        }

        $item['slides'] = $childItems;

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
