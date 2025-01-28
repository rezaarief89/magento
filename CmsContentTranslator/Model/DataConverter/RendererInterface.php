<?php

namespace Wow\CmsContentTranslator\Model\DataConverter;

/**
 * Interface RendererInterface
 */
interface RendererInterface
{
    const BACKGROUND_IMAGES_ATTR = 'data-background-images';

    /**
     * Convert HTML into Array
     *
     * @param \DOMDocument $domDocument
     * @param \DOMElement $node
     * @return array
     * @throws \InvalidArgumentException
     */
    public function toArray(\DOMDocument $domDocument, \DOMElement $node) : array;

    /**
     * @return bool
     */
    public function processChildren() : bool;
}
