<?php
namespace Wow\DigitalPrinting\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
   public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
   {

       $setup->getConnection()->addColumn(
           $setup->getTable('catalog_product_option_type_value'),
           'image',
           [
               'type'     => Table::TYPE_TEXT,
               'nullable' => true,
               'default'  => null,
               'comment'  => 'Image',
           ]
       );

        $setup->getConnection()->addColumn(
            "sales_order_item",
            'option_image',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'length' => 255,
                'comment' => 'Digital Printing Image Option'
            ]
        );

        $setup->getConnection()->addColumn(
            "sales_order_item",
            'option_price',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_FLOAT,
                'nullable' => true,
                'comment' => 'Digital Printing Price Option'
            ]
        );

       $setup->endSetup();
   }
}