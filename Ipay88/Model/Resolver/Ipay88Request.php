<?php

namespace Wow\Ipay88\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

class Ipay88Request extends \Ipay88\Ipay88\Model\Resolver\Ipay88Request
{
    public function __construct(
        \Magento\Sales\Model\Order $order,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Ipay88\Ipay88\Helper\Config $config
    ) {
        $this->order = $order;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->config = $config;
        parent::__construct($order, $customerRepositoryInterface, $config);
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|\Magento\Framework\GraphQl\Query\Resolver\Value|mixed
     * @throws GraphQlInputException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $value['app'] = isset($value['app']) ? 0 : 1;
        $order_id = $value['order_number'];
        $order = $this->order->loadByIncrementId($order_id);
        $customer = $this->_customerRepositoryInterface->get($order->getCustomerEmail());

        //get merchant code and id
        if (1 == $value['app']) { //send from mobile apps
            $merchantKey = $this->config->getConfig("ipay88_app_merchant_key");
            $merchantCode = $this->config->getConfig("ipay88_app_merchant_code");
        } else {
            $merchantKey = $this->config->getConfig("ipay88_merchant_key");
            $merchantCode = $this->config->getConfig("ipay88_merchant_code");
        }

        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $methodTitle = $method->getTitle();

        $RefNo = $order_id;
        $amount = number_format($order->getBaseGrandTotal(), 2, "", "");
        $currency = $order->getBaseCurrencyCode();

        $string = $merchantKey . $merchantCode . $RefNo . $amount . $currency;

        // $rawSignature = $merchantKey . $merchantCode .$RefNo.$amount.$currency;
        // $signature = hash_hmac("sha512",$rawSignature,$merchantKey);
        $hash = hash_hmac('sha512', $string);

        //retrieve items
        $orderItems = $order->getAllItems();
        $prodesc = "";
        foreach ($orderItems as $key) {
            $prodesc .= $key->getName() . ",";
        }
        $prodesc = rtrim($prodesc, ",");

        $output = [
            "MerchantCode" => $merchantCode,
            "RefNo" => $order_id,
            "Amount" => number_format($order->getBaseGrandTotal(), 2),
            "Currency" => $order->getBaseCurrencyCode(),
            "ProdDesc" => $prodesc,
            "UserName" => $order->getCustomerName(),
            "UserEmail" => $order->getCustomerEmail(),
            "UserContact" => $order->getShippingAddress()->getTelephone(),
            "SignatureType" => "SHA256",
            "Signature" => $hash,
            "ResponseURL" => "http://fnsg.appscentral.net/",
            "BackendURL" => "http://fnsg.appscentral.net/"
        ];

        return $output;
    }
}
