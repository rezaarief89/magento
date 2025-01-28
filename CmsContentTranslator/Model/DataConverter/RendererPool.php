<?php

namespace Wow\CmsContentTranslator\Model\DataConverter;

/**
 * Class RendererPool
 */
class RendererPool
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
     * @return RendererInterface
     */
    public function getRenderer(string $contentType) : ?RendererInterface
    {
        if (isset($this->renderers[$contentType])) {
            return $this->renderers[$contentType];
        }

        return $this->renderers['default'];
    }
}
