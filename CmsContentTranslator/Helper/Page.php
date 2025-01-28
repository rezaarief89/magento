<?php

namespace Wow\CmsContentTranslator\Helper;

use Wow\CmsContentTranslator\Model\PageBuilderConverterToJson;
use Wow\CmsContentTranslator\Helper\ArrayBuilder;

class Page extends \Magento\Framework\App\Helper\AbstractHelper
{
   
    private $contentConverterJson;

    private $arrayBuilder;

    public function __construct(
        PageBuilderConverterToJson $contentConverterJson,
        ArrayBuilder $arrayBuilder
    ) {
        $this->contentConverterJson = $contentConverterJson;
        $this->arrayBuilder = $arrayBuilder;
    }
    
     /**
     * Get Content by Id
     *
     * @param int $pageId
     * @return string[]
     * @throws GraphQlInputException
     */
    public function getContentById(int $pageId): array
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $pageRepository = $objectManager->get('\Magento\Cms\Api\PageRepositoryInterface');
        $page = $pageRepository->getById($pageId);
        $content['content'] = $page->getContent();
        return $content;
    }

    /**
     * Get Content by Identifier
     *
     * @param string $pageIdentifier
     * @param int $storeId
     * @return array
     * @throws GraphQlInputException
     */
    public function getContentByIdentifier(string $pageIdentifier, int $storeId): array
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $pageByIdentifier = $objectManager->get('\Magento\Cms\Api\GetPageByIdentifierInterface');
        $page = $pageByIdentifier->execute($pageIdentifier, $storeId);
        $content['content'] = $page->getContent();
        return $content;
    }

    public function blockConverter($content)
    {
        $dataContent = $content;

        $arrConvertedContent = $this->contentConverterJson->convert($dataContent);
        $arrResult = $arrConvertedContent;

        $arrFinal2 = $this->arrayBuilder->process($arrConvertedContent);

        foreach ($arrResult as $key => $value) {
            foreach ($value as $k => &$v) {
                $newV = $value[$k];
                if(!is_array($newV)){
                    $newV = str_replace("\\","",$newV);
                    $newV = $this->arrayBuilder->replaceStringValue($newV);
                }
                unset($arrResult[$key][$k]);
                $arrResult[$key][str_replace("-","_",$k)] = $newV;
            }
            if(isset($arrFinal2[$key])){
                $arrResult[$key]["items"] = $arrFinal2[$key];
            }else{
                $arrResult[$key]["items"] = null;
            }
        }
        // $this->writeLog($arrResult);
        return array(
            "contents"=>$arrResult
        );
    }

    public function writeLog($message)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $logger = $objectManager->get('\Wow\CmsContentTranslator\Model\DataConverter\Logger');
        $logger->writeLog($message);
    }

}