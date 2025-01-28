<?php

namespace Fef\CustomShipping\Block\Checkout;

use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\Address as AddressHelper;
use Magento\Customer\Model\Session;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class AttributeMerger extends \Smartosc\Checkout\Block\Checkout\AttributeMerger
{
    /**
     * Map form element
     *
     * @var array
     */
    protected $formElementMap = [
        'checkbox'    => 'Magento_Ui/js/form/element/select',
        'select'      => 'Magento_Ui/js/form/element/select',
        'textarea'    => 'Magento_Ui/js/form/element/textarea',
        'multiline'   => 'Magento_Ui/js/form/components/group',
        'multiselect' => 'Magento_Ui/js/form/element/multiselect',
        'image' => 'Magento_Ui/js/form/element/media',
        'file' => 'Magento_Ui/js/form/element/media',
        'smart_date' => 'Smartosc_Checkout/js/form/element/smart_date',
        'smart_text' => 'Fef_CustomShipping/js/form/element/smart_date'
    ];

    /**
     * @var array
     */
    protected $templateMap = [
        'image' => 'media',
        'file' => 'media',
        'smart_date' => 'Smartosc_Checkout/js/form/element/smart_date'
    ];

    /**
     * @var AddressHelper
     */
    private $addressHelper;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var CustomerInterface
     */
    private $customer;

    /**
     * @var \Magento\Directory\Helper\Data
     */
    private $directoryHelper;

    /**
     * List of codes of countries that must be shown on the top of country list
     *
     * @var array
     */
    private $topCountryCodes;

    /**
     * AttributeMerger constructor.
     * @param AddressHelper $addressHelper
     * @param Session $customerSession
     * @param CustomerRepository $customerRepository
     * @param DirectoryHelper $directoryHelper
     */
    public function __construct(AddressHelper $addressHelper, Session $customerSession, CustomerRepository $customerRepository, DirectoryHelper $directoryHelper)
    {

        $this->addressHelper = $addressHelper;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->directoryHelper = $directoryHelper;
        $this->topCountryCodes = $directoryHelper->getTopCountryCodes();
        parent::__construct($addressHelper, $customerSession, $customerRepository, $directoryHelper);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFieldConfig(
        $attributeCode,
        array $attributeConfig,
        array $additionalConfig,
        $providerName,
        $dataScopePrefix
    ) {
        // street attribute is unique in terms of configuration, so it has its own configuration builder
        if (isset($attributeConfig['validation']['input_validation'])) {
            $validationRule = $attributeConfig['validation']['input_validation'];
            $attributeConfig['validation'][$this->inputValidationMap[$validationRule]] = true;
            unset($attributeConfig['validation']['input_validation']);
        }

        if ($attributeConfig['formElement'] == 'multiline') {
            return $this->getMultilineFieldConfig($attributeCode, $attributeConfig, $providerName, $dataScopePrefix);
        }

        $uiComponent = isset($this->formElementMap[$attributeConfig['formElement']])
            ? $this->formElementMap[$attributeConfig['formElement']]
            : 'Magento_Ui/js/form/element/abstract';
        $elementTemplate = isset($this->templateMap[$attributeConfig['formElement']])
            ? 'ui/form/element/' . $this->templateMap[$attributeConfig['formElement']]
            : 'ui/form/element/' . $attributeConfig['formElement'];
        if ($attributeConfig['formElement'] == 'smart_date') {
            $elementTemplate = 'Smartosc_Checkout/form/element/smart_date';
        }

        $element = [
            'component' => isset($additionalConfig['component']) ? $additionalConfig['component'] : $uiComponent,
            'config' => $this->mergeConfigurationNode(
                'config',
                $additionalConfig,
                [
                    'config' => [
                        // customScope is used to group elements within a single
                        // form (e.g. they can be validated separately)
                        'customScope' => $dataScopePrefix,
                        'template' => 'ui/form/field',
                        'elementTmpl' => $elementTemplate,
                    ],
                ]
            ),
            'dataScope' => $dataScopePrefix . '.' . $attributeCode,
            'label' => $attributeConfig['label'],
            'provider' => $providerName,
            'sortOrder' => isset($additionalConfig['sortOrder'])
                ? $additionalConfig['sortOrder']
                : $attributeConfig['sortOrder'],
            'validation' => $this->mergeConfigurationNode('validation', $additionalConfig, $attributeConfig),
            'options' => $this->getFieldOptions($attributeCode, $attributeConfig),
            'filterBy' => isset($additionalConfig['filterBy']) ? $additionalConfig['filterBy'] : null,
            'customEntry' => isset($additionalConfig['customEntry']) ? $additionalConfig['customEntry'] : null,
            'visible' => isset($additionalConfig['visible']) ? $additionalConfig['visible'] : true,
        ];

        if ($attributeCode === 'region_id' || $attributeCode === 'country_id') {
            unset($element['options']);
            $element['deps'] = [$providerName];
            $element['imports'] = [
                'initialOptions' => 'index = ' . $providerName . ':dictionaries.' . $attributeCode,
                'setOptions' => 'index = ' . $providerName . ':dictionaries.' . $attributeCode
            ];
        }

        if (isset($attributeConfig['value']) && $attributeConfig['value'] != null) {
            $element['value'] = $attributeConfig['value'];
        } elseif (isset($attributeConfig['default']) && $attributeConfig['default'] != null) {
            $element['value'] = $attributeConfig['default'];
        } else {
            $defaultValue = $this->getDefaultValue($attributeCode);
            if (null !== $defaultValue) {
                $element['value'] = $defaultValue;
            }
        }

        return $element;
    }
}
