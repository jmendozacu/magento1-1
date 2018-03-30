<?php
/**
 * Created by PhpStorm.
 * User: prabu
 * Date: 05/10/16
 * Time: 3:17 PM
 */
class Dever_Sms_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_url;
    protected $_username;
    protected $_password;
    protected $_senderId;

    public function __construct()
    {
        $this->_username = Mage::getStoreConfig('general/smsgateway/username');
        $this->_password = Mage::getStoreConfig('general/smsgateway/password');
        $this->_senderId = Mage::getStoreConfig('general/smsgateway/sender_id');
        $this->_url = Mage::getStoreConfig('general/smsgateway/api_url');
    }

    public function sendSms($order, $template)
    {
        $countryId = $order->getShippingAddress()->getCountryId();

        // Prepare Phone number with Country code
        $phone = $order->getShippingAddress()->getTelephone();
        $countryCode = $this->_getCountryCode($countryId, $phone);
        // Check if number has 0 as first occurence
        $tmp = substr($phone, 0, 1);
        if ($tmp == 0) {
            $phone = substr($phone, 1);
        }
        // Add country code as prefix
        if ($countryCode) {
            $phoneWithCountryCode = $countryCode . $phone;
        } else {
            $phoneWithCountryCode = $phone;
        }
        // Prepare Content for Sms
        $smsContent = $this->_smsTemplates($template, $order->getIncrementId());
        // Send Sms
        $responseCode = $this->_sendSms($smsContent, $phoneWithCountryCode);
        if ($responseCode != 200) {
            Mage::log("Delivery failed - {$order->getIncrementId()}", null, 'sms.log');
        }

        $response = array(
            'code' => $responseCode,
            'mobile' => $phoneWithCountryCode
        );

        return $response;
    }

    protected function _sendSms($content, $phoneNumber)
    {
        $data = array (
            'user'  => $this->_username,
            'pwd'  => $this->_password,
            'senderid' => $this->_senderId,
            'priority' => 'High',
            'CountryCode' => 'ALL'
        );

        // Extra params for sms
        $data['mobileno'] = $content;
        $data['msgtext'] = $phoneNumber;

        $url = $this->_url . "?" . http_build_query($data);
        Mage::log($url, null, 'sms.log');

        $client = curl_init($url);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($client, CURLINFO_HTTP_CODE);
        curl_close($ch);

        Mage::log($httpcode, null, 'sms.log');

        return $httpcode;
    }

    protected function _smsTemplates($entityType, $data)
    {
        switch ($entityType)
        {
            case 'order':
                $content = "Thank you for shopping from Zippo.ae.\nYour Order {$data} is under process and you will soon receive a confirmation call from our team.\nFor support call - +971566639310\nTeam Zippo";
                return $content;
                break;
            default:
                // Do Nothing
        }
    }

    protected function _getCountryCode($code, $phone)
    {
        $countryCode = array (
            'AE' => '+971'
        );

        if (in_array($phone, $countryCode)) {
            // Country code exists in phone number
            return false;
        }

        return $countryCode[$code];
    }
}