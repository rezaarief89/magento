<?php


namespace Wow\CmsContentTranslator\Model\DataConverter\ChildrenRenderer;

use Wow\CmsContentTranslator\Model\DataConverter\AttributesProcessor;
use Wow\CmsContentTranslator\Model\DataConverter\ChildrenRendererInterface;

/**
 * Class Line
 */
class Line implements ChildrenRendererInterface
{

    /**
     * @var AttributesProcessor
     */
    private $attributeProcessor;

    /**
     * Slider constructor.
     *
     * @param AttributesProcessor $attributeProcessor
     */
    public function __construct(AttributesProcessor $attributeProcessor)
    {
        $this->attributeProcessor = $attributeProcessor;
    }

    /**
     * @inheritdoc
     */
    public function toArray(\DOMDocument $domDocument, \DOMElement $node): array
    {
        $settings['style'] = $this->attributeProcessor->getAttributeValue($node, 'style');

        return $settings;
    }
}
