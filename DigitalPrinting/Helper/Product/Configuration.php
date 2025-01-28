<?php

namespace Wow\DigitalPrinting\Helper\Product;

use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Escaper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Catalog\Helper\Product\Configuration\ConfigurationInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Helper for fetching properties by product configurational item
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Configuration extends \Magento\Catalog\Helper\Product\Configuration
{
    /**
     * Filter manager
     *
     * @var \Magento\Framework\Filter\FilterManager
     */
    protected $filter;

    /**
     * @var \Magento\Catalog\Model\Product\OptionFactory
     */
    protected $_productOptionFactory;

    /**
     * Magento string lib
     *
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory
     * @param \Magento\Framework\Filter\FilterManager $filter
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param Json $serializer
     * @param Escaper $escaper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Framework\Filter\FilterManager $filter,
        \Magento\Framework\Stdlib\StringUtils $string,
        Json $serializer = null,
        Escaper $escaper = null
    ) {
        $this->_productOptionFactory = $productOptionFactory;
        $this->filter = $filter;
        $this->string = $string;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
        $this->escaper = $escaper ?: ObjectManager::getInstance()->get(Escaper::class);
        parent::__construct($context, $productOptionFactory,$filter,$string,$serializer,$escaper);
    }


    /**
     * Retrieves product configuration options
     *
     * @param ItemInterface $item
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */

    // changes in cart page
    public function getCustomOptions(ItemInterface $item) //phpcs:ignore Generic.Metrics.NestingLevel
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        

        $product = $item->getProduct();
        $options = [];
        $optionIds = $item->getOptionByCode('option_ids');
        $priceAttribute = "";

        $resourceConnection = ObjectManager::getInstance()->get(ResourceConnection::class);
        $storeManager = ObjectManager::getInstance()->get(StoreManagerInterface::class);

        $connection = $resourceConnection->getConnection();
        $table = $connection->getTableName('catalog_product_option_type_value');
        $tablePrice = $connection->getTableName('catalog_product_option_type_price');

        $storeId = $storeManager->getStore()->getId();
        $currencyCode = $storeManager->getStore()->getCurrentCurrency()->getCode(); 
        $currency = ObjectManager::getInstance()->create('Magento\Directory\Model\CurrencyFactory')->create()->load($currencyCode); 
        $currencySymbol = ($currency->getCurrencySymbol()!="") ? trim($currency->getCurrencySymbol()) : "";
        
        if ($optionIds && $optionIds->getValue()) {
            $options = [];
            foreach (explode(',', $optionIds->getValue()) as $optionId) {

                // $logger->info("optionId : ".$optionId);

                $option = $product->getOptionById($optionId);
                if ($option) {
                    $itemOption = $item->getOptionByCode('option_' . $option->getId());

                    /** @var $group \Magento\Catalog\Model\Product\Option\Type\DefaultType */
                    $group = $option->groupFactory($option->getType())
                        ->setOption($option)
                        ->setConfigurationItem($item)
                        ->setConfigurationItemOption($itemOption);

                    if ('file' == $option->getType()) {
                        $downloadParams = $item->getFileDownloadParams();
                        if ($downloadParams) {
                            $url = $downloadParams->getUrl();
                            if ($url) {
                                $group->setCustomOptionDownloadUrl($url);
                            }
                            $urlParams = $downloadParams->getUrlParams();
                            if ($urlParams) {
                                $group->setCustomOptionUrlParams($urlParams);
                            }
                        }
                    }

                    $skuOption = explode("-",$product->getSku())[1];
                    // $optionId = $option["option_id"];
                    $query = "SELECT option_type_id, sku, `image` FROM `" . $table . "` WHERE option_id = $optionId and sku = '".$skuOption."'";
                    $resultQuery = $connection->fetchAll($query);
                    if(count($resultQuery) > 0){
                        $optionTypeId = $resultQuery[0]["option_type_id"];
                        $queryPrice = "SELECT `price` FROM `" . $tablePrice . "` WHERE option_type_id = $optionTypeId and store_id = ".$storeId;
                        $resultQueryPrice = $connection->fetchAll($queryPrice);
                        if(count($resultQueryPrice) > 0){
                            $priceAttribute = (int)$resultQueryPrice[0]["price"];
                        }
                    }

                    $optValue = $group->getFormattedOptionValue($itemOption->getValue());
                    if($priceAttribute !=""){
                        $optValue = $group->getFormattedOptionValue($itemOption->getValue())." $currencySymbol$priceAttribute)";
                        // $logger->info("optValue : ".$optValue);
                    }

                    $options[] = [
                        'label' => $option->getTitle(),
                        'value' => $group->getFormattedOptionValue($itemOption->getValue()),
                        // 'value' => $optValue,
                        'print_value' => $group->getPrintableOptionValue($itemOption->getValue()),
                        // 'print_value' => $optValue,
                        'option_id' => $option->getId(),
                        'option_type' => $option->getType(),
                        'custom_view' => $group->isCustomizedView(),
                    ];

                }
            }
        }

        $addOptions = $item->getOptionByCode('additional_options');
        if ($addOptions) {
            $options = array_merge($options, $this->serializer->unserialize($addOptions->getValue()));
        }

        return $options;
    }

    protected function isTwSite(): bool
    {
        $storeManager = ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $storeCode = $storeManager->getStore()->getCode();
        return ($storeCode == "coachtw_tw");
    }
}
