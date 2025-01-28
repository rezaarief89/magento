<?php
    
    namespace Wow\Xsearch\Plugin;

    class AbstractSearch
    {
        public function aroundFormatAccordingToConstraint(
            \Amasty\Xsearch\Block\Search\AbstractSearch $subject,
            \Closure $proceed,
            $text,
        ) {
            $text = str_replace("&quot;", "", $text);
            $result = $proceed($text);
            return $result;
        }
    }