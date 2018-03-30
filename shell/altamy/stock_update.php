<?php
/**
 * Created by PhpStorm.
 * User: prabu
 * Date: 7/10/16
 * Time: 5:23 AM
 */
require_once '../abstract.php';

require_once '../simplexlsx.class.php';

class Altamy_Product_Bulk_Stock_Update extends Mage_Shell_Abstract
{
    protected $_file = null;
    protected $_path = null;

    protected $_processData = null;

    protected $_importData = null;

    protected function _construct()
    {
        parent::_construct();
        try {

            $this->_path = Mage::getBaseDir('var') . DS . 'import' . DS . 'stock';
            $this->_file = $this->_path . DS . 'stock_update.xlsx';

            echo "Loading {$this->_file}. \n";
            $xlsx = @(new SimpleXLSX($this->_file));
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

                //Price data
                if ($row['price']) {
                    $product->setPrice($row['price'])
                        ->setCost($row['cost']);
                    if ($row['special_price']) {
                        $product->setSpecialPrice($row['special_price']);
                    }
                }

                $product->setStatus($row['status']);

                //print_r($row);
                //print_r($product->getData());
                //exit;

                if ($product->save()) {
                    echo "Product update - {$product->getSku()} \n";
                    //exit;
                }

                //unset($product);
            }

            //Rename file with timestamp
            $date = date('Ymdhis');
            $renamedFile = $this->_path . DS . 'stock_' . $date . '.xlsx';
            rename("{$this->_file}", "{$renamedFile}");

        } catch (Exception $e) {
            echo (string)$e->getMessage();
        }
    }
}

$obj = new Altamy_Product_Bulk_Stock_Update();
$obj->run();