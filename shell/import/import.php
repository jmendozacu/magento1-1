<?php
/**
 * Created by PhpStorm.
 * User: prabu
 * Date: 20/11/17
 * Time: 5:55 PM
 */

require_once '../abstract.php';

require_once '../simplexlsx.class.php';

class Dever_Shell_Import_Import extends Mage_Shell_Abstract
{
    protected $_processData = null;

    protected $_importData = null;

    public function _construct()
    {
        parent::_construct();
        $datafile = Mage::getBaseDir('var') . DS . 'import' . DS . $this->getArg('sheet') .'.xlsx';

        echo "Loading {$datafile}. \n";
        $xlsx = @(new SimpleXLSX($datafile));
        $rows =  $xlsx->rows();
        $total = count($rows);
        echo "Loaded {$total} rows. \n";

        $this->_processData = $rows;
    }

    public function run()
    {
        ini_set('memory_limit', '2G');
        $this->saveProductOptions();
        $this->prepareDataForImport();
        $this->saveProduct();
    }

    public function saveProductOptions()
    {
        /** @var Dever_Import_Model_Import $model */
        $model = Mage::getModel('dever_import/import');
        try {
            if ($this->_processData) {
                $csvHeaders = array();
                foreach ($this->_processData as $key => $lines) {
                    if ($key == 0) {
                        $csvHeaders = $lines;
                    } else {
                        $arrayCombined = array_combine($csvHeaders, $lines);
                        $model->saveProductOptions($arrayCombined);
                    }
                }

            }
        } catch (Exception $e) {
            echo (string)$e->getMessage();
        }
    }

    public function prepareDataForImport()
    {
        /** @var Dever_Import_Model_Import $model */
        $model = Mage::getModel('dever_import/import');
        try {
            if ($this->_processData) {
                $csvHeaders = array();
                $importData = array();
                foreach ($this->_processData as $key => $lines)
                {
                    //print_r($lines);
                    if ($key == 0) {
                        $csvHeaders = $lines;
                    } else {
                        //print_r($csvHeaders);
                        $arrayCombined = array_combine($csvHeaders, $lines);
                        $importData[] = $model->prepareDataForImport($arrayCombined);
                    }
                    //print_r($arrayCombined);
                    //exit;
                }
                //exit;
                $this->_importData = $importData;
            }
        } catch (Exception $e) {
            echo (string)$e->getMessage();
        }
    }

    public function saveProduct()
    {
        try {
            if ($importData = $this->_importData) {
                /** @var Dever_Import_Model_Import $model */
                $model = Mage::getModel('dever_import/import');
                foreach ($importData as $data)
                {
                    echo "--Prepare Product Save ...\n";
                    $model->saveProduct($data, $this->getArg('mediaDir'));
                    echo "--End Product Save ...\n";
                }

            }
        } catch (Exception $e) {
            echo (string)$e->getMessage();
        }
    }
}

$obj = new Dever_Shell_Import_Import();
$obj->run();