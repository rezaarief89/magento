<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Wow\Einvoice\Cron;

class Creditmemo
{
	protected $logger;

	protected $resourceConnection;

		

   /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
		\Magento\Framework\App\ResourceConnection $resourceConnection,
		\Psr\Log\LoggerInterface $logger
	)
    {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
		$writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
		$logger = new \Zend_Log();
		$logger->addWriter($writer);

		$connection = $this->resourceConnection->getConnection();
		$query = "SELECT 
					'0.00' as 'pre_payment_amount',
					o.increment_id as 'document_number',
					'04' as 'einvoice_type',
					DATE_FORMAT(DATE_ADD(o.created_at, INTERVAL 8 HOUR), '%Y-%m-%d') document_date, 
					DATE_FORMAT(DATE_ADD(o.created_at, INTERVAL 8 HOUR), '%H:%i:%s') document_time, 
					inv.increment_id as 'original_einvoice_reference_number',
					'NA' as 'original_einvoice_uuid',
					upper(i.name) as 'description_of_product_or_service',
					i.qty as 'quantity',
					i.price as 'unit_price',
					i.row_total_incl_tax as 'subtotal',
					ROUND(ABS(IFNULL(i.discount_amount, 0)), 0) as 'discount_amount',
					(i.row_total-IFNULL(i.discount_amount, 0)) as 'line_item_total_excluding_tax',
					(i.row_total-IFNULL(i.discount_amount, 0)) as 'item_taxable_amount',
					'0.00' as 'tax_rate',
					i.tax_amount as 'tax_amount',
					(i.row_total-IFNULL(i.discount_amount, 0)) as 'line_item_including_tax',
					'0.00' as 'invoice_additional_discount_amount',
					'0.00' as 'total_discount_value',
					(o.subtotal+IFNULL(o.discount_amount, 0)+o.tax_amount) as 'total_net_amount',
					(o.subtotal+IFNULL(o.discount_amount, 0)) as 'total_excluding_tax',
					o.tax_amount as 'total_tax_amount',
					(o.subtotal+IFNULL(o.discount_amount, 0)+o.tax_amount) 'total_including_tax',
					(o.subtotal+IFNULL(o.discount_amount, 0)+o.tax_amount) 'total_payable_amount',
					inv.increment_id as 'udf1',
					i.sku as 'sku'
					FROM sales_creditmemo_item AS i 
					LEFT JOIN sales_creditmemo as o on o.entity_id = i.parent_id
					LEFT JOIN sales_invoice as inv on inv.order_id = o.order_id
					WHERE o.store_id = 1
					AND inv.subtotal_incl_tax > 0
					AND i.row_total_incl_tax >0
					AND DATE_ADD(o.created_at, INTERVAL 8 HOUR) <= DATE_ADD(NOW(), INTERVAL 8 HOUR)
					AND DATE_ADD(o.created_at, INTERVAL 8 HOUR) >= DATE_ADD(NOW(), INTERVAL 7 HOUR)
					order by i.entity_id ASC
					";
        
		//AND TIMESTAMPDIFF(MINUTE,DATE_ADD(o.created_at, INTERVAL 8 HOUR),DATE_ADD(NOW(), INTERVAL 8 HOUR)) < 300000

		/**
		* DATE_ADD(NOW(), INTERVAL 8 HOUR) as `current_time`,
		* TIMESTAMPDIFF(MINUTE,DATE_ADD(o.created_at, INTERVAL 8 HOUR),DATE_ADD(NOW(), INTERVAL 8 HOUR)) 	
		*/
		
		// $logger->info("query : ".$query);

		$result = $connection->fetchAll($query);

        foreach($result as $key=>$r) {
			
			$_sql = "SELECT einvoice_id FROM wow_einvoice_einvoice WHERE document_number = '".$r['document_number']."' and sku = '".$r['sku']."' and einvoice_type = '04'";
			
			$einvoice = $connection->fetchOne($_sql);

			
			if(!$einvoice){
				$sql = "INSERT INTO wow_einvoice_einvoice (pre_payment_amount,document_number,einvoice_type,document_date,document_time,original_einvoice_reference_number,original_einvoice_uuid,description_of_product_or_service,quantity,unit_price,subtotal,discount_amount ,line_item_total_excluding_tax,item_taxable_amount,tax_rate,tax_amount,line_item_including_tax,invoice_additional_discount_amount,total_discount_value,total_net_amount,total_excluding_tax,total_tax_amount,total_including_tax,total_payable_amount,udf1,sku)
						VALUES ('".$r['pre_payment_amount']."','".$r['document_number']."','".$r['einvoice_type']."','".$r['document_date']."','".$r['document_time']."','".$r['original_einvoice_reference_number']."','".$r['original_einvoice_uuid']."','".addslashes($r['description_of_product_or_service'])."','".$r['quantity']."','".$r['unit_price']."','".$r['subtotal']."','".$r['discount_amount']."','".$r['line_item_total_excluding_tax']."','".$r['item_taxable_amount']."','".$r['tax_rate']."','".$r['tax_amount']."','".$r['line_item_including_tax']."','".$r['invoice_additional_discount_amount']."','".$r['total_discount_value']."','".$r['total_net_amount']."','".$r['total_excluding_tax']."','".$r['total_tax_amount']."','".$r['total_including_tax']."','".$r['total_payable_amount']."','".$r['udf1']."','".$r['sku']."')";				
				$_result = $connection->query($sql);

			}
		}





    }
}

