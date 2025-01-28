<?php 
namespace Fef\CustomerSso\Helper;

use Magento\Framework\App\Helper\Context;
use Fef\CustomShipping\Model\FefTokenFactory;
use Fef\CustomShipping\Helper\Data;
use Magento\Framework\ObjectManagerInterface;

class Token extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function __construct(
        FefTokenFactory $modelFefTokenFactory,
        Data $helper,
        ObjectManagerInterface $objectManager
    ) {
        $this->_objectManager = $objectManager;
        $this->_modelFefTokenFactory = $modelFefTokenFactory;
        $this->_helper = $helper;
        
    }

    public function CheckToken()
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/token.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        //GET AUTH LOGIN
        // $modelFefTokenFactory = $this->_objectManager->get('\Fef\CustomShipping\Model\FefTokenFactory');
        $modelFefToken = $this->_modelFefTokenFactory->create();
        
        $collection = $this->_helper->getTokenCollection();
        $countToken = count($collection->getData());
        $token = "";
        if($countToken==0){
            // insert auto by cred
            $authReq = $this->_helper->callAuthApi("login");
            if($this->_helper->getDebugMode()==1){
                $logger->info("LOGIN API RESULT : ".$authReq);
            }
            $authReqArray = json_decode($authReq,true);
            if($authReqArray["status"]=="success"){
                if(!empty($authReqArray["data"]["expiry"])){
                    $token = $authReqArray["data"]["token"];
                    $modelFefToken->setToken($token);
                    $modelFefToken->setRefreshToken($authReqArray["data"]["refreshToken"]);
                    $modelFefToken->setExpiry($authReqArray["data"]["expiry"]);
                    $modelFefToken->save();
                    if($this->_helper->getDebugMode()==1){
                        $logger->info("ADDED TOKEN ".$token." AND EXPIRED IN : ".$authReqArray["data"]["expiry"]);
                    }
                }
            }
        } else {
            // check token is expired or not
            // if expired then call API refresh token

            // DIsabled process re-generate token
            $dataCollection = $collection->getData();
            foreach ($dataCollection as $key => $collection) {
            //     $id = $collection["id"];
            //     $expiry = $collection["expiry"];
            //     $expiry = str_replace("T"," ",$expiry);
            //     $expiry = str_replace(".000Z","",$expiry);
            //     $expiryStrTime = strtotime($expiry);
            //     $timeNow = time() + (7*60*60);//GMT +7

            //     $timefromdatabase = $expiryStrTime;
            //     $dif = $timeNow - $timefromdatabase;

            //     if($dif > 86400)
            //     {
            //         $logger->info("MORE THAN 24 HOURS");
            //         $authReq = $this->_helper->callAuthApi("login");
            //         if($this->_helper->getDebugMode()==1){
            //             $logger->info("LOGIN API RESULT : ".$authReq);
            //         }
            //         $authReqArray = json_decode($authReq,true);
            //         if($authReqArray["status"]=="success"){
            //             if(!empty($authReqArray["data"]["expiry"])){
            //                 $this->updateToken($id,$modelFefToken,$authReqArray);
            //                 if($this->_helper->getDebugMode()==1){
            //                     $logger->info("ADDED TOKEN ".$token." AND EXPIRED IN : ".$authReqArray["data"]["expiry"]);
            //                 }
            //             }
            //         }
            //     }else{
            //         $logger->info("LESS THAN 24 HOURS");
            //         //  call API refresh token
            //         $paramToken = array(
            //             "refreshToken" => $collection["refresh_token"]
            //         );
            //         // $logger->info("paramToken : ".print_r($paramToken,true));
            //         $authReq = $this->_helper->callAuthApi("refresh",$paramToken);
            //         $authReqArray = json_decode($authReq,true);
            //         if($this->_helper->getDebugMode()==1){
            //             $logger->info("UPDATE AUTH API RESULT : ".$authReq);
            //         }

            //         if(isset($authReqArray["status"]) && $authReqArray["status"]=="success"){
            //             $expiryData = $authReqArray["data"]["expiry"];
            //             if(!empty($expiryData)){
            //                 $this->updateToken($id,$modelFefToken,$authReqArray);
            //                 if($this->_helper->getDebugMode()==1){
            //                     $logger->info("TOKEN UPDATED TO ".$token ." AND REFRESH TOKEN UPDATED TO ".$authReqArray["data"]["refreshToken"]." AND EXPIRY UPDATED TO : ".$authReqArray["data"]["expiry"]);
            //                 }
            //             }
            //         }
            //     }
                $token = $collection["token"];
            }
        }
    }

    private function updateToken($id,$modelFefToken,$dataResult)
    {
        $token = $dataResult["data"]["token"];
        $postUpdate = $modelFefToken->load($id);
        $postUpdate->setToken($token);
        $postUpdate->setRefreshToken($dataResult["data"]["refreshToken"]);
        $postUpdate->setExpiry($dataResult["data"]["expiry"]);
        $postUpdate->save();
    }
}