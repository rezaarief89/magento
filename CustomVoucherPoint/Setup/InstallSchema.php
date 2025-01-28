<?php

namespace Fef\CustomVoucherPoint\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{

    const VOUCHER_TBL = 'proseller_voucher_point';

   /**
    * {@inheritdoc}
    * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
    */
   public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
   {    
        $installer = $setup;

        $installer->startSetup();

        try {
            // Add custom table for token stored
            $voucTbl = $installer->getTable($this::VOUCHER_TBL);
            if ($installer->getConnection()->isTableExists($voucTbl) != true) {                
                $table = $installer->getConnection()
                    ->newTable($voucTbl)
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
                        'customer_id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'nullable' => false, 
                            'default' => 0
                        ],
                        'Magento Customer ID'
                    )
                    ->addColumn(
                        'proseller_member_id',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable' => false, 'default' => ''],
                        'Proseller Member Id'
                    )
                    ->addColumn(
                        'member_voucher_list',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable' => false, 'default' => ''],
                        'Proseller Member Voucher List'
                    )
                    ->addColumn(
                        'member_point',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable' => false, 'default' => ''],
                        'Proseller Member Point'
                    )
                    ->setComment('Fef Voucher and Point API Table');
                $installer->getConnection()->createTable($table);
            }
           
        } catch (Exception $err) {
            \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info($err->getMessage());
        }
        
   }
}
