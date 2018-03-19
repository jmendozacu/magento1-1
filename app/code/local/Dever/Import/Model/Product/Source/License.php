<?php

class Dever_Import_Model_Product_Source_License
    extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public function getAllOptions()
    {
        if (!$this->_options) {
            $this->_options = array(
                array(
                    'value' => '',
                    'label' => ''
                ),
                array(
                    'value' => '1',
                    'label' => 'Yes',
                ),
                array(
                    'value' => '2',
                    'label' => 'No',
                )
            );
        }

        return $this->_options;
    }
}
