<?php
/**
 * Created by PhpStorm.
 * User: prabu
 * Date: 20/11/17
 * Time: 5:55 PM
 */

require_once '../abstract.php';

require_once '../simplexlsx.class.php';

class Dever_Shell_Custom_Bulkupload extends Mage_Shell_Abstract
{
    protected $_processData = null;

    protected $_importData = null;

    public function _construct()
    {
        parent::_construct();
        try {
            if ($this->getArg('sheetType') == 'xlsx') {
                $datafile = Mage::getBaseDir('var') . DS . 'import' . DS . $this->getArg('sheet') . '.xlsx';
                echo "Loading {$datafile}. \n";
                $xlsx = @(new SimpleXLSX($datafile));
                $rows =  $xlsx->rows();
                $total = count($rows) - 1; //Ignore Headers
                echo "Loaded {$total} rows. \n";
            } else {
                $datafile = Mage::getBaseDir('var') . DS . 'import' . DS . $this->getArg('sheet') . '.csv';
                echo "Loading {$datafile}. \n";
                $csv = new Varien_File_Csv();
                $rows = $csv->getData($datafile);
                $total = count($rows) - 1; // Ignore Headers
                echo "Loaded {$total} rows. \n";
            }
        } catch (Exception $e) {
            die((string)$e->getMessage());
        }
        $this->_processData = $rows;
    }

    public function run()
    {
        ini_set('memory_limit', '2G');
        $this->generateKeyPair();
        $this->saveProduct();
    }

    public function generateKeyPair()
    {
        $importData = array();
        try {
            if ($this->_processData) {
                $csvHeaders = array();
                foreach ($this->_processData as $key => $lines) {
                    if ($key == 0) {
                        $csvHeaders = $lines;
                    } else {
                        $importData[] = array_combine($csvHeaders, $lines);
                    }
                }

            }
        } catch (Exception $e) {
            echo (string)$e->getMessage();
        }

        $this->_importData = $importData;
    }

    public function saveProduct()
    {
        try {
            if ($importData = $this->_importData) {
                echo "Script execution starts ---- \n";
                foreach ($importData as $data) {
                    echo "\t Start Save {$data['sku']} \n";
                    $this->_saveProduct($data);
                    echo "\t End Save {$data['sku']} \n";
                }
                echo "Script execution ends ---- \n";
            }
        } catch (Exception $e) {
            echo (string)$e->getMessage();
        }
    }

    protected function _saveProduct($data)
    {
        /** @var Mage_Catalog_Model_Product $model */
        $model = Mage::getModel('catalog/product');
        $product = $model->loadByAttribute('sku', $data['sku']);

        //Import images
        $imgArr = explode(',', $data['images']);
        foreach ($imgArr as $key => $imgUrl)
        {
            //Simple hack for image
            $tmpUrl = explode('?', $imgUrl);
            $image_type = substr(strrchr($tmpUrl[0], "."), 1);
            $filename = md5($imgUrl) . '.' . $image_type;
            $filepath = Mage::getBaseDir('media') . DS . 'import' . DS . $filename;
            file_put_contents($filepath, file_get_contents(trim($imgUrl)));
            if ($key == 0) {
                $mediaAttribute = array('thumbnail', 'small_image', 'image');
            } else {
                $mediaAttribute = array();
            }
            $obj = $product->addImageToMediaGallery($filepath, $mediaAttribute, false, false);
            if ($obj) {
                echo "\t\tProduct Image uploaded {$filepath}\n";
            } else {
                return false;
            }
        }
        $product->save();
    }
}

$obj = new Dever_Shell_Custom_Bulkupload();
$obj->run();