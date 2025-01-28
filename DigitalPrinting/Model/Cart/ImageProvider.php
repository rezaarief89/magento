<?php

namespace Wow\DigitalPrinting\Model\Cart;

use Magento\Checkout\CustomerData\DefaultItem;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;

class ImageProvider extends \Magento\Checkout\Model\Cart\ImageProvider
{
    /**
     * @var \Magento\Quote\Api\CartItemRepositoryInterface
     */
    protected $itemRepository;

    /**
     * @var \Magento\Checkout\CustomerData\ItemPoolInterface
     * @deprecated 100.2.7 No need for the pool as images are resolved in the default item implementation
     * @see \Magento\Checkout\CustomerData\DefaultItem::getProductForThumbnail
     */
    protected $itemPool;

    /**
     * @var \Magento\Checkout\CustomerData\DefaultItem
     * @since 100.2.7
     */
    protected $customerDataItem;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    private $imageHelper;

    /**
     * @var \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface
     */
    private $itemResolver;

    private $storeManager;

    protected $resourceConnection;

    protected $optionId = "";

    /**
     * @param \Magento\Quote\Api\CartItemRepositoryInterface $itemRepository
     * @param \Magento\Checkout\CustomerData\ItemPoolInterface $itemPool
     * @param DefaultItem|null $customerDataItem
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface $itemResolver
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Quote\Api\CartItemRepositoryInterface $itemRepository,
        \Magento\Checkout\CustomerData\ItemPoolInterface $itemPool,
        \Magento\Checkout\CustomerData\DefaultItem $customerDataItem = null,
        \Magento\Catalog\Helper\Image $imageHelper = null,
        \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface $itemResolver = null,
        StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        
        $this->itemRepository = $itemRepository;
        $this->itemPool = $itemPool;
        $this->customerDataItem = $customerDataItem ?: ObjectManager::getInstance()->get(DefaultItem::class);
        $this->imageHelper = $imageHelper ?: ObjectManager::getInstance()->get(\Magento\Catalog\Helper\Image::class);
        $this->itemResolver = $itemResolver ?: ObjectManager::getInstance()->get(
            \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface::class
        );
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        return parent::__construct(
            $itemRepository,
            $itemPool,
            $customerDataItem,
            $imageHelper,
            $itemResolver
        );
    }

    /**
     * {@inheritdoc}
     */

     //This custom for checkout page summary section
    public function getImages($cartId)
    {
        $itemData = [];
        $items = $this->itemRepository->getList($cartId);
        foreach ($items as $cartItem) {
            $itemData[$cartItem->getItemId()] = $this->getProductImageData($cartItem);
        }
        return $itemData;
    }

    /**
     * Get product image data
     *
     * @param \Magento\Quote\Model\Quote\Item $cartItem
     *
     * @return array
     */
    private function getProductImageData($cartItem)
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('catalog_product_option_type_value');
        $tablePrice = $connection->getTableName('catalog_product_option_type_price');

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode(); 
        $currency = $objectManager->create('Magento\Directory\Model\CurrencyFactory')->create()->load($currencyCode); 
       // $currencySymbol = trim($currency->getCurrencySymbol());
        $currencySymbol = ($currency->getCurrencySymbol()!="") ? trim($currency->getCurrencySymbol()) : "";

        $imageAttribute = "";        
        $imageHelper = $this->imageHelper->init(
            $this->itemResolver->getFinalProduct($cartItem),
            'mini_cart_product_thumbnail'
        );
        if(!$this->isTwSite()){
            $imageData = [
                'src' => $imageHelper->getUrl(),
                'alt' => $imageHelper->getLabel(),
                'width' => $imageHelper->getWidth(),
                'height' => $imageHelper->getHeight()
            ];
            return $imageData;
        }

        $storeId = $this->storeManager->getStore()->getId();
        $priceAttribute = "";

        $imageUrl = $imageHelper->getUrl();
        $optionId = "";

        if($cartItem->getOptions()!=NULL){
            foreach ($cartItem->getOptions() as $option) {
                if ($option->getCode() == 'option_ids') {
                    $optionId = $option->getValue();
                    if($optionId!=""){
                        $this->optionId = $optionId;
                    }
                }

                if($option->getCode()=="info_buyRequest"){

                    $optionValues = json_decode($option->getValue(),true);
                    
                    if(isset($optionValues["options"]) || isset($optionValues["options"][$this->optionId])){
                        
                        foreach ($optionValues["options"] as $valueOpt) {
                            if(gettype($valueOpt)=="array"){
                                if(isset($valueOpt[0])){
                                    $optionTypeId = $valueOpt[0];
                                }
                            }else{
                                $optionTypeId = $valueOpt;
                            }                            
                        }
                        

                        if($optionTypeId != ""){
                            $query = "SELECT `image` FROM `" . $table . "` WHERE option_type_id = $optionTypeId ";
                            $resultQuery = $connection->fetchAll($query);
                            if(count($resultQuery) > 0){
                                $imageAttribute = $resultQuery[0]["image"];
                            }

                            $queryPrice = "SELECT `price` FROM `" . $tablePrice . "` WHERE option_type_id = $optionTypeId and store_id = ".$storeId;
                            $resultQueryPrice = $connection->fetchAll($queryPrice);
                            if(count($resultQueryPrice) > 0){
                                $priceAttribute = (int)$resultQueryPrice[0]["price"];
                            }
                        }
                    }
                }
            }
        }

        if($imageAttribute!="" && $imageAttribute!=NULL && $optionId != ""){
            $imageUrl = $baseUrl."media/catalog/product/file/".$imageAttribute;
        }

        $price = "";
        if($priceAttribute!="" && $priceAttribute!=NULL && $optionId != ""){
            // $price = " ($currencySymbol$priceAttribute)";
        }
        
        $imageData = [
            'src' => $imageUrl,
            'alt' => $imageHelper->getLabel(),
            'width' => $imageHelper->getWidth(),
            'height' => $imageHelper->getHeight(),
            'price' => $price
        ];
        return $imageData;
    }

    protected function isTwSite(): bool
    {
        $storeCode = $this->storeManager->getStore()->getCode();
        return ($storeCode == "coachtw_tw");
    }
}
