<?php

namespace Wow\CmsContentTranslator\Model\DataConverter\Renderer;

use Wow\CmsContentTranslator\Model\DataConverter\AttributesProcessor;
use Wow\CmsContentTranslator\Model\DataConverter\ChildrenRendererPool;
use Wow\CmsContentTranslator\Model\DataConverter\RendererInterface;

/**
 * Class Divider
 */
class Divider implements RendererInterface
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
     * Divider constructor.
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
        $hrNode = $node->firstChild;
        $render = null;

        if ($hrNode) {
            $render = $this->childrenRendererPool->getRenderer(
                $this->attributeProcessor->getAttributeValue($hrNode, 'data-element')
            );
        }

        $hrSettings = [];

        if ($render) {
            $hrSettings = $render->toArray($domDocument, $hrNode);
        }

        $item['line_settings'] = $hrSettings;

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
