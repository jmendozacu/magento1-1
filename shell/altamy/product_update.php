<?php
/**
 * Created by PhpStorm.
 * User: prabu
 * Date: 7/10/16
 * Time: 5:23 AM
 */
require_once '../abstract.php';

require_once '../simplexlsx.class.php';

class Altamy_Product_Bulk_Update extends Mage_Shell_Abstract
{
    protected $_processData = null;

    protected $_importData = null;

    protected function _construct()
    {
        parent::_construct();
        try {
            $datafile = Mage::getBaseDir('var') . DS . 'import' . DS . $this->getArg('sheet') . '.xlsx';
            echo "Loading {$datafile}. \n";
            $xlsx = @(new SimpleXLSX($datafile));
            $rows =  $xlsx->rows();
            $total = count($rows);
            echo "Loaded {$total} rows. \n";
        } catch (Exception $e) {
            die((string)$e->getMessage());
        }
        $this->_processData = $rows;
    }

    public function run()
    {
        $this->prepareDataForImport();
        $this->saveProductData();
    }

    public function prepareDataForImport()
    {
        try {
            if ($this->_processData) {
                $csvHeaders = array();
                $arrayCombined = array();
                foreach ($this->_processData as $key => $lines)
                {

                    if ($key == 0) {
                        $csvHeaders = $lines;
                    } else {
                        $arrayCombined[] = array_combine($csvHeaders, $lines);
                    }
                }

                $this->_importData = $arrayCombined;
            }
        } catch (Exception $e) {
            echo (string)$e->getMessage();
        }
    }

    public function saveProductData()
    {
        ini_set('memory_limit', '2G');
        try {
            foreach ($this->_importData  as $key => $row)
            {
                if ($key == 0) {
                    continue;
                }

                $productId = Mage::getModel('catalog/product')->getIdBySku($row['sku']);
                //echo $productId;

                $product = Mage::getModel('catalog/product')->load($productId);
                if(!is_object($product) || !$productId){
                    echo "Product Sku {$productId} not exists \n";
                    continue;
                }

                if ($row['attribute_set_name']) {
                    $attrbiuteSetModel = Mage::getModel('eav/entity_attribute_set');
                    $attrbiuteSetCollection = $attrbiuteSetModel->getCollection()
                        ->addFieldToFilter('attribute_set_name', $row['attribute_set_name']);
                    foreach ($attrbiuteSetCollection as $attrbiuteSet) {
                        $attributeSetId = $attrbiuteSet->getAttributeSetId();
                    }
                    $product->setAttributeSetId($attributeSetId);
                }


                //Stock data
                if ($row['qty']) {
                    $product->setStockData(
                        array (
                            'use_config_manage_stock' => 0,
                            'manage_stock' => 1,
                            'min_sale_qty' => 1,
                            'is_in_stock' => $row['qty'] > 0 ? 1 : 0,
                            'qty' => $row['qty']
                        )
                    );
                }

                $product->addData($row);
                if ($product->save()) {
                    echo "Product update - {$product->getSku()} \n";
                }

                unset($product);
            }

        } catch (Exception $e) {
            echo (string)$e->getMessage();
        }
    }
}

$obj = new Altamy_Product_Bulk_Update();
$obj->run();