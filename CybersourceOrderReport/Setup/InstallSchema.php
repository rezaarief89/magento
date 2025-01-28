<?php

namespace Wow\CybersourceOrderReport\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{

   /**
    * {@inheritdoc}
    * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
    */
   public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
   {    
        $installer = $setup;

        $installer->startSetup();

        try {
            $wowCyberReportTable = $installer->getTable('wow_cybersource_order_report');
            if ($installer->getConnection()->isTableExists($wowCyberReportTable) != true) {                
                $table = $installer->getConnection()
                    ->newTable($wowCyberReportTable)
                    ->addColumn(
                        'id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'identity' => true,
                            'unsigned' => true,
                            'nullable' => false,
                            'primary' => true
                        ],
                        'ID'
                    )
                    ->addColumn(
                        'date',
                        Table::TYPE_DATE,
                        null,
                        ['nullable' => false, 'default' => date('Y-m-d')],
                        'Order Date'
                    )
                    ->addColumn(
                        'total_order',
                        Table::TYPE_INTEGER,
                        null,
                        ['nullable' => false, 'default' => 0],
                        'Total Order'
                    )
                    ->setComment('Sales prder data with cyber payment gateway');
                $installer->getConnection()->createTable($table);
            }
            


        } catch (Exception $err) {
            \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info($err->getMessage());
        }
        
   }
}
