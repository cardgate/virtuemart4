<?php

/**
 * Virtuemart Card Gate Plus payment extension
 *
 * NOTICE OF LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category    VMPayment
 * @package     cgpcreditcard
 * @author      Richard Schoots <support@cardgate.com>
 * @copyright   Copyright (c) 2013 Card Gate Plus B.V. - All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
require_once dirname( __FILE__ ) . '/cardgate-clientlib-php/src/Autoloader.php';
cardgate\api\Autoloader::register();

defined('_JEXEC') or die('Direct Access to ' . basename(__FILE__) . ' is not allowed.');

if(!class_exists('VmConfig')) {
    require(JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php');
}
VmConfig::loadConfig();

if(!class_exists('vmPSPlugin')) {
    require(VMPATH_PLUGINLIBS . DS . 'vmpsplugin.php');
}

class plgVMPaymentCgpgeneric extends vmPSPlugin {

    public static $_this = false;

    /**
     * CardGatePlus plugin features
     *
     * @var mixed
     */
    protected $_plugin_version = "4.0.6";
    protected $_url = '';
    protected $_merchant_id = '';
    protected $_api_key= '';
    protected $_test_mode = false;


    /**
     * Base constructor
     *
     * @param type $subject
     * @param type $config
     */
    public function __construct(&$subject, $config) {
        parent::__construct($subject, $config);

        $jlang = JFactory::getLanguage();
        $jlang->load('plg_vmpayment_cgpideal', JPATH_ADMINISTRATOR, NULL, TRUE);
        $this->_loggable = true;
        $this->tableFields = array_keys($this->getTableSQLFields());
        $this->_tablepkey = 'id'; // virtuemart_cardgate_id';
        $this->_tableId = 'id'; // 'virtuemart_cardgate_id';

        $varsToPush = array(
            'test_mode' => array(
                '',
                'char'
            ),
            'site_id' => array(
                0,
                'int'
            ),
            'hash_key' => array(
                '',
                'char'
            ),
            'merchant_id' => array(
                '',
                'char'
            ),
            'api_key' => array(
                '',
                'char'),
            'gateway_language' => array(
                'nl',
                'char'
            ),
            'payment_currency' => array(
                0,
                'int'
            ),
            'debug' => array(
                0,
                'int'
            ),
            'log' => array(
                0,
                'int'
            ),
            'payment_logos' => array(
                '',
                'char'
            ),
            'status_pending' => array(
                '',
                'char'
            ),
            'status_success' => array(
                '',
                'char'
            ),
            'status_canceled' => array(
                '',
                'char'
            ),
            'countries' => array(
                0,
                'char'
            ),
            'min_amount' => array(
                0,
                'int'
            ),
            'max_amount' => array(
                0,
                'int'
            ),
            'cost_per_transaction' => array(
                0,
                'int'
            ),
            'cost_percent_total' => array(
                0,
                'int'
            ),
            'tax_id' => array(
                0,
                'int'
            )
        );
        $this->setConfigParameterable( $this->_configTableFieldName, $varsToPush );
    }

    /**
     * Create plugin database
     *
     * @return type
     */
    protected function getVmPluginCreateTableSQL() {
        return $this->createTableSQL('Payment ' . $this->_plugin_name . ' Table');
    }

    /**
     * Check to see if id field is of the right type
     *
     * @return null boolean
     */
    public function checkFieldID() {
        $db = JFactory::getDBO();

        $query = 'SELECT id FROM `' . $this->_tablename . '`' . ' order by id desc  LIMIT 1';
        $db->setQuery($query);

        $id = $db->loadResult();
        if (isset($id)) {
            // check fieldtype
            $query = 'SHOW COLUMNS FROM ' . $this->_tablename . ' LIKE "id"';
            $db->setQuery($query);
            $aColumn = $db->loadResultArray(1);
            $sType = $aColumn[0];
            if (substr_compare($sType, 'tinyint', 0, 7) == 0) {
                $query = 'ALTER TABLE `' . $this->_tablename . '` MODIFY id  INT(11)';
                $db->setQuery($query);
                $result = $db->loadResult();
            }
        }
        return;
    }

    /**
     * Fields for plugin database
     *
     * @return string
     */
    public function getTableSQLFields() {
        $SQLfields = array(
            'id' => ' int(11) unsigned NOT NULL AUTO_INCREMENT ',
            'virtuemart_order_id' => ' int(11) UNSIGNED ',
            'order_number' => ' char(32) ',
            'virtuemart_paymentmethod_id' => ' mediumint(1) UNSIGNED ',
            'payment_name' => ' char(255) NOT NULL DEFAULT \'\' ',
            'payment_order_total' => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',
            'payment_currency' => 'char(3) ',
            'cost_per_transaction' => ' decimal(10,2) ',
            'cost_percent_total' => ' decimal(10,2) ',
            'tax_id' => ' smallint(1) ',
            'cgp_custom' => ' varchar(255)  ',
            'cgp_response_amount' => ' int(11) ',
            'cgp_response_currency' => ' char(3) ',
            'cgp_response_transaction_id' => ' int(11) ',
            'cgp_response_ref' => ' varchar(255) ',
            'cgp_response_transaction_fee' => ' int(11) ',
            'cgp_response_billing_option' => ' varchar(32) ',
            'cgpresponse_raw' => ' longtext '
        );

        return $SQLfields;
    }

    /**
     * Redirects customer to payment page
     *
     * @param type $cart
     * @param type $order
     * @return null|boolean
     */
    public function plgVmConfirmedOrder($cart, $order) {
        
        $iPaymentmethodId = $order['details']['BT']->virtuemart_paymentmethod_id;
        if (! ($method = $this->getVmPluginMethod($iPaymentmethodId))) {
            return null;
        }
        if (! $this->selectedThisElement($method->payment_element)) {
            return false;
        }

        $this->merchant_id = (int)$method->merchant_id;
        $this->api_key = $method->api_key;
        $this->test_mode = ($method->test_mode == 'test' ? true : false);

        $method_name = substr($this->_plugin_name, 3);
        $this->set_url($method->test_mode == 'test');
        $cartItems = array();
        $products = $order['items'];
        $details = $order['details']['BT'];

        $taxTotal = 0;
        $itemTotal = 0;

        foreach ($products as $product) {
            $item = array();
            $item['quantity'] = $product->product_quantity;
            $item['sku'] = $product->order_item_sku;
            $item['name'] = $product->order_item_name;
            $price = $product->product_discountedPriceWithoutTax;
            $item['price'] = (int)round($price * 100, 0);
            $item['vat_amount'] = (int)round($product->product_tax * 100, 0);
            $item['vat_inc'] = 0;
            $item['type'] = 'product';
            $cartItems[] = $item;
            $taxTotal += (int)($item['quantity'] * $product->product_tax * 100);
            $itemTotal += $item['quantity'] * ($item['price']);

        }

        if (!empty($cart->cartPrices['shipmentValue']) && $cart->cartPrices['shipmentValue'] > 0) {
            $item = array();
            $item['quantity'] = 1;
            $item['sku'] = $cart->virtuemart_shipmentmethod_id;
            $item['name'] = 'SHIPPING';
            $item['price'] = (int)round($cart->cartPrices['shipmentValue'] * 100, 0);
            $item['vat_amount'] = (int)round($cart->cartPrices['shipmentTax'] * 100, 0);
            $item['vat_inc'] = 0;
            $item['type'] = 'shipping';
            $cartItems[] = $item;
            $taxTotal += $item['vat_amount'];
            $itemTotal += $item['price'];
        }

        if (!empty($details->coupon_discount) && $details->coupon_discount < 0) {
            $item = array();
            $item['quantity'] = 1;
            $item['sku'] = 'coupon_discount';
            $item['name'] = 'Coupon Discount';
            $item['price'] = round($details->coupon_discount * 100, 0);
            $item['vat_amount'] = 0;
            $item['vat_inc'] = 0;
            $item['type'] = 'discount';
            $cartItems[] = $item;
            $itemTotal += $item['price'];
        }

        if (!empty($details->order_payment) && $details->order_payment > 0) {
            $item = array();
            $item['quantity'] = 1;
            $item['sku'] = 'payment_fee';
            $item['name'] = 'Payment fee';
            $item['price'] = round($details->order_payment * 100, 0);
            $item['vat_amount'] = round($details->order_payment_tax * 100, 0);
            $item['vat_inc'] = 0;
            $item['type'] = 'paymentfee';
            $cartItems[] = $item;
            $taxTotal += $item['vat_amount'];
            $itemTotal += $item['price'];
        }

        $orderTax = (int)round(($details->order_tax + $details->order_shipment_tax + $details->order_payment_tax) * 100);

        if (abs($orderTax - $taxTotal) > 0){
            $item = array();
            $item['quantity'] = 1;
            $item['sku'] = 'vat_correction';
            $item['name'] = 'vat_correction';
            $item['price'] = round(($orderTax - $taxTotal), 0);
            $item['vat_amount'] = 0;
            $item['vat_inc'] = 0;
            $item['type'] = 'vatcorrection';
            $cartItems[] = $item;
        }

        $orderTotal = (int)round($order['details']['BT']->order_total * 100);
        if (abs($orderTotal - (int)$itemTotal - (int)$taxTotal)  > 0){
            $item = array();
            $item['quantity'] = 1;
            $item['sku'] = 'item_correction';
            $item['name'] = 'item_correction';
            $item['price'] = round(($orderTotal - (int)$itemTotal - (int)$taxTotal), 0);
            $item['vat_amount'] = 0;
            $item['vat_inc'] = 0;
            $item['type'] = 'correction';
            $cartItems[] = $item;
        }
        $session = JFactory::getSession();
        $return_context = $session->getId();
       // $this->_debug = $method->debug; // enable debug
        //$this->logInfo('plgVmConfirmedOrder order number: ' . $order['details']['BT']->order_number, 'message');

        // Load VM models if not already exist
        if (! class_exists('VirtueMartModelOrders')) {
            require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
        }

        if (! class_exists('VirtueMartModelCurrency')) {
            require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'currency.php');
        }

        // Load customer address details
        $new_status = '';
        $address = $order['details']['BT'];

        // Load vendor
        $vendorModel = VmModel::getModel('vendor');
        $vendorModel->setId(1);
        $vendor = $vendorModel->getVendor();
        $this->getPaymentCurrency($method);
        $q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $method->payment_currency . '" ';
        $db = JFactory::getDBO();
        $db->setQuery($q);

        // Obtain order's currency and amount
        $currency_code_3 = $db->loadResult();
        $paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
        $amount = (int)round($paymentCurrency->convertCurrencyTo($method->payment_currency, $order['details']['BT']->order_total, false) * 100);
        $cd = CurrencyDisplay::getInstance($cart->pricesCurrency);

        $this->getPaymentCurrency($method, $order['details']['BT']->payment_currency_id);
        $currency_code_3 = shopFunctions::getCurrencyByID($method->payment_currency, 'currency_code_3');
        $email_currency = $this->getEmailCurrency($method);

        $totalInPaymentCurrency = vmPSPlugin::getAmountInCurrency($order['details']['BT']->order_total,$method->payment_currency);

        // Prepare data that should be stored in the database
        $dbValues = array();
        $dbValues['payment_name'] = $this->renderPluginName($method);
        if ( !empty($method->payment_info) ) $dbValues['payment_name'] .= '<br />' . $method->payment_info;

        $dbValues['order_number'] = $order['details']['BT']->order_number;
        $dbValues['virtuemart_paymentmethod_id'] =  (int)$order['details']['BT']->virtuemart_paymentmethod_id;
        $dbValues['cgp_custom'] = $return_context;
        $dbValues['cost_per_transaction'] = $method->cost_per_transaction;
        $dbValues['cost_min_transaction'] = $method->cost_min_transaction;
        $dbValues['cost_percent_total'] = $method->cost_percent_total;
        $dbValues['payment_currency'] =  $currency_code_3;
        $dbValues['email_currency'] = $email_currency;
        $dbValues['payment_order_total'] = $totalInPaymentCurrency;
        $dbValues['tax_id'] = $method->tax_id;
        $this->storePSPluginInternalData($dbValues);







        // Validate SiteID and HashKey
        $site_id = (int)$method->site_id;
        $hash_key = $method->hash_key;
        $merchant_id = (int)$method->merchant_id;
        $api_key = $method->api_key;
        $test_mode = ($method->test_mode == 'test' ? true : false);

        if (empty($site_id)) {
            vmInfo(JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_SITE_ID_NOT_SET'));
            return false;
        }

        if (empty($hash_key)) {
            vmInfo(JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_HASH_KEY_NOT_SET'));
            return false;
        }

        if (empty($merchant_id)) {
            vmInfo(JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_MERCHANT_ID_NOT_SET'));
            return false;
        }

        if (empty($api_key)) {
            vmInfo(JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_API_KEY_NOT_SET'));
            return false;
        }

        $oCardGate = new cardgate\api\Client( $merchant_id, $api_key, $test_mode );

        $oCardGate->setIp( $_SERVER['REMOTE_ADDR'] );

        $oCardGate->setLanguage( $method->gateway_language );
        $oCardGate->version()->setPlatformName( vmVersion::$PRODUCT);
        $oCardGate->version()->setPlatformVersion( vmVersion::$RELEASE);
        $oCardGate->version()->setPluginName( $this->_plugin_name );
        $oCardGate->version()->setPluginVersion( $this->_plugin_version );

        $oTransaction = $oCardGate->transactions()->create( $site_id, $amount, $currency_code_3 );

        // Configure payment option.
        $oTransaction->setPaymentMethod( $method_name);

        // Configure customer.
        $oConsumer = $oTransaction->getConsumer();
        if ( $address->email != '' ) {
            $oConsumer->setEmail( $address->email );
        }
        if (  $address->phone_1 != '' ) {
            $oConsumer->setPhone(  $address->phone_1 );
        }
        if ( $address->first_name != '' ) {
            $oConsumer->address()->setFirstName( $address->first_name );
        }
        if ( $address->last_name != '' ) {
            $oConsumer->address()->setLastName( $address->last_name );
        }
        if ( $address->address_1!= '' ) {
            $oConsumer->address()->setAddress( $address->address_1 . (isset($address->address_2) ? ', ' . $address->address_2 : '') );
        }
        if ( $address->zip != '' ) {
            $oConsumer->address()->setZipCode( $address->zip );
        }
        if ( $address->city != '' ) {
            $oConsumer->address()->setCity( $address->city );
        }
        if ( $address->virtuemart_state_id != '' ) {
            $oConsumer->address()->setState(isset($address->virtuemart_state_id) ? ShopFunctions::getStateByID($address->virtuemart_state_id) : '');
        }
        if ($address->virtuemart_country_id != '' ) {
            $oConsumer->address()->setCountry( ShopFunctions::getCountryByID($address->virtuemart_country_id, 'country_2_code') );
        }
        if ($details->STsameAsBT == 1){
            $q=1;
        }
        $oCart = $oTransaction->getCart();

        foreach ( $cartItems as $item ) {

            switch ( $item['type'] ) {
                case 'product':
                    $iItemType = \cardgate\api\Item::TYPE_PRODUCT;
                    break;
                case 'shipping':
                    $iItemType = \cardgate\api\Item::TYPE_SHIPPING;
                    break;
                case 'paymentfee':
                    $iItemType = \cardgate\api\Item::TYPE_HANDLING;
                    break;
                case 'discount':
                    $iItemType = \cardgate\api\Item::TYPE_DISCOUNT;
                    break;
                case 'correction':
                    $iItemType = \cardgate\api\Item::TYPE_CORRECTION;
                    break;
                case 'vatcorrection':
                    $iItemType = \cardgate\api\Item::TYPE_VAT_CORRECTION;
                    break;
            }

            $oItem = $oCart->addItem( $iItemType, $item['sku'], $item['name'], (int) $item['quantity'], (int) $item['price'] );
            $oItem->setVatAmount( $item['vat_amount'] );
            $oItem->setVatIncluded( 0 );
        }

        $oTransaction->setCallbackUrl( self::getNotificationUrl($order));
        $oTransaction->setSuccessUrl( JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginResponseReceived&on=' . $order['details']['BT']->order_number . '&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id));
        $oTransaction->setFailureUrl( JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginResponseReceived&on=' . $order['details']['BT']->order_number . '&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id));
        $oTransaction->setReference( 'O' . time() . $order['details']['BT']->order_number );
        $oTransaction->setDescription( JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_ORDER_DESCRIPTION') . " " . $order['details']['BT']->order_number );

        $oTransaction->register();

        $sActionUrl = $oTransaction->getActionUrl();

        $app = JFactory::getApplication();
        if ( null !== $sActionUrl ) {
            $oModelOrder = VmModel::getModel('orders');
            $order['customer_notified'] = 1;
            $order['comments'] = '';
            // Updating the order status
            $oModelOrder->updateStatusForOneOrder($order['details']['BT']->virtuemart_order_id, $order, true);
            $cart->_confirmDone     = FALSE;
            $cart->_dataValidated   = FALSE;
            $cart->setCartIntoSession ();
            $app->redirect($sActionUrl, 301);
            $app->close();
            exit();
        } else {
            $sErrorMessage = 'CardGate error: ' .'no redirect URL';
            vmInfo(JText::_($sErrorMessage));
            return false;
        }
        exit();
    }

    /**
     * Sets VM currency
     *
     * @param type $virtuemart_paymentmethod_id
     * @param type $paymentCurrencyId
     * @return null|boolean
     */
    public function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, &$paymentCurrencyId) {
        if (! ($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
            return null;
        }

        if (! $this->selectedThisElement($method->payment_element)) {
            return false;
        }

        $this->getPaymentCurrency($method);
        $paymentCurrencyId = $method->payment_currency;
    }

    /**
     * From the plugin page, the user returns to the shop.
     * The order email is sent, and the cart emptied.
     *
     * @param type $html
     * @return null|boolean
     */
    public function plgVmOnPaymentResponseReceived(&$html) {

        // the payment itself should send the parameter needed.
        $virtuemart_paymentmethod_id = vRequest::getInt('pm', 0);

        if (! ($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing
        }

        if (! $this->selectedThisElement($method->payment_element)) {
            return false;
        }

        vmLanguage::loadJLang('com_virtuemart',true);
        vmLanguage::loadJLang('com_virtuemart_orders', TRUE);

        $order_number = vRequest::getVar('on');
        if (! $order_number) {
            return false;
        }

        if (! class_exists('VirtueMartModelOrders')) {
            require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
        }
        if (! class_exists('VirtueMartCart')) {
            require (JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
        }

        $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);
        if ($virtuemart_order_id) {
            $order = new VirtueMartModelOrders();
            $order = $order->getOrder($virtuemart_order_id);
        }

        $totalInPaymentCurrency = vmPSPlugin::getAmountInCurrency($order['details']['BT']->order_total,$method->payment_currency);
        $payment_name = $this->renderPluginName($method);

        $orderlink='';
        $tracking = VmConfig::get('ordertracking','guests');
        if($tracking !='none' and !($tracking =='registered' and empty($order['details']['BT']->virtuemart_user_id) )) {

            $orderlink = 'index.php?option=com_virtuemart&view=orders&layout=details&order_number=' . $order['details']['BT']->order_number;
            if ($tracking == 'guestlink' or ($tracking == 'guests' and empty($order['details']['BT']->virtuemart_user_id))) {
                $orderlink .= '&order_pass=' . $order['details']['BT']->order_pass;
            }
        }

        $html = $this->renderByLayout('post_payment', array(
            'order_number' =>$order['details']['BT']->order_number,
            'order_pass' =>$order['details']['BT']->order_pass,
            'payment_name' => $payment_name,
            'displayTotalInPaymentCurrency' => $totalInPaymentCurrency['display'],
            'orderlink' =>$orderlink,
            'method' => $method
        ));

        // Display order info
        /*
        $payment_name = $this->renderPluginName($method);
        $html = $this->getPaymentResponseHtml($order, $payment_name);
        */
        $code = vRequest::getVar('code');
        if ($code >=200 && $code < 300) {
            vmInfo( JText::_( 'VMPAYMENT_' . strtoupper( $this->_plugin_name ) . '_PAYMENT_SUCCESS' ) );
            // Get the correct cart / session
            $cart = VirtueMartCart::getCart();
            // Clear cart
            $cart->emptyCart();
            vRequest::setVar ('html', $html);
        }
        if ($code >=300 && $code <400){
            if ($code == 309){
                vRequest::setVar('option','com_virtuemart');
                vRequest::setVar('view','cart');
            } else {
                $this->handlePaymentUserCancel( $virtuemart_order_id );
            }
        }
        if ($code == 0 || ($code >= 700 && $code < 800)){
            vmInfo( JText::_( 'VMPAYMENT_' . strtoupper( $this->_plugin_name ) . '_PAYMENT_PENDING' ) );
        }
        return true;
    }


    /**
    * process User Payment Cancel
    *
    * @return  boolean|null
    * @since   1.0.0
    */
    function plgVmOnUserPaymentCancel () {

        if (! class_exists('VirtueMartModelOrders')) {
            require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
        }

        $order_number = vRequest::getVar('on');
        if (! $order_number) {
            return false;
        }
        $pm = vRequest::getVar('pm');
        if (! $pm) {
            return false;
        }

        $db = JFactory::getDBO();
        $query = 'SELECT #__virtuemart_paymentmethods .`payment_element` FROM #__virtuemart_paymentmethods' . " WHERE  `virtuemart_paymentmethod_id`= '" . $pm . "'";
        $db->setQuery($query);
        $payment_element = $db->loadResult();

        if (! $payment_element) {
            return null;
        }
        if ($this->_name == $payment_element){
            $query = 'SELECT #__virtuemart_payment_plg_' . $payment_element . '.`virtuemart_order_id` FROM ' . '#__virtuemart_payment_plg_' . $payment_element . " WHERE  `order_number`= '" . $order_number . "'";
            $db->setQuery($query);
            $virtuemart_order_id = $db->loadResult();
            if (! $virtuemart_order_id) {
                return null;
            }
              $this->handlePaymentUserCancel($virtuemart_order_id);
            return true;
        }
    }

    /**
     * This event is fired by Offline Payment.
     * It can be used to validate the payment data as entered by the user.
     *
     * @return boolean|null
     */
    public function plgVmOnPaymentNotification() {

        $pt = vRequest::getString('pt','');
        $pt = $pt == 'directebanking' ? 'sofortbanking' : $pt;

        $plugin_name = substr($this->_plugin_name, 3);
        if ($plugin_name != $pt){
            return null;
        }

        if (!class_exists('VirtueMartModelOrders')) {
            require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
        }
        $virtuemart_paymentmethod_id = vRequest::getInt('pm', 0);

        //$this->_debug=true;
        if (!($this->_currentMethod = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
            return NULL; // Another method was selected, do nothing
        }

        $order_number = vRequest::getString('on', '');
        if (empty($order_number)) {
            return FALSE;
        }

        if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number))) {
            return FALSE;
        }

        $pt = vRequest::getString('pt','');
        $pt = $pt == 'directebanking' ? 'sofortbanking' : $pt;

        $plugin_name = substr($this->_plugin_name, 3);
        if ($plugin_name == $pt) {
            foreach (glob(JPATH_VM_ADMINISTRATOR . DS . 'tables' . DS . "*.php") as $filename) {
                require_once ($filename);
            }
            if (! class_exists('VirtueMartModelOrders')) {
                require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
            }

            $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);
            // Send email to Admins if Order was not found
            if (! $virtuemart_order_id) {
                $this->_debug = true; // force debug here
                $this->logInfo('plgVmOnCgpCallback: virtuemart_order_id not found ', 'ERROR');
                // send an email to admin, and ofc not update the order status: exit is fine
                $this->sendEmailToVendorAndAdmins(JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_ERROR_EMAIL_SUBJECT'), JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_UNKNOW_ORDER_ID'));
                exit();
            } else {
                $this->logInfo('plgVmOnCgpCallback: virtuemart_order_id  found ' . $virtuemart_order_id, 'message');
            }

            $thisOrder = new VirtueMartModelOrders();
            $thisOrder = $thisOrder->getOrder($virtuemart_order_id);
            $method = $this->getVmPluginMethod($thisOrder['details']['BT']->virtuemart_paymentmethod_id);
            if (! $this->selectedThisElement($method->payment_element)) {
                return false;
            }

            if ($thisOrder['details']['BT']->order_status != $method->status_success &&  $thisOrder['details']['BT']->order_status !=$method->status_canceled && $thisOrder['details']['BT']->order_status != 'X') {
                // Send email if HASH verification failed
                $hashString = ($method->test_mode == 'test' ? 'TEST' : '') . vRequest::getString('transaction') . vRequest::getString('currency') . vRequest::getInt('amount') . vRequest::getString('reference') . vRequest::getInt('code') . $method->hash_key;

                if (md5($hashString) != vRequest::getString('hash')) {
                    $this->_debug = true; // force debug here
                    $this->logInfo('plgVmOnCgpCallback: invalid hash ', 'ERROR');
                    // send an email to admin, and ofc not update the order status: exit is fine
                    $this->sendEmailToVendorAndAdmins(JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_ERROR_EMAIL_SUBJECT'), JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_INVALID_HASH '));
                    exit('invalid hash!');
                }

                $this->_debug = $method->debug;

                // Get all know columns of the table
                $db = JFactory::getDBO();
                $columns = $db->getTableColumns($this->_tablename);
                $post_msg = '';

                // Save all cgp_response_<field> data
                $response_fields = array();
                $data = $_GET;
                foreach ($data as $key => $value) {
                    $post_msg .= $key . "=" . $value . "<br />";
                    $table_key = 'cgp_response_' . $key;
                    if (in_array($table_key, $columns)) {
                        $response_fields[$table_key] = $value;
                    }
                }
/*
                $response_fields['payment_name'] = $this->renderPluginName($method);
                $response_fields['cgpresponse_raw'] = var_export($data, true);
                $response_fields['order_number'] = $order_number;
                $response_fields['virtuemart_order_id'] = $virtuemart_order_id;
                $this->storePSPluginInternalData($response_fields);
*/
                // Update Order status
                $code = vRequest::getInt('code');
                if ($code >=0 && $code <200){
                    $new_status = $method->status_pending;
                    $comments = JText::sprintf('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_PAYMENT_PENDING', $order_number);
                }
                if ($code >=200 && $code <300){
                    $new_status = $method->status_success;
                    $comments   = JText::sprintf( 'VMPAYMENT_' . strtoupper( $this->_plugin_name ) . '_PAYMENT_SUCCESS', $order_number );
                }
                if ($code >=300 && $code <400){
                    // X is the dafault canceled value
                    $new_status = $data['code']==309 ? 'X': $method->status_canceled;
                    $comments   = JText::sprintf( 'VMPAYMENT_' . strtoupper( $this->_plugin_name ) . '_PAYMENT_FAILED', $order_number );
                }
                if ($code >=700 && $code <800){
                    $new_status = 'U'; //confirmed by shopper
                    $comments = JText::sprintf('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_PAYMENT_PENDING', $order_number);
                }
                $this->logInfo('plgVmOnCgpCallback: return new_status:' . $new_status, 'message');
                $order['order_status'] = $new_status;
                $order['virtuemart_order_id'] = $virtuemart_order_id;
                $order['customer_notified'] = 1;
                $order['comments'] = $comments;
                $modelOrder = VmModel::getModel('orders');
                $modelOrder->updateStatusForOneOrder( $virtuemart_order_id, $order, true );
                echo $data['transaction'].'.'.$data['code'];
            } else {
                echo 'payment already processed.';
            }
            die;
        } else {
            return null;
        }
    }

    /**
     * This is custom trigger especially for Card Gate Plus to handle callback
     * `com_cgp` component needed.
     *
     * @param string $option
     * @param int $status
     * @return boolean
     */
    public function plgVmOnCgpCallback($data) {

        // correct for sofortbanking if necessary
        if ($data['pt'] == 'directebanking') {
            $data['pt'] = 'sofortbanking';
        }

        // Process only correct payment option
        $plugin_name = substr($this->_plugin_name, 3);
        if ($plugin_name == $data['pt']) {
            foreach (glob(JPATH_VM_ADMINISTRATOR . DS . 'tables' . DS . "*.php") as $filename) {
                require_once ($filename);
            }
            if (! class_exists('VirtueMartModelOrders')) {
                require (JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
            }

            $order_number = trim(substr($data['reference'],11));

            $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);
            // Send email to Admins if Order was not found
            if (! $virtuemart_order_id) {
                $this->_debug = true; // force debug here
                $this->logInfo('plgVmOnCgpCallback: virtuemart_order_id not found ', 'ERROR');
                // send an email to admin, and ofc not update the order status: exit is fine
                $this->sendEmailToVendorAndAdmins(JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_ERROR_EMAIL_SUBJECT'), JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_UNKNOW_ORDER_ID'));
                exit();
            } else {
                $this->logInfo('plgVmOnCgpCallback: virtuemart_order_id  found ' . $virtuemart_order_id, 'message');
            }

            $thisOrder = new VirtueMartModelOrders();
            $thisOrder = $thisOrder->getOrder($virtuemart_order_id);
            $method = $this->getVmPluginMethod($thisOrder['details']['BT']->virtuemart_paymentmethod_id);
            if (! $this->selectedThisElement($method->payment_element)) {
                return false;
            }

            if ($thisOrder['details']['BT']->order_status != $method->status_success &&  $thisOrder['details']['BT']->order_status !=$method->status_canceled && $thisOrder['details']['BT']->order_status != 'X') {
                // Send email if HASH verification failed
                $hashString = ($method->test_mode == 'test' ? 'TEST' : '') . $data['transaction'] . $data['currency'] . $data['amount'] . $data['reference'] . $data['code'] . $method->hash_key;

                if (md5($hashString) != $data['hash']) {
                    $this->_debug = true; // force debug here
                    $this->logInfo('plgVmOnCgpCallback: invalid hash ', 'ERROR');
                    // send an email to admin, and ofc not update the order status: exit is fine
                    $this->sendEmailToVendorAndAdmins(JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_ERROR_EMAIL_SUBJECT'), JText::_('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_INVALID_HASH '.$hashString));
                    exit('invalid hash!');
                }

                $this->_debug = $method->debug;

                // Get all know columns of the table
                $db = JFactory::getDBO();
                $columns = $db->getTableColumns($this->_tablename);
                $post_msg = '';

                // Save all cgp_response_<field> data
                $response_fields = array();
                foreach ($data as $key => $value) {
                    $post_msg .= $key . "=" . $value . "<br />";
                    $table_key = 'cgp_response_' . $key;
                    if (in_array($table_key, $columns)) {
                        $response_fields[$table_key] = $value;
                    }
                }

                $response_fields['payment_name'] = $this->renderPluginName($method);
                $response_fields['cgpresponse_raw'] = var_export($data, true);
                $response_fields['order_number'] = $order_number;
                $response_fields['virtuemart_order_id'] = $virtuemart_order_id;
                $this->storePSPluginInternalData($response_fields);

                // Update Order status
                if ($data['code'] >=0 && $data['code'] <200){
                    $new_status = $method->status_pending;
                    $comments = JTExt::sprintf('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_PAYMENT_PENDING', $order_number);
                }
                if ($data['code'] >=200 && $data['code'] <300){
                    $new_status = $method->status_success;
                    $comments   = JTExt::sprintf( 'VMPAYMENT_' . strtoupper( $this->_plugin_name ) . '_PAYMENT_SUCCESS', $order_number );
                }
                if ($data['code'] >=300 && $data['code'] <400){
                    // X is the dafault canceled value
                    $new_status = $data['code']==309 ? 'X': $method->status_canceled;
                    $comments   = JTExt::sprintf( 'VMPAYMENT_' . strtoupper( $this->_plugin_name ) . '_PAYMENT_FAILED', $order_number );
                }
                if ($data['code'] >=700 && $data['code'] <800){
                    $new_status = 'U'; //confirmed by shopper
                    $comments = JTExt::sprintf('VMPAYMENT_' . strtoupper($this->_plugin_name) . '_PAYMENT_PENDING', $order_number);
                }
                $this->logInfo('plgVmOnCgpCallback: return new_status:' . $new_status, 'message');
                $modelOrder = VmModel::getModel('orders');
                $order['order_status'] = $new_status;
                $order['virtuemart_order_id'] = $virtuemart_order_id;
                $order['customer_notified'] = 1;
                $order['comments'] = $comments;
                $modelOrder->updateStatusForOneOrder( $virtuemart_order_id, $order, true );
                return $data['transaction'].'.'.$data['code'];
            } else {
                return('payment already processed');
            }
        } else {
            return null;
        }
    }

    /**
     * Display stored payment data for an order
     *
     * @see components/com_virtuemart/helpers/vmPSPlugin::plgVmOnShowOrderBEPayment()
     * @param type $virtuemart_order_id
     * @param type $payment_method_id
     * @return null|string
     */
    public function plgVmOnShowOrderBEPayment($virtuemart_order_id, $payment_method_id) {
        if (! $this->selectedThisByMethodId($payment_method_id)) {
            return null; // Another method was selected, do nothing
        }

        $db = JFactory::getDBO();
        $q = 'SELECT * FROM `' . $this->_tablename . '`' . ' WHERE `virtuemart_order_id` = ' . $virtuemart_order_id;
        $db->setQuery($q);
        if (! ($paymentTable = $db->loadObject())) {
            return '';
        }

        $this->getPaymentCurrency($paymentTable);
        $q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $paymentTable->payment_currency . '" ';
        $db = JFactory::getDBO();
        $db->setQuery($q);
        $currency_code_3 = $db->loadResult();
        $html = '<table class="adminlist">' . "\n";
        $html .= $this->getHtmlHeaderBE();
        $html .= $this->getHtmlRowBE(strtoupper($this->_plugin_name) . '_PAYMENT_NAME', $paymentTable->payment_name);
        /*
         * $code = "cgp_response_";
         *
         * foreach ($paymentTable as $key => $value) {
         * if (substr($key, 0, strlen($code)) == $code) {
         * $html .= $this->getHtmlRowBE($key, $value);
         * }
         * }
         */
        $html .= '</table>' . "\n";

        return $html;
    }

    /**
     * @param $security
     * @param $order
     *
     * @return string
     */
    static function getNotificationUrl ($order) {

        return JURI::root()  .  "index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component&pm=" . $order['details']['BT']->virtuemart_paymentmethod_id . '&on=' . $order['details']['BT']->order_number .'&lang='.vRequest::getCmd('lang','');
    }

    /**
     * @param $currency
     * @param $payment_method
     *
     * @return bool
     */
    private function checkPaymentCurrency($currency,$payment_method) {
        $strictly_euro = in_array($payment_method,['cardgateideal',
            'cardgateidealqr',
            'cardgatebancontact',
            'cardgatebanktransfer',
            'cardgatebillink',
            'cardgatesofortbanking',
            'cardgatedirectdebit',
            'cardgateonlineueberweisen',
            'cardgatespraypay']);
        if ($strictly_euro && $currency != 'EUR') return false;

        $strictly_pln = in_array($payment_method,['cardgateprzelewy24']);
        if ($strictly_pln && $currency != 'PLN') return false;

        return true;
    }

    /**
     * Check if the payment conditions are fulfilled for this payment method
     *
     * @param type $cart
     * @param type $method
     * @param type $cart_prices
     * @return boolean true if the conditions are fulfilled, false otherwise
     */
    protected function checkConditions($cart, $method, $cart_prices) {
        $address        = (($cart->ST == 0) ? $cart->BT : $cart->ST);
        $amount         = $cart_prices['salesPrice'];
        $amount_cond    = ($amount >= $method->min_amount and $amount <= $method->max_amount or ($method->min_amount <= $amount and ($method->max_amount == 0)));

        $payment_method = 'cardgate'.substr($this->_name, 3);
        $currency       = shopFunctions::getCurrencyByID($cart->pricesCurrency, 'currency_code_3');
        $currency_cond  = $this->checkPaymentCurrency($currency, $payment_method);

        $countries = array();
        if (! empty($method->countries)) {
            if (! is_array($method->countries)) {
                $countries[0] = $method->countries;
            } else {
                $countries = $method->countries;
            }
        }

        if (! is_array($address)) {
            $address = array();
            $address['virtuemart_country_id'] = 0;
        }

        if (! isset($address['virtuemart_country_id'])) {
            $address['virtuemart_country_id'] = 0;
        }

        if (in_array($address['virtuemart_country_id'], $countries) || count($countries) == 0) {
            if ($amount_cond && $currency_cond) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return Gateway Url
     *
     * @return string
     */
    protected function getGatewayUrl() {
        return $this->_url;
    }

    /**
     * *************************************************
     * We must reimplement this triggers for joomla 1.7
     * *************************************************
     */

    /**
     * Create the table for this plugin if it does not yet exist.
     * This functions checks if the called plugin is active one.
     * When yes it is calling the standard method to create the tables
     *
     * @author Valérie Isaksen
     */
    public function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {
        return $this->onStoreInstallPluginTable($jplugin_id);
    }

    /**
     * This event is fired after the payment method has been selected.
     * It can be used to store
     * additional payment info in the cart.
     *
     * @author Max Milbers
     * @author Valérie isaksen
     * @param VirtueMartCart $cart:
     *            the actual cart
     * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
     */
    public function plgVmOnSelectCheckPayment(VirtueMartCart $cart) {
        return $this->OnSelectCheck($cart);
    }

    /**
     * plgVmDisplayListFEPayment
     * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for example
     * On errors, JError::raiseWarning (or JError::raiseError) must be used to set a message.
     *
     * @param object $cart
     *            Cart object
     * @param integer $selected
     *            ID of the method selected
     * @return boolean True on succes, false on failures, null when this plugin was not selected.
     * @author Valerie Isaksen
     * @author Max Milbers
     */
    public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn) {

        if ($this->getPluginMethods($cart->vendorId) === 0) {
            if (empty($this->_name)) {
                $app = JFactory::getApplication();
                $app->enqueueMessage(vmText::_('COM_VIRTUEMART_CART_NO_' . strtoupper($this->_psType)));
                return false;
            } else {
                return false;
            }
        }

        $method_name = $this->_psType . '_name';
        $idN = 'virtuemart_'.$this->_psType.'method_id';

        foreach ($this->methods as $this->_currentMethod) {
            if ($this->checkConditions($cart, $this->_currentMethod, $cart->cartPrices)) {

                $html = '';
                $cartPrices=$cart->cartPrices;
                if (isset($this->_currentMethod->cost_method)) {
                    $cost_method=$this->_currentMethod->cost_method;
                } else {
                    $cost_method=true;
                }
                $methodSalesPrice = $this->setCartPrices($cart, $cartPrices, $this->_currentMethod, $cost_method);

                $this->_currentMethod->payment_currency = $this->getPaymentCurrency($this->_currentMethod);
                $this->_currentMethod->$method_name = $this->renderPluginName($this->_currentMethod);

                if (!$this->_currentMethod->itemise_in_cart and $this->_currentMethod->paypalproduct=='exp'){
                    continue;
                }
                $html .= $this->getPluginHtml($this->_currentMethod, $selected, $methodSalesPrice);

                $htmlIn[$this->_psType][$this->_currentMethod->$idN] =$html;

            }
            return true;
        }
        return $this->displayListFE($cart, $selected, $htmlIn);
    }

    /**
     * Calculate transaction costs
     *
     * @param VirtueMartCart $cart
     * @param type $method
     * @param type $cart_prices
     * @return type
     */

    public function getCosts(VirtueMartCart $cart, $method, $cart_prices) {
        if (preg_match('/%$/', $method->cost_percent_total)) {
            $cost_percent_total = substr($method->cost_percent_total, 0, - 1);
        } else {
            $cost_percent_total = $method->cost_percent_total;
        }

        return ($method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
    }

    /**
     * plgVmonSelectedCalculatePricePayment
     * Calculate the price (value, tax_id) of the selected method
     * It is called by the calculator
     * This function does NOT to be reimplemented.
     * If not reimplemented, then the default values from this function are taken.
     *
     * @author Valerie Isaksen
     * @param VirtueMartCart $cart
     *            the current cart
     * @param array $cart_prices
     *            the new cart prices
     * @param array $cart_prices_name
     *            the new cart prices
     * @return boolean|null if the method was not selected, false if the shipping rate is not valid any more, true otherwise
     */
    public function plgVmOnSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices,
        &$cart_prices_name) {

        if(!($this->selectedThisByMethodId($cart->virtuemart_paymentmethod_id))) {
            return NULL; // Another method was selected, do nothing
        }

        $method =  $this->getVmPluginMethod($cart->virtuemart_paymentmethod_id);
        $cart_prices_name = $this->renderPluginName($method);
        $this->setCartPrices($cart, $cart_prices, $method);
        return TRUE;
    }

    /**
     * plgVmOnCheckAutomaticSelectedPayment
     * Checks how many plugins are available.
     * If only one, the user will not have the choice. Enter edit_xxx page
     * The plugin must check first if it is the correct type
     *
     * @author Valerie Isaksen
     * @param
     *            VirtueMartCart cart: the cart object
     * @return null if no plugin was found, 0 if more then one plugin was found, virtuemart_xxx_id if only one plugin is found
     */
    public function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array()) {
        return $this->onCheckAutomaticSelected($cart, $cart_prices);
    }

    /**
     * This method is fired when showing the order details in the frontend.
     * It displays the method-specific data.
     *
     * @param integer $order_id
     *            The order ID
     * @return mixed Null for methods that aren't active, text (HTML) otherwise
     * @author Max Milbers
     * @author Valerie Isaksen
     */
    public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {
        $this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
    }

    /**
     * This method is fired when showing when priting an Order
     * It displays the the payment method-specific data.
     *
     * @param integer $_virtuemart_order_id
     *            The order ID
     * @param integer $method_id
     *            method used for this order
     * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
     * @author Valerie Isaksen
     */
    public function plgVmonShowOrderPrintPayment($order_number, $method_id) {
        return $this->onShowOrderPrint($order_number, $method_id);
    }

    function plgVmDeclarePluginParamsPaymentVM3(&$data) {
        return $this->declarePluginParams('payment', $data);
    }

    public function plgVmSetOnTablePluginParamsPayment($name, $id, &$table) {
        return $this->setOnTablePluginParams($name, $id, $table);
    }

    private function set_url($test) {
        if ($test) {
            $this->_url = 'https://secure-staging.curopayments.net/gateway/cardgate/';
        } else {
            $this->_url = 'https://secure.curopayments.net/gateway/cardgate/';
        }
    }

    static function getPaymentCurrency (&$method, $selectedUserCurrency = false) {

        if (empty($method->payment_currency)) {
            $vendor_model = VmModel::getModel('vendor');
            $vendor = $vendor_model->getVendor($method->virtuemart_vendor_id);
            $method->payment_currency = $vendor->vendor_currency;
            return $method->payment_currency;
        } else {

            $vendor_model = VmModel::getModel( 'vendor' );
            $vendor_currencies = $vendor_model->getVendorAndAcceptedCurrencies( $method->virtuemart_vendor_id );

            if(!$selectedUserCurrency) {
                if($method->payment_currency == -1) {
                    $mainframe = JFactory::getApplication();
                    $selectedUserCurrency = $mainframe->getUserStateFromRequest( "virtuemart_currency_id", 'virtuemart_currency_id', vRequest::getInt( 'virtuemart_currency_id', $vendor_currencies['vendor_currency'] ) );
                } else {
                    $selectedUserCurrency = $method->payment_currency;
                }
            }

            $vendor_currencies['all_currencies'] = explode(',', $vendor_currencies['all_currencies']);
            if(in_array($selectedUserCurrency,$vendor_currencies['all_currencies'])){
                $method->payment_currency = $selectedUserCurrency;
            } else {
                $method->payment_currency = $vendor_currencies['vendor_currency'];
            }

            return $method->payment_currency;
        }

    }

    protected function renderPluginName($activeMethod) {
        $return = '';
        $plugin_name = $this->_psType . '_name';
        $plugin_desc = $this->_psType . '_desc';
        $description = '';
        // 		$params = new JParameter($plugin->$plugin_params);
        // 		$logo = $params->get($this->_psType . '_logos');
        $logosFieldName = $this->_psType . '_logos';
        $logos = $activeMethod->$logosFieldName;
        if (!empty($logos)) {
            $return = $this->displayLogos($logos) . ' ';
        }
        $pluginName = $return . '<span class="' . $this->_type . '_name">' . $activeMethod->$plugin_name . '</span>';
        if ($activeMethod->sandbox) {
            $pluginName .= ' <span style="color:rgba(255,0,0,0);font-weight:bold">Sandbox (' . $activeMethod->virtuemart_paymentmethod_id . ')</span>';
        }
        if (!empty($activeMethod->$plugin_desc)) {
            $pluginName .= '<span class="' . $this->_type . '_description">' . $activeMethod->$plugin_desc . '</span>';
        }
        return $pluginName;
    }

    protected function getPluginHtml ($plugin, $selectedPlugin, $pluginSalesPrice) {

        $pluginmethod_id = $this->_idName;
        $pluginName = $this->_psType . '_name';
        if ($selectedPlugin == $plugin->{$pluginmethod_id}) {
            $checked = 'checked="checked"';
        } else {
            $checked = '';
        }
        $this->_currentMethod = $plugin;
        $currency = CurrencyDisplay::getInstance ();
        $costDisplay = "";
        if ($pluginSalesPrice) {
            $costDisplay = $currency->priceDisplay( $pluginSalesPrice );
            $t = vmText::_( 'COM_VIRTUEMART_PLUGIN_COST_DISPLAY' );
            if(strpos($t,'/')!==FALSE){
                list($discount, $fee) = explode( '/', vmText::_( 'COM_VIRTUEMART_PLUGIN_COST_DISPLAY' ) );
                if($pluginSalesPrice>=0) {
                    $costDisplay = '<span class="'.$this->_type.'_cost fee"> ('.$fee.' +'.$costDisplay.")</span>";
                } else if($pluginSalesPrice<0) {
                    $costDisplay = '<span class="'.$this->_type.'_cost discount"> ('.$discount.' -'.$costDisplay.")</span>";
                }
            } else {
                $costDisplay = '<span class="'.$this->_type.'_cost fee"> ('.$t.' '.$costDisplay.")</span>";
            }
        }

        $dynUpdate='';
        if( VmConfig::get('oncheckout_ajax',false)) {
            $dynUpdate=' data-dynamic-update="1" ';
        }

        $html = '<input type="radio" '.$dynUpdate.' name="' . $pluginmethod_id . '" id="' . $this->_psType . '_id_' . $plugin->$pluginmethod_id . '"   value="' . $plugin->$pluginmethod_id . '" ' . $checked . ">\n"
                . '<label for="' . $this->_psType . '_id_' . $plugin->$pluginmethod_id . '">' . '<span class="' . $this->_type . '">' . $plugin->$pluginName . $costDisplay . "</span></label>\n";

        return $html;
    }
}