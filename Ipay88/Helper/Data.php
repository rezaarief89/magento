<?php

namespace Wow\Ipay88\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $configHelper;

    private $apiData = [];

    public function __construct(
        \Ipay88\Ipay88\Helper\Config $configHelper
    )
    {
        $this->configHelper = $configHelper;
    }

    public function setData($data)
    {
        $this->apiData = $data;
    }

    public function callApi()
    {
        
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        // $logger->info("helper data");

        $merchantCode = $this->configHelper->getConfig("ipay88_app_merchant_code");
        $merchantKey = $this->configHelper->getConfig("ipay88_app_merchant_key");
        

        // RawSignature = MerchantKey + MerchantCode + iPayRef +  RefNo + RefundAmount + RefundCurrency
        // hash_hmac('sha256', 'The quick brown fox jumped over the lazy dog.');
        // $signature = "";
        
        
        $endpointUrl = $this->configHelper->getConfig("endpoint_url");
        // $logger->info("endpointUrl : $endpointUrl, merchantCode : $merchantCode, merchantKey : $merchantKey");
        

        $apiParams = array(
            "MerchantCode" => $merchantCode,
            "RequestType" => "10",
            "IpayId" => "TR2435936073468928",
            "RefNo" => "MEG-DEV20240717075402",
            "RefundAmount" => (string)"1.00",
            "RefundCurrency" => "MYR",
            "Remark" => "Refund Request",
            "Verification" => array(
                "SignatureType" => "HMACSHA512",
                "Signature" => "",
            ),
        );

        if(isset($this->apiData["RefNo"])){
            $apiParams["RefNo"] = $this->apiData["RefNo"];
        }

        if(isset($this->apiData["RefundAmount"])){
            $apiParams["RefundAmount"] = (string)$this->apiData["RefundAmount"];
        }

        if(isset($this->apiData["IpayId"])){
            $apiParams["IpayId"] = (string)$this->apiData["IpayId"];
        }

        if(isset($this->apiData["RefundCurrency"])){
            $apiParams["RefundCurrency"] = (string)$this->apiData["RefundCurrency"];
        }
        $amount = str_replace(",","",$apiParams["RefundAmount"]);
        $amount = str_replace(".","",$amount);

        $rawSignature = $merchantKey.$merchantCode.$apiParams["IpayId"].$apiParams["RefNo"].$amount.$apiParams["RefundCurrency"];
        $signature = hash_hmac("sha512",$rawSignature,$merchantKey);
        
        // $logger->info("rawSignature : $rawSignature, signature : $signature");

        $apiParams["Verification"]["Signature"] = $signature;

        $apiResponse = $this->setCurl($endpointUrl, "POST", $apiParams);
        $logger->info("apiResponse : ".print_r($apiResponse,true));

        return 1;

    }

    public function callRequeryApi()
    {
        
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        // $logger->info("helper callRequeryApi");

        $merchantCode = $this->configHelper->getConfig("ipay88_app_merchant_code");
        $merchantKey = $this->configHelper->getConfig("ipay88_app_merchant_key");

        $endpointUrl = "https://payment.ipay88.com.my/epayment/enquiry.asp";

        

        $apiParams = array(
            "MerchantCode" => $merchantCode,
            "RefNo" => "1001140619",
            "Amount" => number_format(1,2),
            "Verification" => array(
                "SignatureType" => "HMACSHA512",
                "Signature" => "d3d347a158371d08eb01084176c79b45c602eb234e5b3f42b65938a3333863d00d4f444d6ad6c5b3f4825d3fd2051a9b3dda8768e9e0226bdf49feddbdd7d76",
            ),
        );

        if(isset($this->apiData["RefNo"])){
            $apiParams["RefNo"] = $this->apiData["RefNo"];
        }

        if(isset($this->apiData["RefundAmount"])){
            $apiParams["Amount"] = (string)$this->apiData["Amount"];
        }
        // $amount = str_replace(",","",$apiParams["Amount"]);
        // $amount = str_replace(".","",$amount);

        $rawSignature = $merchantKey.$merchantCode.$apiParams["RefNo"].$apiParams["Amount"];
        $signature = hash_hmac("sha512",$rawSignature, $merchantKey);
        
        // $logger->info("rawSignature : $rawSignature, signature : $signature");

        $apiParams["Verification"]["Signature"] = $signature;

        $endpointUrl .= "?MerchantCode=" .$merchantCode . "&RefNo=" . $apiParams["RefNo"] . "&Amount=" . $apiParams["Amount"];
        
        // $apiResponse = $this->setCurl($endpointUrl, "POST");
        // $logger->info("apiResponse : ".print_r($apiResponse,true));
        
        $query= "https://payment.ipay88.com.my/epayment/enquiry.asp?MerchantCode=" .
        $merchantCode . "&RefNo=" . $apiParams["RefNo"] . "&Amount=" . $apiParams["Amount"];
        $url = parse_url($query);
        $host = $url["host"];
        $sslhost = "ssl://".$host;
        $path = $url["path"] . "?" . $url["query"];
        $timeout = 5;
        $fp = fsockopen ($sslhost, 443, $errno, $errstr, $timeout); 
        if ($fp) {
            $buf = '';
            fputs ($fp, "GET $path HTTP/1.0\nHost: " . $host . "\n\n"); 
            while (!feof($fp)) {
                $buf .= fgets($fp, 128);
            }
            $lines = preg_split("/\n/", $buf);
            $Result = $lines[count($lines)-1]; fclose($fp);
        } else {
            # enter error handing code here
        }

        $logger->info("Result : ".print_r($Result,true));

        

        return 1;

    }

    public function setCurl($url, $method, $param = null)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        // $logger->info("param : ".print_r($param,true));

        $logger->info("url : $url");
        $logger->info("method : $method");
        $logger->info("payload : ".json_encode($param));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($param != null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param));
        }

        $header = array(
            "accept: */*",
            "accept-language: en-US,en;q=0.8",
            "content-type: application/json"
        );

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            $header
        );
        $result = curl_exec($ch);
        return $result;
    }

}
