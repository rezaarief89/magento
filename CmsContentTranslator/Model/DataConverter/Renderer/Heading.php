<?php


namespace Wow\CmsContentTranslator\Model\DataConverter\Renderer;

use Wow\CmsContentTranslator\Model\DataConverter\AttributesProcessor;
use Wow\CmsContentTranslator\Model\DataConverter\RendererInterface;

/**
 * Class Heading
 */
class Heading implements RendererInterface
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
        $item = $this->attributeProcessor->getAttributes($node);
        $item['heading'] = $node->nodeName;
        $item['value'] = $node->nodeValue;

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
