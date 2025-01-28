<?php
namespace Wow\ExcludeDiscountProduct\Block;

use Magento\Store\Model\ScopeInterface;

class Product extends \Magento\Framework\View\Element\Template
{
	public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Magento\Customer\Model\Session $customer,
        \Magento\Customer\Model\Visitor $customerVisitor,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->session = $session;
        $this->customer = $customer;
        $this->_customerVisitor = $customerVisitor;
        $this->storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function adjustCollection($collection)
    {
        if($this->getCurrentStore()->getCode() == "coachsgrt_en"){
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $registry = $objectManager->get('\Magento\Framework\Registry');
    
            $category = $registry->registry('current_category');    
            if($category != NULL){
                $catId = $category->getId();
                $exclCat = $this->getExcludeCategory();
                if($exclCat && $exclCat == $catId){
                    // $logger->info("exclCat : $exclCat || catId : $catId");
                    $exclProductId = [];
                    $inclProductId = [];
                    $inclProductSku = [];
                    foreach ($collection as $product) {
                        if($product->getTypeId() == "configurable"){
                            $childProducts = $product->getTypeInstance()->getUsedProducts($product);
                            foreach ($childProducts as $simpleProduct){
                                $orgprice = $simpleProduct->getPrice();
                                $finalPrice = $simpleProduct->getFinalPrice();
                                if($finalPrice >= $orgprice){
                                    if(!in_array($product->getId(),$exclProductId)){
                                        array_push($exclProductId,$product->getId());
                                    }
                                }else{
                                    if(!in_array($product->getId(),$inclProductId)){
                                        array_push($inclProductId,$product->getId());
                                        array_push($inclProductSku,$product->getSku()." : ".$product->getName());   
                                    }
                                }
                                
                            }
                        }else{
                            $orgprice = $product->getPrice();
                            $finalPrice = $product->getFinalPrice();
                            if($finalPrice >= $orgprice){
                                if(!in_array($product->getId(),$exclProductId)){
                                    array_push($exclProductId, $product->getId());
                                }
                            }else{
                                if(!in_array($product->getId(),$inclProductId)){
                                    array_push($inclProductId,$product->getId());
                                    array_push($inclProductSku,$product->getSku()." : ".$product->getName());   
                                }
                            }
                        }
                    }
                    $collection->addAttributeToFilter('entity_id', array('in' => $inclProductId));
                }
            } 
        }
        
        return $collection;
    }

    public function getExcludeCategory()
    {
        return $this->_scopeConfig->getValue('wowexclude/product/category_id', ScopeInterface::SCOPE_STORES, $this->getCurrentStore()->getId());
    }

    public function getCurrentStore()
    {
        return $this->storeManager->getStore();
    }
}