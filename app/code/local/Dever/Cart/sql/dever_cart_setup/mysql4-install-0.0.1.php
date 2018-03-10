<?php

/**
 * @var $installer Mage_Sales_Model_Mysql4_Setup
 */
$installer = $this;
$installer->startSetup();
$installer->getConnection()->addColumn(
    $installer->getTable('sales/quote_item'), 'is_free_product', "tinyint(4) NOT NULL default '0'"
);
$installer->getConnection()->addColumn(
    $installer->getTable('sales/order_item'), 'is_free_product', "tinyint(4) NOT NULL default '0'"
);
$installer->endSetup();