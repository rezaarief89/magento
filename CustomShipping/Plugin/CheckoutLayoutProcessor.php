<?php 

namespace Fef\CustomShipping\Plugin;

use Magento\Framework\Controller\ResultFactory;

class CheckoutLayoutProcessor
{
    public function afterProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $subject, $result)
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $deliveryStepUiComponent = &$result['components']['checkout']['children']['steps']['children']['delivery-step'];

        $deliverySlotAttributes = $this->getDeliverySlotAttributes();
        $deliveryStepUiComponent['children']['deliveryContent']['children']['delivery_slot'] = $this->getAdditionalDeliveryComponent($deliverySlotAttributes,'delivery-slot');
        $deliveryStepUiComponent['children']['deliveryContent']['children']['delivery_slot']['children']['delivery_slot']['placeholder'] = __('Please select timeslot');
        $deliveryStepUiComponent['children']['deliveryContent']['children']['delivery_slot']['sortOrder'] = 5;
        
        $deliveryStairsOptions = $this->getDeliveryStairsOption();
        // $logger->info(print_r($deliveryStairsOptions,true));

        if(!empty($deliveryStairsOptions)){
            $deliveryStairsAttributes = $this->getDeliveryStairsAttributes($deliveryStairsOptions);
            $deliveryStepUiComponent['children']['deliveryContent']['children']['delivery_stairs'] = $this->getAdditionalDeliveryComponent($deliveryStairsAttributes,'delivery-stairs');
            $deliveryStepUiComponent['children']['deliveryContent']['children']['delivery_stairs']['children']['delivery_stairs']['placeholder'] = __('Enter building level');
            $deliveryStepUiComponent['children']['deliveryContent']['children']['delivery_stairs']['sortOrder'] = 6;   
        }

        return $result;
    }

    /**
     * @return array
     */
    private function getDeliverySlotAttributes()
    {

        $elements['delivery_slot']=$this->addFieldToLayout('delivery_slot', [
            'dataType' => 'select',
            'formElement' => 'select',
            'sortOrder' => 25,
            'validation' => [
                'required-entry' => true
            ],
            "options" => [
                ['value' => '', 'label' => __('Please select timeslot')]
            ]
        ]);


        return $elements;
    }

     /**
     * @return array
     */
    private function getDeliveryStairsAttributes($deliveryStairsOptions)
    {
        $options = [];
        // $deliveryStairsOptions = $this->getDeliveryStairsOption();

        $options[0] = [
            'value' => '', 
            'label' => __('Please select building level')
        ];
        $k = 1;
        foreach ($deliveryStairsOptions as $key => $value) {
            $options[$k] = [
                'value' => $value, 
                'label' => __($value)
            ];
            $k++;
        }

        $elements['delivery_stairs'] = $this->addFieldToLayout('delivery_stairs', [
            'dataType' => 'select',
            'formElement' => 'select',
            'sortOrder' => 26,
            'validation' => [
                'required-entry' => true
            ],
            "options" => $options
        ]);

        

        return $elements;
    }

    /**
     * @param $elements
     * @return array
     */
    protected function getAdditionalDeliveryComponent($elements,$elCode)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $merge = $objectManager->get('\Magento\Checkout\Block\Checkout\AttributeMerger');

        $components = [
            'component' => 'uiComponent',
            'displayArea' => $elCode,
            'children' => $merge->merge(
                $elements,
                'checkoutProvider',
                $elCode,
                []
            )
        ];

        return $components;
    }

    /**
     * @param string $customAttributeCode
     * @param array $customField
     * @return array
     */
    protected function addFieldToLayout($customAttributeCode = 'custom_field', $customField = [])
    {
        return array_merge([
            'component' => 'Magento_Ui/js/form/element/date',
            'config' => [
                'customScope' => 'shippingAddress.custom_attributes',
                'customEntry' => null,
                'template' => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/date'
            ],
            'dataScope' => 'shippingAddress.custom_attributes' . '.' . $customAttributeCode,
            'label' => '',
            'provider' => 'checkoutProvider',
            'sortOrder' => 0,
            'validation' => [
                'required-entry' => true
            ],
            'options' => [],
            'filterBy' => null,
            'customEntry' => null,
            'visible' => true,
            'value' => ''
        ], $customField);
    }

    private function getDeliveryStairsOption()
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->get("\Fef\CustomShipping\Helper\Data");
        $outletId = $helper->getConfig("carriers/custom/outlet_id");


        $dataBuilding = [];
        try {

            $url = $helper->getConfig("carriers/custom/base_url")."delivery-provider/custom-fields/default";
            $resGetBuildingResult = $helper->setCurl($url,"GET",null,1);

            $resGetBuildingResultArray = json_decode($resGetBuildingResult,true);

            // $logger->info("resGetBuildingResultArray : ".print_r($resGetBuildingResultArray,true));

            if($resGetBuildingResultArray["status"]=="success"){
                if($resGetBuildingResultArray["data"]["customFields"]){
                    $customFields = $resGetBuildingResultArray["data"]["customFields"];
                    foreach ($customFields as $key => $value) {
                        if($value["value"]=="staircase"){
                            $dataBuilding = $value["options"];
                        }
                    }
                }
            }

            // $logger->info("dataBuilding : ".print_r($dataBuilding,true));
            
        } catch (\Exception $ex) {
            $logger->info("exception : ".$ex->getMessage());
        }

        return $dataBuilding;
        
    }
}