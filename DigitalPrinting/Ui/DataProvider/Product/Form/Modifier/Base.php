<?php
namespace Wow\DigitalPrinting\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Field;

class Base extends AbstractModifier
{
   /**
    * @var array
    */
   protected $meta = [];

   /**
    * {@inheritdoc}
    */
   public function modifyData(array $data)
   {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $locator = $objectManager->get('\Magento\Catalog\Model\Locator\LocatorInterface');
        $storeManager = $objectManager->get("\Magento\Store\Model\StoreManagerInterface");
        $mediaUrl =  $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);


        foreach($data as &$dataP){
            if(isset($dataP["product"])){
                if(isset($dataP["product"]["options"])){
                    $productOptions = $dataP["product"]["options"];
                    // $productOptions[0]["values"][0]["sku"] = 33333333;
                    // $logger->info("dataP : ".print_r($productOptions,true));
                }
            }
            
        }

        $product   = $locator->getProduct();
        $productId = $product->getId();
        $data = array_replace_recursive(
            $data, [
                $productId => [
                    'product' => [
                        'options' => [
                            0 => [
                                'values' => [
                                    0 => [
                                        // 'file' => [
                                        //     'url' => $mediaUrl.'/catalog/product/file/about_img_1.jpg',
                                        //     'previewType' => 'image'
                                        // ]
                                        // 'file' => $mediaUrl.'/catalog/product/file/about_img_1.jpg',
                                        // 'sku' => '242424242424'
                                    ]
                                ]
                            ]
                        ]
                    ],
                ],
            ]);

        // $logger->info(" ======================================================== ");
        // $logger->info("options : ".print_r($data[$productId]["product"]["options"],true));
        // $logger->info("media_gallery : ".print_r($data[$productId]["product"]["media_gallery"],true));
        // $logger->info(" ======================================================== ");
        
       return $data;
   }

   /**
    * {@inheritdoc}
    */
   public function modifyMeta(array $meta)
   {
       $this->meta = $meta;

       $this->addFields();

    //    $this->addImageFields();

       return $this->meta;
   }

   /**
    * Adds fields to the meta-data
    */
   protected function addFields()
   {        
        $groupCustomOptionsName    = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName       = CustomOptions::CONTAINER_OPTION;

        // Add fields to the values
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children']['values']['children']['record']['children'],
            $this->getValueFieldsConfig()
        );
   }

      /**
    * Adds fields to the meta-data
    */
    protected function addImageFields()
    {        
         $groupCustomOptionsName    = CustomOptions::GROUP_CUSTOM_OPTIONS_NAME;
         $optionContainerName       = CustomOptions::CONTAINER_OPTION;
 
         // Add fields to the values
         $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
         [$optionContainerName]['children']['values']['children']['record']['children'] = array_replace_recursive(
             $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
             [$optionContainerName]['children']['values']['children']['record']['children'],
             $this->getValueFieldsAddConfig()
         );
    }

   /**
    * The custom option fields config
    *
    * @return array
    */
   protected function getValueFieldsConfig()
   {
        $fields['file'] = $this->getImageFieldConfig();
        return $fields;
   }

    /**
    * The custom option fields config
    *
    * @return array
    */
    protected function getValueFieldsAddConfig()
    {
         $fields['image_url'] = $this->getImageFieldConfigUrl();
         return $fields;
    }

   /**
    * Get description field config
    *
    * @return array
    */
   protected function getImageFieldConfig()
   {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $storeManager = $objectManager->get("\Magento\Store\Model\StoreManagerInterface");
        $mediaUrl =  $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        // echo "mediaUrl : ".$mediaUrl;exit();

        // https://coachprod.appscentral.net/media/

       return [
           'arguments' => [
               'data' => [
                    'config' => [
                        'label' => __('Image'),
                        'visible' => 'true',
                        'componentType' => 'image',
                        'formElement' => 'imageUploader',
                        'component' => 'Magento_Ui/js/form/element/image-uploader',
                        'elementTmpl' => 'ui/form/element/uploader/uploader',
                        'previewTmpl' => 'Wow_DigitalPrinting/image-preview',
                        // 'previewTmpl' => 'Magento_Catalog/image-preview',
                        'allowedExtensions' => 'jpg jpeg gif png',
                        'dataScope' => 'file',
                        // 'dataScope' => 'data.product',
                        'fileInputName' => 'image',
                        'source' => 'product',
                        'uploaderConfig' => [
                            'url' => 'printing/upload/image'
                        ],
                        'resizeConfig' => [
                            'width' => '200',
                            'height' => '200'
                        ],
                        'sortOrder' => 41,
                        // 'media_url' => $mediaUrl."/catalog/product/file/about_img_1.jpg"
                    ],
               ],
           ],
       ];
   }

      /**
    * Get description field config
    *
    * @return array
    */
    protected function getImageFieldConfigUrl()
    {
         $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
         $storeManager = $objectManager->get("\Magento\Store\Model\StoreManagerInterface");
 
         $mediaUrl =  $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
 
         // echo "mediaUrl : ".$mediaUrl;exit();
 
         // https://coachprod.appscentral.net/media/
 
        return [
            'arguments' => [
                'data' => [
                     'config' => [
                        'label' => __('Custom Title'),
                        'componentType' => Field::NAME,
                        'formElement' => Input::NAME,
                        'dataScope' => 'data.product',
                        'dataType' => Text::NAME,
                        'sortOrder' => 42,
                    ],
                ],
            ],
        ];
    }
}