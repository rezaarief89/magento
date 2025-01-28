<?php

namespace Wow\CmsContentTranslator\Helper;

class ArrayBuilder extends \Magento\Framework\App\Helper\AbstractHelper
{

    const IMG_MEDIA = '{{media url=';
    const MEDIA_QUOTE = '&quot;';
    const WIDGET = '{{widget type=';
    const LT = "&lt;";
    const GT = "&gt;";

    public function process($arrConvertedContent)
    {
        $arrFinal2 = [];
        foreach ($arrConvertedContent as $k1 => $v1) {           

            $dIt1 = $v1["items"];
            $flagConvertToArray = 1;
            foreach ($dIt1 as $kF1 => $valF1) {

                if($valF1["data-content-type"]=="html"){
                    $flagConvertToArray = 0;
                }

                if(is_array($dIt1[$kF1])){
                    $tmpArrs = $this->checkAndMakeArray($arrFinal2, $valF1, $flagConvertToArray);
                    $arrFinal2[$k1][$kF1] = $tmpArrs;

                    // $this->writeLog("k1 : $k1, kF1 : $kF1");
                }else{
                    $newKF1 = str_replace("-","_",$kF1);
                    // $this->writeLog("kF1 : $kF1, newKF1 : $newKF1");
                    $arrFinal2[$kF1][$newKF1] = $this->replaceStringValue($dIt1[$kF1]);
                }                
            }
        }
        
        return $arrFinal2;
    }

    
    public function replaceStringValue($fullString, $flagConvertToArray = 1)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');

        $baseUrl = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        $newString = $fullString;

        
        if($flagConvertToArray==0){
            return $newString;
        }
        
        if (strpos($newString, self::IMG_MEDIA) !== false) {
            $res = str_replace( self::IMG_MEDIA, $baseUrl, $newString); 
            $resQ = str_replace( '"}}', '', $res); 
            $newString = str_replace( '}}', '', $resQ); 
        }

        if (strpos($newString, self::MEDIA_QUOTE) !== false) {
            $res = str_replace( '{{media url=&quot;', $baseUrl, $newString); 
            $newString = str_replace( '&quot;}}', '', $res);
        }

        if (strpos($newString, self::LT) !== false) {
            $newString = str_replace( self::LT, "<", $newString); 
        }

        if (strpos($newString, self::GT) !== false) {
            $newString = str_replace( self::GT, ">", $newString); 
        }

        if (strpos($newString, "}}") !== false) {
            $newString = str_replace( "}}", "", $newString); 
        }

        if ($newString == "{}") {
            $newString = str_replace( "{}", '"{}"', $newString); 
        }
        
        $isJson = json_validate($newString);
        if($isJson==true){
            $newString = json_decode($newString,true);
        }

        return $newString;
    }

    public function getStringBetween($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;

        return substr($string, $ini, $len);
    }

    public function checkAndMakeArray($arrFinal, $data, $flagConvertToArray)
    {
        $arrFinal = [];
        foreach ($data as $kValFinal => $vValFinal) {
            if(is_array($vValFinal)){
                $tmpArray = $this->checkAndMakeArray($arrFinal, $vValFinal, $flagConvertToArray);
                if(is_string($kValFinal)){
                    $arrFinal[str_replace("-","_",$kValFinal)] = $tmpArray;
                }else{                    
                    foreach ($tmpArray as $keyTmp => &$valTmp) {
                        if(!is_array($valTmp)){
                            $valTmp = str_replace("\\","",$valTmp);
                            $valTmp = $this->replaceStringValue($valTmp);
                        }
                    }
                    array_push($arrFinal,$tmpArray);
                }
            }else{
                // $this->writeLog("flagConvertToArray : ".$flagConvertToArray);
                $arrFinal[str_replace("-","_",$kValFinal)] = $this->replaceStringValue($vValFinal, $flagConvertToArray);
            }
        }
        return $arrFinal;
    }

    
    public function writeLog($message)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $logger = $objectManager->get('\Wow\CmsContentTranslator\Model\DataConverter\Logger');
        $logger->writeLog($message);
    }

}