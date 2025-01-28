<?php
namespace Fef\CustomVoucherPoint\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{

    const VOUCHER_TBL_USED = 'proseller_voucher_point_used';
    const CALCTEMP_TBL = 'proseller_calculate_temp';

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->addVoucherUsedTable($setup);
        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $this->addCalculateTempTable($setup);
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $this->addVoucherUsedTableColumn($setup);
        }

        if (version_compare($context->getVersion(), '1.0.4', '<')) {
            $this->addVoucherValidUsedTableColumn($setup);
        }

        $setup->endSetup();
    }

    
    private function addVoucherUsedTable(\Magento\Framework\Setup\SchemaSetupInterface $setup)
    {
        $installer = $setup;
        try {
            $voucTbl = $installer->getTable($this::VOUCHER_TBL_USED);
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
                        'quote_id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'nullable' => false, 
                            'default' => 0
                        ],
                        'Magento Quote ID'
                    )
                    ->addColumn(
                        'used_voucher',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable' => false, 'default' => ''],
                        'Member used voucher'
                    )
                    ->setComment('Fef Voucher Used Table');
                $installer->getConnection()->createTable($table);
            }
        } catch (Exception $err) {
            \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info($err->getMessage());
        }
    }

    private function addCalculateTempTable(\Magento\Framework\Setup\SchemaSetupInterface $setup){
        $installer = $setup;
        try {
            $calcTempTable = $installer->getTable($this::CALCTEMP_TBL);
            if ($installer->getConnection()->isTableExists($calcTempTable) != true) {                
                $table = $installer->getConnection()
                    ->newTable($calcTempTable)
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
                        'quote_id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'nullable' => false, 
                            'default' => 0
                        ],
                        'Magento Quote ID'
                    )
                    ->addColumn(
                        'calculate_result',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable' => false, 'default' => ''],
                        'Result of Calculate Order'
                    )
                    ->setComment('Fef Calculate Temp Table');
                $installer->getConnection()->createTable($table);
            }
        } catch (Exception $err) {
            \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info($err->getMessage());
        }
    }

    private function addVoucherUsedTableColumn(\Magento\Framework\Setup\SchemaSetupInterface $setup)
    {
        $installer = $setup;
        $installer->getConnection()->addColumn(
            $installer->getTable($this::VOUCHER_TBL_USED),
            'voucher_name',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => false,
                'comment' => 'Proseller Used Voucher Name'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable($this::VOUCHER_TBL_USED),
            'voucher_amount',
            [
                'type' => Table::TYPE_DECIMAL,
                'length'    => '10,2',
                'nullable'  => true,
                'default'   => 0.00,
                'comment' => 'Proseller Used Voucher Amount'
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable($this::VOUCHER_TBL_USED),
            'voucher_type',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => false,
                'comment' => 'Proseller Used Voucher Type'
            ]
        );
        
    }

    private function addVoucherValidUsedTableColumn(\Magento\Framework\Setup\SchemaSetupInterface $setup)
    {
        $installer = $setup;
        $installer->getConnection()->addColumn(
            $installer->getTable($this::VOUCHER_TBL_USED),
            'voucher_validity',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => false,
                'comment' => 'Proseller Used Voucher Validity'
            ]
        );
        
    }
    
}
