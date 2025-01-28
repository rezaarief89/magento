<?php
 
namespace Fef\CustomShipping\Model\Carrier;
 
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Psr\Log\LoggerInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Catalog\Model\Product;
 
class Custom extends AbstractCarrier implements CarrierInterface
{
 
    protected $_code = 'custom';
 
    protected $rateResultFactory;
 
    protected $rateMethodFactory;
 
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        Product $product,
        array $data = []
    )
    {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->product = $product;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }
 
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }
 
    public function collectRates(RateRequest $request)
    {        
        $request = $this->excludeVirtual($request);
        $request = $this->excludeDownloadable($request);

        if (!$this->getConfigFlag('active')) {
            return false;
        }
        
        $result = $this->setMethod();
        return $result;
    }

    private function excludeVirtual($request){
        if (!$this->getConfigFlag('use_virtual_product') && $request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
                
                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    
                    foreach ($item->getChildren() as $child) {
                        if ($child->getProduct()->isVirtual()) {
                            $request->setPackageValue($request->getPackageValue() - $child->getBaseRowTotal());
                        }
                    }
                } elseif ($item->getProduct()->getTypeId() == 'virtual') {
                    $request->setPackageValue($request->getPackageValue() - $item->getBaseRowTotal());
                }
            }
        }
        return $request;
    }

    private function excludeDownloadable($request){
        if (!$this->getConfigFlag('use_download_product') && $request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getParentItem()) {
                    continue;
                }                
                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {       
                        if ($child->getProduct()->getTypeId()=='downloadable') {
                            $request->setPackageValue($request->getPackageValue() - $child->getBaseRowTotal());
                        }
                    }
                } elseif ($item->getProduct()->getTypeId()=='downloadable') {
                    $request->setPackageValue($request->getPackageValue() - $item->getBaseRowTotal());
                }
            }
           
        }
        return $request;
    }

    private function getShippingList(){
        return array  
        (  
            array(
                "method_code" => "standard",
                "name" => "standard",
                "title" => "Standard Delivery",
                "price" => 0
            )
            // ,
            // array(
            //     "method_code" => "express",
            //     "name" => "express",
            //     "title" => "Express Delivery",
            //     "price" => 0
            // )
        );
    }

    private function setMethod(){
        $listShipping = $this->getShippingList();

        $result = $this->rateResultFactory->create();
        
        foreach ($listShipping as $key => $value) {
            $method = $this->rateMethodFactory->create();
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title')." (".$listShipping[$key]["name"].")");
            $method->setMethod($listShipping[$key]["method_code"]);
            $method->setMethodTitle($listShipping[$key]["title"]);
            $method->setMethodDescription($listShipping[$key]["title"]);
            $method->setPrice($listShipping[$key]["price"]);
            $method->setCost($listShipping[$key]["price"]);
            $result->append($method);
        }
        return $result;
    }
}