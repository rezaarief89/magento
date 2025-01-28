<?php

namespace Wow\CmsContentTranslator\Model\DataConverter\Renderer;

use Wow\CmsContentTranslator\Model\DataConverter\AttributesProcessor;
use Wow\CmsContentTranslator\Model\DataConverter\RendererInterface;
use Wow\CmsContentTranslator\Helper\ArrayBuilder;

/**
 * Class Html
 */
class Html implements RendererInterface
{

    const OPEN_DIV = '<div';
    const CLOSE_DIV = '</div>';
    const CLOSE_LINETAG = '/>';
    const CLOSE_TAG = '>';
    const DOUBLE_QUOTE = '"';
    const SPAN = 'span';
    const SPAN_OPEN_TAG = '<span>';
    const SPAN_CLOSE_TAG = '</span>';
    const H1 = 'h1';
    const CATID = 'data-category-id';
    const FLAG_INDEX = 'data-custom-type';
    const IMG_TAG = '<img';

    

    /**
     * @var AttributesProcessor
     */
    private $attributeProcessor;

    private $arrayBuilder;

    /**
     * Slider constructor.
     *
     * @param AttributesProcessor $attributeProcessor
     */
    public function __construct(
        AttributesProcessor $attributeProcessor,
        ArrayBuilder $arrayBuilder
    ){
        $this->attributeProcessor = $attributeProcessor;
        $this->arrayBuilder = $arrayBuilder;
    }

    /**
     * @inheritdoc
     */
    public function toArray(\DOMDocument $domDocument, \DOMElement $node): array
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $logger = $objectManager->get('\Wow\CmsContentTranslator\Model\DataConverter\Logger');
        
        $item = $this->attributeProcessor->getAttributes($node);
        $html = '';
        
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                $html .= $domDocument->saveHtml($child);
            }
        }
        $linkDomElement = $this->getDomElementFromHtml($html);
        $textDomElement = $linkDomElement->textContent;

        $attributes = $this->parseToArray($textDomElement,$logger);
        
        $item['value'] = $html;
        $item['html_attributes'] = json_encode($attributes);
        return $item;
    }

    
    /**
     * @inheritdoc
     */
    public function processChildren(): bool
    {
        return false;
    }

    function getDomElementFromHtml($html)
    {
        $document = new \DOMDocument();
        $document->loadHTML($html);
        return $document->getElementsByTagName('*')->item(0);
    }

    public function parseToArray($htmlText, $logger)
    {
        $htmlText = preg_replace( "/\r|\n|\t/", "||", $htmlText);
        $htmlTextArray = explode("||",$htmlText);
        $finalResults = array();
        $eachResults = [];
        foreach ($htmlTextArray as $htmlContent) {
            $htmlContent = ltrim($htmlContent);
            if($htmlContent!=""){
                $htmlContent = $this->arrayBuilder->replaceStringValue($htmlContent);
                array_push($eachResults, $htmlContent);
            }
        }

        $newArray = [];
        $newSecondArray = [];
        $idxKey = 0;
        $idxArray = [];

        foreach ($eachResults as $keyRes => $results) 
        {
            if(strpos($results, self::FLAG_INDEX) !== false){
                $valResult = $this->replaceChar($results);
                $finalResults[str_replace("-","_",self::FLAG_INDEX)][$valResult] = array();
                $idxArray[$idxKey] = $valResult;
                $idxKey++;
            }
            else{
                
                if (
                    (strpos($results, self::OPEN_DIV) !== false && strpos($results, self::CLOSE_DIV) === false) &&
                    (strpos($results, self::OPEN_DIV) !== false && strpos($results, self::CLOSE_LINETAG) === false)
                ){
                    $newSecondArray = $this->getTextWithAllTags($results);
                    if(!empty($newSecondArray)){
                        $finalResults[str_replace("-","_",self::FLAG_INDEX)][$idxArray[$idxKey-1]] = $newSecondArray;
                    }
                }else{
                    $newSecondArray = $this->getTextWithAllTags($results);
                    if(!empty($newSecondArray)){
                        $finalResults[str_replace("-","_",self::FLAG_INDEX)][$idxArray[$idxKey-1]]["items"][] = $newSecondArray;
                    }
                }
            }
        }
        // $logger->writeLog($finalResults);
        // $logger->writeLog(json_encode($finalResults,1));
        return $finalResults;
    }

    private function replaceChar($string)
    {
        $valResult = str_replace(self::OPEN_DIV,"",$string);
        $valResult = str_replace(self::CLOSE_DIV,"",$valResult);
        $valResult = str_replace(self::CLOSE_LINETAG,"",$valResult);        
        $valResult = str_replace(self::FLAG_INDEX."=","",$valResult);
        $valResult = str_replace(self::CLOSE_TAG,"",$valResult);
        $valResult = str_replace(self::DOUBLE_QUOTE,"",$valResult);
        return trim($valResult);
    }

    private function getTextWithAllTags($string) 
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadHTML($string);
        $return = array();
        foreach($domDocument->getElementsByTagName('*') as $item){
            $attributes = $item->attributes;
            foreach ($attributes as $attribute) {
                $return[str_replace("-","_",$attribute->nodeName)] = $attribute->nodeValue;
            }
            $return["text"] = $item->textContent;
            $return["tag_name"] = $item->nodeName;
        }
        return $return;
    }
}
