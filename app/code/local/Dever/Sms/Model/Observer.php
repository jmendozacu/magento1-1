<?php
class Dever_Sms_Model_Observer
{
    public function sendSmsCod($observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order->getPayment()->getMethod() == 'cashondelivery') {
            /** @var Dever_Sms_Helper_Data $helper */
            $helper = Mage::helper('dever_sms');
            $helper->sendSms($order, 'order');
        }

        return $this;
    }
}