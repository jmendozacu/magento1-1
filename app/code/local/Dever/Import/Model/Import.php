<?php
/**
 * Created by PhpStorm.
 * User: thillai.rajendran
 * Date: 6/22/16
 * Time: 11:24 AM
 */
class Dever_Import_Model_Import extends Mage_Core_Model_Abstract
{
    public function saveProductOptions($data)
    {
        /** @var Dever_Import_Helper_Import $helper */
        $helper = Mage::helper('dever_import/import');

        foreach ($data as $code => $value)
        {
            if (empty($value) || $value == '') {
                continue;
            }
            //Check attribute type
            $type = $helper->checkDefaultAttributeType($code);
            if ($type) {
                $checkAttr = $helper->attributeValueExists($code, $value);
                if ($checkAttr) {
                    continue;
                }
                // Create new option
                $helper->saveAttributeOptions(
                    $code,
                    $value
                );
            } else {
                continue;
            }
        }
    }

    public function prepareDataForImport($data)
    {
        /** @var Dever_Import_Helper_Import $helper */
        $helper = Mage::helper('dever_import/import');
        foreach ($data as $code => $value)
        {
            if (empty($value) || $value == '') {
                continue;
            }

            $product = Mage::getModel('catalog/product');
            //Check attribute type
            $type = $helper->checkDefaultAttributeType($code);
            if ($type) {

                $optionId = $helper->attributeValueExists($code, $value);
                if ($optionId) {
                    $data[$code] = $optionId;
                }

            } else {
                continue;
            }
            unset($product);
        }

        return $data;
    }

    /**
     * Save New Product
     * Product type config , simple
     * @param $index
     */
    public function saveProduct($index, $mediaDir)
    {
        try {

            /** @var Mage_Catalog_Model_Product $product */
            $product = Mage::getModel('catalog/product');
            $product->load(null);

            //Get Attribute set id by name
            $attrbiuteSetModel = Mage::getModel('eav/entity_attribute_set');
            $attrbiuteSetCollection = $attrbiuteSetModel->getCollection()
                ->addFieldToFilter('attribute_set_name', $index['group']);
            foreach ($attrbiuteSetCollection as $attrbiuteSet) {
                $attributeSetId = $attrbiuteSet->getAttributeSetId();
            }

            // Basic product details
            if (empty($index['sku'])) {
                throw new Exception("\t Skip row - Missing sku \n");
            }
            $product
                ->setSku($index['sku'])
                ->setTypeId('simple')
                ->setWeight(1)
                ->setWebsiteIds(array(1))
                ->setAttributeSetId($attributeSetId)
                ->setCreatedAt(strtotime('now'))
                ->setTaxClassId(4)
                ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
                ->setName($index['name']);

            if ($index['category']) {
                $categoryIds = explode('/', $index['category']);
                $product->setCategoryIds($categoryIds);
                unset($index['category']);
            }

            if ($index['images']) {
                $this->addImages($product, $index['images'], $mediaDir);
                unset($index['images']);
            } else {
                $product->setStatus(2);
                unset($index['status']);
            }

            //Special Case for Desc
            if ($index['description']) {
                $product->setData('description', html_entity_decode($index['description']));
                $product->setData('short_description', html_entity_decode($index['short_description']));
                unset($index['description'],$index['short_description']);
            }

            //Price fields
            $product->setPrice($index['price']);
            $product->setCost($index['cost']);
            if ($index['special_price']) {
                $product->setSpecialPrice($index['special_price']);
            }

            $product->addData($index);
            if ($product->save()) {
                //Stock Data
                $_product = Mage::getModel('catalog/product')->load($product->getId());
                if ($index['qty'] > 0) {
                    $_product->setStockData(
                        array (
                            'use_config_manage_stock' => 0,
                            'manage_stock' => 1,
                            'min_sale_qty' => 1,
                            'is_in_stock' => 1,
                            'qty' => $index['qty']
                        )
                    );
                } else {
                    $_product->setStockData(
                        array (
                            'use_config_manage_stock' => 0,
                            'manage_stock' => 1,
                            'min_sale_qty' => 1,
                            'is_in_stock' => 0,
                            'qty' => $index['qty']
                        )
                    );
                }
                $_product->save();
                echo "Product Save - {$_product->getSku()} Done \n";
                unset($product, $_product);
            }

        } catch (Exception $e) {

            echo (string)$e->getMessage();
        }
    }

    public function addImages($product, $images, $dir)
    {
        $imgArr = explode(',', $images);
        foreach ($imgArr as $key => $imgUrl)
        {
            $image_type = substr(strrchr($imgUrl, "."), 1);
            $filename = md5($imgUrl) . '.' . $image_type;
            //Simple hack for image
            $newimage = explode('?', $filename);
            $filepath = Mage::getBaseDir('media') . DS . 'import' . DS . $newimage[0];
            file_put_contents($filepath, file_get_contents(trim($imgUrl)));
            $mediaAttribute = array('thumbnail', 'small_image', 'image');
            $obj = $product->addImageToMediaGallery($filepath, $mediaAttribute, false, false);
            if ($obj) {
                echo "......Product Image uploaded {$filepath}\n";
            } else {
                return false;
            }
        }
    }

    public function addImagesBySku($product, $sku, $dir)
    {
        //Jpg image
        $imgPath = Mage::getBaseDir('media') . DS . 'import' . DS . $dir . DS . $sku . '.jpg';
        $mediaAttribute = array('thumbnail', 'small_image', 'image');
        $obj = $product->addImageToMediaGallery($imgPath, $mediaAttribute, false, false);
        if ($obj) {
            echo "......Product Image uploaded {$imgPath}\n";
        }
    }
}
