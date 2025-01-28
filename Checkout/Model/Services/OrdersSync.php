<?php
//
//namespace KTech\Checkout\Model\Services;
//
//use Magento\Catalog\Model\Product\Type as ProductType;
//
//class OrdersSync
//{
//
//    protected $edgeworksHelper;
//    protected $logger;
//    protected $orderRepository;
//    protected $jsonHelper;
//
//    protected $httpClientFactory;
//
//    public function __construct(
//        \KTech\Checkout\Logger\Logger $logger,
//        \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface,
//        \Magento\Framework\Json\Helper\Data $jsonHelper,
//        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
//    ) {
////        $this->edgeworksHelper = $edgeworksHelper;
//        $this->logger = $logger;
//        $this->orderRepository = $orderRepositoryInterface;
//        $this->jsonHelper = $jsonHelper;
//        $this->httpClientFactory = $httpClientFactory;
//    }
//
//    /**
//     * @param $data
//     * @return bool true if successful and delete from queue
//     */
//    public function sendOrder($data, $membershipNo)
//    {
//        $this->logger->info("start sendOrder");
//        $order = $this->orderRepository->get($data['id']);
//
//        if (!$order) {
//            $this->logger->info("order not found");
//            // return true so queue row is deleted
//            return true;
//        }
//
//        $n = 0;
//        $lineItems = [];
//
//        foreach ($order->getItems() as $item) {
//            if ($item->getHasChildren() && $item->getProductType() != ProductType::TYPE_SIMPLE) {
//                $n++;
//                //Configurable
//                foreach ($item->getChildrenItems() as $childItem) {
//                    $lineItems[] = $this->getItemInfo($item, $childItem);
//                }
//            } elseif ($item->getProductType() === ProductType::TYPE_SIMPLE) { //Simple
//                $n++;
//                $lineItems[] = $this->getItemInfo($item);
//            }
//        }
//
//        if ($n === 0) {
//            $this->logger->info("no products");
//            // return true so queue row is deleted
//            return true;
//        }
//
//        $pos_name = $this->edgeworksHelper->getStoreConfigValue('edgeworks_general/pos/pos_name');
//
//        $payments = [];
//        $payment = $order->getPayment();
//        $payments['paymentType'] = $payment->getMethod();
//        $payments['remark'] = '';
//        $payments['amount'] = number_format($payment->getAmountOrdered(), 2, '.', '');
//
//
//        $results = [];
//        $results['orderNo'] = $order->getIncrementId();
//        $results['orderDate'] = $order->getCreatedAt();
//        $results['membershipNo'] = $membershipNo;
//        $results['posName'] = $pos_name;
//        $results['username'] = $order->getCustomerEmail();
//        $results['totalAmount'] = number_format($order->getGrandTotal(), 2, '.', '');
//        $results['lineItems'] = $lineItems;
//        $results['payments'] = [];
//        $results['payments'][] = $payments;
//
//        if ($membershipNo === 'WALK-IN') {
//            $results['remark'] = json_encode($order->getBillingAddress()->getData());
//        }
//
//        $json = json_encode($results);
//        $this->logger->info($json);
//
//        return $this->sendToPOS($json, 'ExtAPI/Sales/SendSales.ashx');
//    }
//
//
//    /**
//     * @param $item
//     * @param $childItem
//     * @return array
//     */
//    protected function getItemInfo($item, $childItem = null, $includePrice = true, $qty = 0)
//    {
//        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
//        $productData = [];
//
//        if ($childItem) {
//            $productData['id'] = $childItem->getProduct()->getId();
//            $productData['qty'] = number_format($childItem->getQtyOrdered(), 0, '', '');
//        } else {
//            $productData['id'] = $item->getProduct()->getId();
//            $productData['qty'] = number_format($item->getQtyOrdered(), 0, '', '');
//        }
//
//        if ($qty > 0) {
//            $productData['qty'] = number_format($qty, 0, '', '');
//        }
//
//        $product = $objectManager->create('Magento\Catalog\Model\Product')->load($productData['id']);
//        $productData['itemNo'] = $product->getData('barcode');
//        if (is_null($product->getData('barcode')) || empty($product->getData('barcode')))
//            $productData['itemNo'] = $product->getData('sku');
//
//        if ($includePrice) {
//            $productData['gstAmount'] = number_format($item->getTaxAmount(), 2, '.', '');
//            $productData['discount'] = number_format($item->getDiscountAmount(), 2, '.', '');
//            $productData['totalamount'] = number_format($item->getRowTotalInclTax(), 2, '.', '');
//            $productData['price'] = number_format($item->getPrice(), 2, '.', '');
//        }
//
//        return $productData;
//    }
//
//    /**
//     * @param $order
//     */
//    protected function shippingItem($priceShippingFee)
//    {
//        $productData = [];
//        $productData['id'] = 1;
//        $productData['qty'] = 1;
//        $productData['itemNo'] = 'Shipping';
//        $productData['totalamount'] = $priceShippingFee;
//        $productData['price'] = $priceShippingFee;
//        return $productData;
//    }
//
//    /**
//     * @param $data
//     * @return bool true if successful and delete from queue
//     */
//    public function voidOrder($data)
//    {
//        $this->logger->info("start voidOrder");
//        $order = $this->orderRepository->get($data['id']);
//
//        if (!$order) {
//            $this->logger->info("order not found");
//            // return true so queue row is deleted
//            return true;
//        }
//
//        $results = [];
//        $results['orderNo'] = $order->getIncrementId();
//
//        $json = json_encode($results);
//        $this->logger->info($json);
//
//        return $this->sendToPOS($json, 'ExtAPI/Sales/VoidSales.ashx');
//    }
//
//    /**
//     * @param $data
//     * @return bool true if successful and delete from queue
//     */
//    public function sendStockIn($data)
//    {
//        $this->logger->info("start sendStockIn");
//        $order = $this->orderRepository->get($data['id']);
//        $itemsToRestock = [];
//        $itemsToRestockQty = [];
//        if (isset($data['items'])) {
//            $itemsToRestock = $data['items'];
//            $itemsToRestockQty = $data['qty'];
//        }
//
//        $this->logger->info("itemsToRestock" . print_r($itemsToRestock, true));
//
//        if (!$order) {
//            $this->logger->info("order not found");
//            // return true so queue row is deleted
//            return true;
//        }
//
//        $n = 0;
//        $lineItems = [];
//
//        foreach ($order->getItems() as $item) {
//            // if ($item->getHasChildren() && $item->getProductType() != ProductType::TYPE_SIMPLE) {
//            //     $n++;
//            //     //Configurable
//            //     foreach ($item->getChildrenItems() as $childItem) {
//            //         $lineItems[] = $this->getItemInfo($item, $childItem, false);
//            //     }
//            // } else
//            if ($item->getProductType() === ProductType::TYPE_SIMPLE) { //Simple
//                $n++;
//
//                $this->logger->info("SKU: " . $item->getProduct()->getSku());
//
//                $qty = 0;
//                $key = array_search($item->getProduct()->getSku(),  $itemsToRestock);
//                $this->logger->info($key);
//                if ($key === FALSE) {
//                    $this->logger->info("do not restock");
//                    continue;
//                } elseif ($itemsToRestockQty[$key]) {
//                    $qty = $itemsToRestockQty[$key];
//                }
//
//                $this->logger->info("restock");
//                $lineItems[] = $this->getItemInfo($item, null, false, $qty);
//            }
//        }
//
//        if ($n === 0) {
//            $this->logger->info("no products");
//            // return true so queue row is deleted
//            return true;
//        }
//
//        $invLocationName = $this->edgeworksHelper->getStoreConfigValue('edgeworks_general/pos/inventory_location_name');
//
//        $results = [];
//        $results['refNo'] = $order->getIncrementId();
//        $results['invLocationName'] = $invLocationName;
//        $results['remarks'] = "";
//        $results['stockItems'] = $lineItems;
//
//        $json = json_encode($results);
//        $this->logger->info($json);
//
//        return $this->sendToPOS($json, 'ExtAPI/Inventory/StockIn.ashx');
//    }
//
//    public function searchMember($data)
//    {
//        $this->logger->info("start searchMember");
//        $order = $this->orderRepository->get($data['id']);
//
//        $results = [];
//        $results['membershipNo'] = $order->getCustomerId();
//        $results['emailAddress'] = $order->getCustomerEmail();
//
//        $json = json_encode($results);
//        $this->logger->info($json);
//
//        $membership = $this->sendToPOS($json, 'ExtAPI/Member/IsMemberRegistered.ashx', true);
//
//        if ($membership !== false && $membership->ResultCode === 0) {
//            // should contain membershipNo
//            return $membership->ResultMessage;
//        }
//
//        $this->logger->info(print_r($membership));
//        return false;
//    }
//
//    public function createMember($data)
//    {
//        $this->logger->info("start createMember");
//        $order = $this->orderRepository->get($data['id']);
//
//        $results = [];
//        $results['membershipNo'] = $order->getCustomerId();
//        $results['email'] = $order->getCustomerEmail();
//        $results['name'] = "";
//        $results['name'] .= $order->getCustomerFirstname() ? $order->getCustomerFirstname() . " " : "";
//        $results['name'] .= $order->getCustomerLastname() ? $order->getCustomerLastname() : "";
//        $results['address'] = json_encode($order->getBillingAddress()->getData());
//        $results['mobile'] = $order->getBillingAddress()->getTelephone();
//        $results['groupName'] = "Normal";
//        $results['gender'] = $order->getCustomerGender();
//
//        $json = json_encode($results);
//        $this->logger->info($json);
//
//        $membership = $this->sendToPOS($json, 'ExtAPI/Member/NewMember.ashx');
//        $this->logger->info(print_r($membership));
//
//        if ($membership !== false && $membership->ResultCode === 0) {
//            // should contain membershipNo
//            return $results['membershipNo'];
//        }
//
//        return false;
//    }
//
//    private function sendToPOS($json, $path, $returnData = false)
//    {
//        $pos_url = $this->edgeworksHelper->getStoreConfigValue('edgeworks_general/pos/pos_url');
//        $pos_url .= $path;
//        $pos_token = $this->edgeworksHelper->getStoreConfigValue('edgeworks_general/pos/pos_token');
//        $this->logger->info("URL: " . $pos_url . " Token: " . $pos_token);
//
//        try {
//            $client = $this->httpClientFactory->create();
//            $client->setUri($pos_url);
//            $client->setMethod(\Zend_Http_Client::POST);
//            $client->setHeaders('Content-Type', 'application/json');
//            $client->setHeaders('Accept', 'application/json');
//            $client->setHeaders("Authorization", "Bearer " . $pos_token);
//            $client->setRawData($json);
//            $response = $client->request();
//            $body = $response->getBody();
//            $this->logger->info("response: " . $body);
//            $result = json_decode($body);
//            // 0 = delete row in queue, else do not delete (API might be down)
//            if (isset($result->ResultCode)) {
//                if ($returnData) {
//                    return $result;
//                } else if ($result->ResultCode === 0) {
//                    return true;
//                }
//            }
//
//            if (isset($result->ResultMessage)) {
//                return $result->ResultMessage;
//            } else {
//                // don't delete item in queue
//                return false;
//            }
//        } catch (\Exception $e) {
//            $this->logger->critical($e);
//            return false;
//        }
//    }
//}
