<?php

namespace Fef\CustomerSso\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Smartosc\Customer\Helper\Data;

class CustomerRegisterConfigProvider extends \Smartosc\Checkout\Model\CustomerRegisterConfigProvider
{
    /**
     * @var Data
     */
    private $helperData;


    public function __construct(
        Data $helperData
    )
    {
        $this->helperData = $helperData;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $output['country_element_html'] = $this->helperData->getCountryHtmlSelect();
        $output['contact_number_element_html'] = $this->getContactNumberElementHtml();
        $output['dob_tooltip_label'] = $this->helperData->getDobTooltipLabel();
        $output['contact_number_tooltip_label'] = $this->helperData->getContactNumberTooltipLabel();
        $output['privacy_url'] = $this->helperData->getPrivacyPoliciesUrl();
        $output['term_url'] = $this->helperData->getTermAndConditionUrl();

        return $output;
    }

    public function getContactNumberElementHtml()
    {
        $fieldLabel = __('Contact Number');
        $countryList = $this->helperData->getCountryCodeOptions();
        $html = '<select id="contact_number" name="contact_number" data-validate="{\'validate-select\':true}" aria-required="true">';
        foreach ($countryList as $countryOption) {
            $countryCode = $countryOption['country_code'];
            $selectedSG = "";
            if ($countryOption['value'] == "SG") {
                $selectedSG = "selected";
            }
            $html .= <<<HTML
<option value="$countryCode" $selectedSG>+$countryCode</option>
HTML;
        }

        $html .= <<<HTML
 </select>
HTML;
        $html .= <<<HTML
 <input type="text" class="input-text" id="contact_number_1" placeholder="$fieldLabel" data-validate="{required: true}" required>
HTML;
        return $html;
    }
}
