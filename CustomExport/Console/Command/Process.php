<?php
namespace Fef\CustomExport\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Process
 */
class Process extends Command
{

    protected function configure()
    {
        $this->setName('fef:custom:export');
        $this->setDescription('Export Product');
        
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $resourceConnection = $objectManager->get('\Magento\Framework\App\ResourceConnection');

        $connection = $resourceConnection->getConnection();

        $sql = 'SELECT 
                    cpe.*,
                    categories_aggregated.category_id,
                    categories_aggregated.category_name,
                    res.rating_summary,
                    res.reviews_count,
                    ciss.qty as stock_quantity
                FROM catalog_product_entity cpe
                LEFT JOIN (
                    SELECT 
                        ccp.product_id, 
                        ccp.category_id, 
                        ccev.value as category_name
                    FROM catalog_category_product ccp
                    INNER JOIN catalog_category_entity cce
                    ON ccp.category_id = cce.entity_id
                    INNER JOIN catalog_category_entity_varchar ccev
                    ON ccev.value_id = ccp.category_id
                    INNER JOIN eav_attribute ea
                    ON ea.attribute_id = ccev.attribute_id
                    WHERE  ea.entity_type_id=3 AND store_id = 0 AND attribute_code = "name"
                ) categories_aggregated
                ON cpe.entity_id = categories_aggregated.product_id
                
                LEFT JOIN
                (SELECT * FROM review_entity_summary WHERE entity_type = 1 AND store_id = 0 ) res
                ON cpe.entity_id = res.entity_pk_value
                
                LEFT JOIN
                (SELECT * FROM cataloginventory_stock_status WHERE stock_id = 1 AND website_id = 0 ) ciss
                ON cpe.entity_id = ciss.product_id';

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/export-product.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        // $logger->info("sql 1 : ".$sql);


        $valueArr = $connection->fetchAll($sql);

        $x = 0;
        foreach ($valueArr as $row) {
            if($x==0){
                $id = $row['entity_id'];
                $row = $this->getRow($row);
            }
            $x++;
        }
    }

    private function getRow($row){
        $id = $row['entity_id'];
        $data = [
            'id' => $row['entity_id'],
            'sku' => $row['sku'],
            'attribute_set_id' => $row['attribute_set_id'],
            'type_id' => $row['type_id'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'required_options' => $row['required_options'],
            'categories' => [$row['category_name']],
            'category_ids' => [$row['category_id']],
            'rating_summary' => $row['rating_summary'],
            'reviews_count' => $row['reviews_count'],
            'stock_quantity' => intval($row['stock_quantity'])
        ];

        $characteristics = [];

        $eavAttributes = $this->getEavAttributes($id);


        // foreach ($eavAttributes as $attribute){
        //     $code = $attribute['attribute_code'];
        //     $value = $attribute['value'];
        //     $data[$code] = $value;

        //     if($attribute['is_user_defined']){
        //         $characteristics[] = [
        //             'label' => $attribute['frontend_label'],
        //             'value' => $attribute['value']
        //         ];
        //     }
        // }

        // $data['characteristics'] = $characteristics;
        // $data['name_exact'] = $data['name'];
        // $data['name_suggest'] = $this->getNameSuggest($data['name']);

        return $data;
    }

    /**
     * @param $entityId
     * @return array
     */
    private function getEavAttributes($entityId){
        $sql = '
            (SELECT
                value_id,
                attribute_code,
                frontend_label,
                value,
                is_user_defined,
                store_id
            FROM catalog_product_entity_varchar cpe
            INNER JOIN eav_attribute ea
            ON ea.attribute_id = cpe.attribute_id
            WHERE cpe.entity_id = '.$entityId.' AND ea.entity_type_id=4 AND store_id = 0
            )
            
            UNION
            
            (
            SELECT
                value_id,
                attribute_code,
                frontend_label,
                value,
                is_user_defined,
                store_id
            FROM catalog_product_entity_text cpe
            INNER JOIN eav_attribute ea
            ON ea.attribute_id = cpe.attribute_id
            WHERE cpe.value_id = '.$entityId.' AND ea.entity_type_id=4 AND store_id = 0
            )
            
            UNION
            
            (
            SELECT
                value_id,
                attribute_code,
                frontend_label,
                value,
                is_user_defined,
                store_id
            FROM catalog_product_entity_int cpe
            INNER JOIN eav_attribute ea
            ON ea.attribute_id = cpe.attribute_id
            WHERE cpe.entity_id = '.$entityId.' AND ea.entity_type_id=4 AND store_id = 0
            )
            
            UNION
            
            (
            SELECT
                value_id,
                attribute_code,
                frontend_label,
                value,
                is_user_defined,
                store_id
            FROM catalog_product_entity_decimal cpe
            INNER JOIN eav_attribute ea
            ON ea.attribute_id = cpe.attribute_id
            WHERE cpe.entity_id = '.$entityId.' AND ea.entity_type_id=4 AND store_id = 0
            )';

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $resourceConnection = $objectManager->get('\Magento\Framework\App\ResourceConnection');

        $connection = $resourceConnection->getConnection();


        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/export-product.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info("sql 2 : ".$sql);

        // $data = $connection->fetchAll($sql);
        $data = array();


        return $data;
    }

    /**
     * @param $name
     * @return array
     */
    private function getNameSuggest($name){
        $words = explode(' ',$name);

        $input = [];
        foreach ($words as $word){
            if(strlen($word) > 3){
                $input[] = $word;
            }
        }

        return [
            "input" => $input
        ];
    }
}