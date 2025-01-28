<?php

namespace Wow\CmsContentTranslator\Helper;

use Wow\CmsContentTranslator\Model\PageBuilderConverterToJson;
use Wow\CmsContentTranslator\Helper\ArrayBuilder;

class Category extends \Magento\Framework\App\Helper\AbstractHelper
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
    
    public function getContentById(int $catId, int $storeId): array    
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $categoryRepository = $objectManager->get('\\Magento\Catalog\Api\CategoryRepositoryInterface');
        $categoryInstance = $categoryRepository->get($catId, $storeId);
        $content['content'] = $categoryInstance->getDescription();
        return $content;
    }
    
    public function blockConverter($content)
    {
        $dataContent = $content;
        $arrConvertedContent = $this->contentConverterJson->convert($dataContent);
        $arrFinal2 = $this->arrayBuilder->process($arrConvertedContent);
        
        $arrResult = [];
        foreach ($arrFinal2 as $key => $value) {
            $arrResult[]["rows"] = $value;
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