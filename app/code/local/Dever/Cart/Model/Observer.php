<?php
/**
 * Created by PhpStorm.
 * User: prabu
 * Date: 17/11/17
 * Time: 9:23 AM
 */
class Dever_Cart_Model_Observer
{
    public function addFreeProduct($observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $free = array();
        foreach ($quote->getAllVisibleItems() as $item)
        {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            //Skip special price products
            if ($product->getSpecialPrice() > 0) {
                continue;
            }

            if ($product->getFreeSku()) {
                $arr = explode(',', $product->getFreeSku());
                foreach ($arr as $each) {
                    //Check free skus available in cart
                    $productId = Mage::getModel('catalog/product')->getIdBySku($each);
                    $free[$productId] = $item->getQty();
                }
                $this->_addFreeProduct($free, $quote);
            } else {
                //Remove Free Product
                if ($item->getIsFreeProduct()) {
                    $quote->removeItem($item->getId());
                }
            }
        }
    }

    protected function _addFreeProduct($data, $quote)
    {
        $storeId = Mage::app()->getStore()->getId();
        foreach ($data as $productId => $qty)
        {
            //Check free product exists, and remove
            $this->_checkFreeProductExists($quote, $productId);
            $product = Mage::getModel('catalog/product')->load($productId);
            /** @var Mage_Sales_Model_Quote_Item $quoteItem */
            $quoteItem = Mage::getModel('sales/quote_item');
            $quoteItem->setProduct($product);
            $quoteItem->setCustomPrice(0.0)
                ->setOriginalCustomPrice(0.0)
                ->setWeeeTaxApplied('a:0:{}')
                ->setStoreId($storeId)
                ->setQty($qty)
                ->setIsFreeProduct(1);
            //$quoteItem->setData('is_free_product', 1);
            /** @var Mage_Sales_Model_Quote $quote */
            $quote->addItem($quoteItem);
        }
    }

    protected function _checkFreeProductExists($quote, $productId)
    {
        /** @var Mage_Sales_Model_Quote $quote */
        if ($quote->hasProductId($productId)) {
            //Remove Free Product if already exists and create fresh
            foreach ($quote->getAllVisibleItems() as $item) {
                if ($productId == $item->getProductId()) {
                    $quote->removeItem($item->getId());
                }
            }
        }
    }


    public function addBundleProduct($observer)
    {
        $debug = true;
        $quote = $observer->getEvent()->getQuote();
        $free = array();
        foreach ($quote->getAllVisibleItems() as $item)
        {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            if ($product->getBundleSku()) {
                $arr = explode(',', $product->getBundleSku());
                foreach ($arr as $each) {
                    //Check free skus available in cart
                    $productId = Mage::getModel('catalog/product')->getIdBySku($each);
                    $free[$productId] = $item->getQty();
                }
                $this->_addBundleProduct($free, $quote);
            } else {
                //Remove Free Product
                if ($item->getIsFreeProduct()) {
                    $quote->removeItem($item->getId());
                }
            }
        }
    }

    protected function _addBundleProduct($data, $quote)
    {
        $storeId = Mage::app()->getStore()->getId();
        foreach ($data as $productId => $qty)
        {
            //Check free product exists, and remove
            //$this->_checkFreeProductExists($quote, $productId);
            $product = Mage::getModel('catalog/product')->load($productId);
            /** @var Mage_Sales_Model_Quote_Item $quoteItem */
            $quoteItem = Mage::getModel('sales/quote_item');
            $quoteItem->setProduct($product);
            $quoteItem->setCustomPrice(0.0)
                ->setOriginalCustomPrice(0.0)
                ->setWeeeTaxApplied('a:0:{}')
                ->setStoreId($storeId)
                ->setQty($qty)
                ->setIsFreeProduct(1);
            //$quoteItem->setData('is_free_product', 1);
            /** @var Mage_Sales_Model_Quote $quote */
            $quote->addItem($quoteItem);
        }
    }
}