<?php

class Mara_Api_Model_Observer
{
	const API_FORWARD_ORDER = 'forward';
	const MARA_API_ORDER_ID = 'orderID';
	const MARA_API_CUSTOMER_NAME = 'customerName';
	const MARA_API_CUSTOMER_CONTACT = 'customerContact';
	const MARA_API_CUSTOMER_ADDRESS = 'customerAddress';
	const MARA_API_CUSTOMER_CITY = 'customerCity';
	const MARA_API_CUSTOMER_EMAIL = 'customerEmail';
	const MARA_API_ORDER_AMOUNT = 'orderAmount';
	const MARA_API_ORDER_TYPE = 'orderType';
	const MARA_API_PAYMENT_METHOD = 'paymentMethod';
	const MARA_API_PRODUCT_DESCRIPTION = 'productDescription';
	const MARA_API_COMMENTS = 'comments';
	const MARA_API_AREA = 'area';
	const HIDDEN_AWB_NUMBER_CUSTOM_FIELD = '_maraAwbNumber'; // this is hidden to prevent accident modification.
	const AWB_NUMBER_CUSTOM_FIELD = 'maraAwbNumber'; // field to show admin

	const SETTINGS_OPTION_GROUP = 'mara_api_settings_options';
	const DELIVERED_STATUS = 'Delivered to end customer';


	public function sendOrder(Varien_Event_Observer $observer)
	{
        $debug = true;
		$invoice = $observer->getEvent()->getInvoice();
		$order = $invoice->getOrder();
		$address = $order->getShippingAddress();
		$custAddr = $address->getFormated();
		$city = $address->getRegion();
		$contact = $this->_buildPhoneNumber($address->getTelephone());
		$custName = $address->getName();
		$email = $order->getCustomerEmail();
		$total = $order->getGrandTotal();
		$order_number = $order->getIncrementId();
		$payment_method = $order->getPayment()->getMethodInstance()->getTitle();
		$area = $address->getCity();

        $titles = array();
		foreach ($order->getAllItems() as $item) {
            $titles[] = $item->getName();
        }
        $productTitles = implode(':', $titles);

		if ($payment_method == 'Cash On Delivery') {
			$payment_method = 'cod';
		} else {
			$payment_method = 'prepaid';
		}

		$data = array(
            self::MARA_API_CUSTOMER_ADDRESS => $custAddr,
            self::MARA_API_CUSTOMER_CITY =>  $city,
            self::MARA_API_CUSTOMER_CONTACT => $contact,
            self::MARA_API_CUSTOMER_EMAIL => $email,
            self::MARA_API_CUSTOMER_NAME => $custName,
            self::MARA_API_ORDER_AMOUNT => $total,
            self::MARA_API_ORDER_ID => (string)$order_number,
            self::MARA_API_ORDER_TYPE => self::API_FORWARD_ORDER,
            self::MARA_API_PAYMENT_METHOD => $payment_method,
            self::MARA_API_PRODUCT_DESCRIPTION => $productTitles,
            self::MARA_API_AREA => $area,
            self::MARA_API_COMMENTS => 'NA'
        );
		$apiUrl = Mage::getStoreConfig('general/mara_api/api_url');
		$apiKey = Mage::getStoreConfig('general/mara_api/api_key');

		$url = $apiUrl . 'orders?apiKey=' . $apiKey;
		$_data = json_encode($data);

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        Mage::log($response, null, 'mara.log');
    }

	protected function _buildPhoneNumber($phone)
    {
        $countryCode = '+971';
        // Check if number has 0 as first occurence
        $tmp = substr($phone, 0, 1);
        if ($tmp == 0) {
            $phone = substr($phone, 1);
        }

        return $countryCode . $phone;
    }
}
