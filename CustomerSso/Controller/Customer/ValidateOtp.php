<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_<modulename>
 * @author    Webkul Software Private Limited
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Fef\CustomerSso\Controller\Customer;

use Magento\Framework\Controller\ResultFactory;
use Fef\CustomerSso\Helper\Data as CustomerHelper;
use Fef\CustomShipping\Helper\Data;
use Fef\CustomShipping\Model\FefTokenFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;

class ValidateOtp extends \Magento\Framework\App\Action\Action
{

    const STR_MEMBERSHIP_URL = 'memberships/';

    /**
     * @var \Magento\Framework\App\Action\Contex
     */
    private $context;
    private $request;
    private $helper;
    private $modelFefTokenFactory;
    private $customerFactory;
    private $customerRepository;
    private $customerSession;
    private $customerHelper;


    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        Context $context,
        Http $request,
        Data $helper,
        FefTokenFactory $modelFefTokenFactory,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        Session $customerSession,
        CustomerHelper $customerHelper
    ) {
        parent::__construct($context);
        $this->context = $context;
        $this->request = $request;
        $this->helper = $helper;
        $this->modelFefTokenFactory = $modelFefTokenFactory;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->customerHelper = $customerHelper;
    }
    
    /**
     * @return json
     */
    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/customer-validate.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $modelFefToken = $this->modelFefTokenFactory->create();

        try {
            // $logger->info(print_r($this->request->getParams(),true));
            // $email = $this->request->getParam('email');
            // $resultJson = $this->customerHelper->sendOtp($email);
            $resultJson->setData([
                "message" => "OTP has been validated",
                "success" => true
            ]);
        } catch (\Exception $ex) {
            $resultJson->setData([
                "message" => ($ex->getMessage()), 
                "success" => false
            ]);
        }
        

        return $resultJson;
    }
    
}