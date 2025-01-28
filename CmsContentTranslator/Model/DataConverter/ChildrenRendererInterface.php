<?php


namespace Wow\CmsContentTranslator\Model\DataConverter;

/**
 * Interface ChildrenRendererInterface
 */
interface ChildrenRendererInterface
{
    /**
     * @param \DOMDocument $domDocument
     * @param \DOMElement $node
     *
     * @return array
     */
    public function toArray(\DOMDocument $domDocument, \DOMElement $node) : array;
}
