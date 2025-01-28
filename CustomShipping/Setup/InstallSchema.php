<?php

namespace Fef\CustomShipping\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{

    const QUOTE_TBL = 'quote';
    const SALES_ORDER_TBL = 'sales_order';

    /**
     * @var \Magento\Sales\Setup\SalesSetupFactory
     */
    private $salesSetupFactory;

    /**
     * @var \Magento\Quote\Setup\QuoteSetupFactory
     */
    private $quoteSetupFactory;

    public function __construct(
        \Magento\Sales\Setup\SalesSetupFactory $salesSetupFactory,
        \Magento\Quote\Setup\QuoteSetupFactory $quoteSetupFactory
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
    }

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
            $fefTokenTable = $installer->getTable('fef_shipping_token');
            if ($installer->getConnection()->isTableExists($fefTokenTable) != true) {                
                $table = $installer->getConnection()
                    ->newTable($fefTokenTable)
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
                        'token',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable' => false, 'default' => ''],
                        'Auth Token'
                    )
                    ->addColumn(
                        'refresh_token',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable' => false, 'default' => ''],
                        'Reresh TOken'
                    )
                    ->addColumn(
                        'expiry',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable' => false, 'default' => ''],
                        'Expiry Token'
                    )
                    ->setComment('Fef Custom Shipping API Table');
                $installer->getConnection()->createTable($table);
            }
            

            // Add custom table for rate API stored
            $fefShippingRateResult = $installer->getTable('fef_shipping_rate_result');
            if ($installer->getConnection()->isTableExists($fefShippingRateResult) != true) {                
                $table = $installer->getConnection()
                    ->newTable($fefShippingRateResult)
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
                        'quote_id',
                        Table::TYPE_INTEGER,
                        null,
                        ['nullable' => false],
                        'Quote Id'
                    )
                    ->addColumn(
                        'rate_result_shipping',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable' => false, 'default' => ''],
                        'Shipping Type'
                    )
                    ->addColumn(
                        'api_params',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable' => false, 'default' => ''],
                        'Get Rate Params'
                    )->addColumn(
                        'api_result',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable' => false, 'default' => ''],
                        'Get Rate Result'
                    )
                    ->setComment('Fef Custom Shipping API Result Table');
                $installer->getConnection()->createTable($table);
            }

            $attributes = [
                'delivery_timeslot' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => \Magento\Framework\DB\Ddl\Table::DEFAULT_TEXT_SIZE,
                    'default' => '',
                    'nullable' => false,
                    'comment' => 'Delivery TImeslot'
                ],
                'delivery_stairs' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => \Magento\Framework\DB\Ddl\Table::DEFAULT_TEXT_SIZE,
                    'default' => '',
                    'nullable' => false,
                    'comment' => 'Delivery Building Level'
                ]
            ];

            foreach ($attributes as $attributeCode => $config) {
                $setup->getConnection()
                    ->addColumn(
                        $setup->getTable(self::QUOTE_TBL),
                        $attributeCode,
                        $config
                    );
                $setup->getConnection()
                    ->addColumn(
                        $setup->getTable(self::SALES_ORDER_TBL),
                        $attributeCode,
                        $config
                    );
            }


        } catch (Exception $err) {
            \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->info($err->getMessage());
        }
        
   }
}
