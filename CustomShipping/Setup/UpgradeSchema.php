<?php
namespace Fef\CustomShipping\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    const QUOTE_TBL = 'quote';
    const SALES_ORDER_TBL = 'sales_order';

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $this->addAdditionalCost($setup);
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $this->updateAdditionalCost($setup);
        }

        $setup->endSetup();
    }

    
    private function addAdditionalCost(\Magento\Framework\Setup\SchemaSetupInterface $setup)
    {

        $attributes = [
            'cost_weight' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'default' => 0,
                'comment' => 'Cost Weight from API',
                'nullable' => true
            ],
            'cost_location' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'default' => 0,
                'nullable' => true,
                'comment' => 'Cost Location from API',
            ],
            'cost_item_spesific' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'default' => 0,
                'nullable' => true,
                'comment' => 'Cost Item Spesific from API',
            ],
            'cost_staircase' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'default' => 0,
                'nullable' => true,
                'comment' => 'Cost Staircase from API',
            ],
            'cost_period' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'default' => 0,
                'nullable' => true,
                'comment' => 'Cost Period from API',
            ],
            'cost_delivery_type' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'default' => 0,
                'nullable' => true,
                'comment' => 'Cost Delivery Type from API',
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
    }

    private function updateAdditionalCost(\Magento\Framework\Setup\SchemaSetupInterface $setup){
        $removeAttributes = [
            "itemSpecific",
            "period",
            "staircase",
            "deliveryType"
        ];

        $addAttributes = [
            'date' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'default' => 0,
                'comment' => 'Cost Date from API',
                'nullable' => true
            ],
            'standardExpressDelivery' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'default' => 0,
                'comment' => 'Flag StandartExpress from API',
                'nullable' => true
            ],
            'itemSurcharge' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'default' => 0,
                'comment' => 'Cost Item Surcharge from API',
                'nullable' => true
            ]
        ];

        foreach ($removeAttributes as $key => $attributeCode) {
            $setup->getConnection()->dropColumn($setup->getTable(self::QUOTE_TBL), $attributeCode);
            $setup->getConnection()->dropColumn($setup->getTable(self::SALES_ORDER_TBL), $attributeCode);
        }

        foreach ($addAttributes as $attrCode => $config) {
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable(self::QUOTE_TBL),
                    $attrCode,
                    $config
                );
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable(self::SALES_ORDER_TBL),
                    $attrCode,
                    $config
                );
        }
    }
}
