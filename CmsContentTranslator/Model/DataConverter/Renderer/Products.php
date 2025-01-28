<?php

namespace Wow\CmsContentTranslator\Model\DataConverter\Renderer;

use Wow\CmsContentTranslator\Model\DataConverter\AttributesProcessor;
use Wow\CmsContentTranslator\Model\DataConverter\RendererInterface;

/**
 * Class Base
 */
class Products implements RendererInterface
{
    const WIDGET = '{{widget type=';
    const SPACE_SEPARATOR = '" ';
    const EQUAL_SEPARATOR = '=';

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
        $value = $node->nodeValue;

        if (strpos($value, self::WIDGET) !== false) {
            $value = stripslashes($value);
            $value = $this->explodeWidget($value);
        }else{
            $value = json_decode(stripslashes($value), true);
        }

        if (is_array($value)) {
            $item['conditions'] = $value;
        }

        return $item;
    }

    /**
     * @inheritdoc
     */
    public function processChildren(): bool
    {
        return false;
    }

    public function explodeWidget($string)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $conditionsW = $objectManager->get('\Magento\Widget\Helper\Conditions');
        $productTotals = $objectManager->get('\Wow\CmsContentTranslator\Model\Catalog\ProductTotals');

        $arrWidget = explode(self::WIDGET, $string);
        $dataWidgetFinal = [];
        foreach ($arrWidget as $keyWidget => $dataWidget) {
            if($dataWidget!=""){
                $widgetNodes = explode(self::SPACE_SEPARATOR, $dataWidget);
                
                foreach ($widgetNodes as $kWN => $vWN) {
                    
                    $arrWidgetNodes = explode(self::EQUAL_SEPARATOR, $vWN);
                    
                    if(isset($arrWidgetNodes[0]) && $arrWidgetNodes[0] == "conditions_encoded"){
                        $res = str_replace('conditions_encoded="', '', $vWN);
                        $res = str_replace(']"', '', $res);

                        $totals = $productTotals->getProductTotals($res);

                        $dataWidgetFinal["sku_list"] = $totals["skuList"];

                        $decodeConditions = $conditionsW->decode($res, 1);
                        $a = 0;
                        $index = 0;
                        $childIndex = 0;
                        foreach ($decodeConditions as $decodeCondition) {
                            if(isset($decodeCondition["aggregator"])){
                                $dataWidgetFinal["widget_conditions"][$a] = $decodeCondition;
                                $index = $a;
                                $childIndex = 0;
                                $a++;
                            }else{
                                $dataWidgetFinal["widget_conditions"][$index]["child"][$childIndex] = $decodeCondition;                                
                                $childIndex++;
                            }
                            
                        }
                    }else{
                        if(count($arrWidgetNodes)==2){
                            if (strpos($arrWidgetNodes[1], '"') !== false) {
                                $arrWidgetNodes[1] = str_replace('"','',$arrWidgetNodes[1]);
                            }
                            $dataWidgetFinal[$arrWidgetNodes[0]] = $arrWidgetNodes[1];
                        }
                    }                    
                }
            }
        }
        
        return $dataWidgetFinal;
    }

    public function writeLog($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        if(is_array($message)){
            $logger->info(print_r($message,true));
        }
         else {
            $logger->info($message);
        }
    }

    private function getProductCollectionByCategories($ids)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productCollectionFactory = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
        $collection = $productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addCategoriesFilter(['in' => $ids]);
        return $collection;
    }
}
