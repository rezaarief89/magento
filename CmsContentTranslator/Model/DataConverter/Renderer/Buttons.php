<?php

namespace Wow\CmsContentTranslator\Model\DataConverter\Renderer;

use Wow\CmsContentTranslator\Model\DataConverter\AttributesProcessor;
use Wow\CmsContentTranslator\Model\DataConverter\ChildrenRendererPool;
use Wow\CmsContentTranslator\Model\DataConverter\RendererInterface;

/**
 * Class Banner
 */
class Buttons implements RendererInterface
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
        $xpath = new \DOMXPath($domDocument);
        $item = $this->attributeProcessor->getAttributes($node);
        $buttonNodes = $this->getButtonsNodes($xpath, $node);
        $buttons = [];

        foreach ($buttonNodes as $button) {
            $linkRender = $this->childrenRendererPool->getRenderer(
                $this->attributeProcessor->getAttributeValue($button, 'data-element')
            );

            if ($linkRender) {
                $settings = $linkRender->toArray($domDocument, $button);
                $settings['data-content-type'] = 'button-item';
                $buttons[] = $settings;
            }
        }

        $item['items'] = $buttons;

        return $item;
    }

    /**
     * @param \DOMXPath $xpath
     * @param \DOMElement $node
     *
     * @return \DOMNodeList
     */
    private function getButtonsNodes(\DOMXPath $xpath, \DOMElement $node)
    {
        return $xpath->query('.//*[contains(@class, "pagebuilder-button")]', $node);
    }

    /**
     * @inheritdoc
     */
    public function processChildren(): bool
    {
        return false;
    }
}
