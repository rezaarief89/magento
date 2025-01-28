<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Wow\Einvoice\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class Invoice extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;
    protected $resourceConnection;
    protected $_logger;
	
	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection
	)
	{
		$this->_pageFactory = $pageFactory;
        $this->resourceConnection = $resourceConnection;
		return parent::__construct($context);
	}

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        
		$connection = $this->resourceConnection->getConnection();
		$query = "SELECT 
				'0.00' as 'pre_payment_amount',
				inv.increment_id as 'document_number',
				'01' as 'einvoice_type',
				DATE_FORMAT(DATE_ADD(inv.created_at, INTERVAL 8 HOUR), '%Y-%m-%d') document_date, 
				DATE_FORMAT(DATE_ADD(inv.created_at, INTERVAL 8 HOUR), '%H:%i:%s') document_time, 
				upper(i.name) as 'description_of_product_or_service',
				i.qty as 'quantity',
				i.price as 'unit_price',
				i.row_total_incl_tax as 'subtotal',
				ABS(IFNULL(i.discount_amount, 0)) as 'discount_amount',
				(i.row_total-IFNULL(i.discount_amount, 0)) as 'line_item_total_excluding_tax',
				(i.row_total-IFNULL(i.discount_amount, 0)) as 'item_taxable_amount',
				'0.00' as 'tax_rate',
				i.tax_amount as 'tax_amount',
				(i.row_total-IFNULL(i.discount_amount, 0)) as 'line_item_including_tax',
				'0.00' as 'invoice_additional_discount_amount',
				'0.00' as 'total_discount_value',
				(inv.subtotal+IFNULL(inv.discount_amount, 0)+inv.tax_amount) as 'total_net_amount',
				(inv.subtotal+IFNULL(inv.discount_amount, 0)) as 'total_excluding_tax',
				inv.tax_amount as 'total_tax_amount',
				(inv.subtotal+IFNULL(inv.discount_amount, 0)+inv.tax_amount) 'total_including_tax',
				(inv.subtotal+IFNULL(inv.discount_amount, 0)+inv.tax_amount) 'total_payable_amount',
				inv.increment_id as 'udf1',
				i.sku as 'sku'
				FROM sales_invoice_item AS i 
				LEFT JOIN sales_invoice as inv on inv.entity_id = i.parent_id
				WHERE inv.store_id = 1
				AND i.row_total_incl_tax >0
				AND DATE_ADD(inv.created_at, INTERVAL 8 HOUR) >= '2024-08-01 00:00:00'
				order by i.entity_id ASC
				";
		
		$result = $connection->fetchAll($query);

        foreach($result as $key=>$r) {
			
			$_sql = "SELECT einvoice_id FROM wow_einvoice_einvoice WHERE document_number = '".$r['document_number']."'  and sku = '".$r['sku']."' and einvoice_type = '01'";
			
			$einvoice = $connection->fetchOne($_sql);

			
			if(!$einvoice){
			
			
				$sql = "INSERT INTO wow_einvoice_einvoice (pre_payment_amount,document_number,einvoice_type,document_date,document_time,description_of_product_or_service,quantity,unit_price,subtotal,discount_amount ,line_item_total_excluding_tax,item_taxable_amount,tax_rate,tax_amount,line_item_including_tax,invoice_additional_discount_amount,total_discount_value,total_net_amount,total_excluding_tax,total_tax_amount,total_including_tax,total_payable_amount,udf1,sku)
						VALUES ('".$r['pre_payment_amount']."','".$r['document_number']."','".$r['einvoice_type']."','".$r['document_date']."','".$r['document_time']."','".addslashes($r['description_of_product_or_service'])."','".$r['quantity']."','".$r['unit_price']."','".$r['subtotal']."','".$r['discount_amount']."','".$r['line_item_total_excluding_tax']."','".$r['item_taxable_amount']."','".$r['tax_rate']."','".$r['tax_amount']."','".$r['line_item_including_tax']."','".$r['invoice_additional_discount_amount']."','".$r['total_discount_value']."','".$r['total_net_amount']."','".$r['total_excluding_tax']."','".$r['total_tax_amount']."','".$r['total_including_tax']."','".$r['total_payable_amount']."','".$r['udf1']."','".$r['sku']."')";
				
				
				
				$_result = $connection->query($sql);

			}
		}	
		
		
    }
}

