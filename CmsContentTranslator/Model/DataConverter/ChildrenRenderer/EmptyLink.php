<?php

namespace Wow\CmsContentTranslator\Model\DataConverter\ChildrenRenderer;

use Wow\CmsContentTranslator\Model\DataConverter\ChildrenRendererInterface;

/**
 * Class EmptyLink
 */
class EmptyLink implements ChildrenRendererInterface
{

    /**
     * @var array
     */
    private $blackListedAttributes = [
        'data-element',
    ];

    /**
     * @inheritdoc
     */
    public function toArray(\DOMDocument $domDocument, \DOMElement $node): array
    {
        $settings = $this->getAttributes($node);

        return $settings;
    }

    /**
     * @param \DOMElement $node
     *
     * @return array
     */
    private function getAttributes($node) : array
    {
        $nodeData = [];

        if ($node->hasAttributes()) {
            /** @var \DOMElement $attribute */
            foreach ($node->attributes as $attribute) {
                $attributeName = (string)$attribute->nodeName;

                if (in_array($attributeName, $this->blackListedAttributes)) {
                    continue;
                }

                $value = $attribute->nodeValue;
                $nodeData[$attributeName] = $value;
            }

            if ($node->firstChild && 'span' === $node->firstChild->nodeName) {
                $nodeData['link_text'] = $node->firstChild->nodeValue;
            }

            if (!empty($nodeData)) {
                $nodeData['link_type'] = 'empty';
            }
        }

        return ['link_settings' => $nodeData];
    }
}
