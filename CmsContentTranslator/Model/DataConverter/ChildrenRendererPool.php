<?php

namespace Wow\CmsContentTranslator\Model\DataConverter;

/**
 * Class ChildrenRendererPool
 */
class ChildrenRendererPool
{
    /**
     * @var array
     */
    private $renderers;

    /**
     * Constructor
     *
     * @param array $renderers
     */
    public function __construct(
        array $renderers
    ) {
        $this->renderers = $renderers;
    }

    /**
     * Get renderer for content type
     *
     * @param string $contentType
     * @return ChildrenRendererInterface
     */
    public function getRenderer(string $contentType) : ?ChildrenRendererInterface
    {
        if (isset($this->renderers[$contentType])) {
            return $this->renderers[$contentType];
        }

        return null;
    }
}
