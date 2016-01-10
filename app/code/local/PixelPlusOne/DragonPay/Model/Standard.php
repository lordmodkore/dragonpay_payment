<?php
class PixelPlusOne_DragonPay_Model_Standard extends Mage_Payment_Model_Method_Abstract {
	protected $_code = 'dragonpay';
	protected $_isInitializeNeeded      = true;
	protected $_canUseInternal          = true;
	protected $_canUseForMultishipping  = false;
	#protected $_infoBlockType = 'dragonpay/payment_info';

	public function getCheckoutFormFields(){

		$_order = Mage::getSingleton('sales/order');
		$orderId = $this->getCheckout()->getLastRealOrderId();
		$txnid = "SD".$orderId;
		$_order->loadByIncrementId($orderId);
		$amount = number_format($_order->getGrandTotal(), 2, '.', '');
		$merchantId =  $this->getConfigData('merchant_id');
		$passwd = $this->getConfigData('merchant_password');
		$gatewayUrl = $this->getConfigData('submit_url');
		$description="Order #".$orderId." payment";
		$email =$_order->getCustomerEmail();
		$ccy=$_order->getBaseCurrencyCode();
		$digest = $this->generatePaymentDigest($merchantId,$txnid,$amount,$ccy,$description,$email,$passwd);
		$params = "merchantid=" . urlencode($merchantId) .
			"&txnid=" .  urlencode($txnid) . 
			"&amount=" .urlencode($amount) .
			"&ccy=" . urlencode($ccy) .
			"&description=" . urlencode($description) .
			"&email=" . urlencode($email) .
			"&digest=" . urlencode($digest);
		return $params;
	}
	/**
	 * [generatePaymentDigest description]
	 * @param  [string] $merchantId  	merchant name
	 * @param  [int] $orderId     		order id
	 * @param  [int] $amount      		order amount
	 * @param  [string] $ccy         	order currency code
	 * @param  [string] $description 	description
	 * @param  [string] $email       	customer email
	 * @param  [string] $passwd      	merchant passwd
	 */
	public function generatePaymentDigest($merchantId,$orderId,$amount,$ccy,$description,$email,$passwd){
		$digest_string = "$merchantId:$orderId:$amount:$ccy:$description:$email:$passwd";
		return sha1($digest_string);
	}
  	public function getUrl(){
  		$sandbox = $this->getConfigData('sandbox');
  		$url = $this->getConfigData('submit_url');
  		if($sandbox==1){
  			$url = "http://test.dragonpay.ph/Pay.aspx";
  		}
    	
    	$gatewayParams = $this->getCheckoutFormFields();
    	$url = $url."?".$gatewayParams;
    	return $url;
    }
    
	/**
	 * redirect user after order placed
	 * @return redirect url
	 */
	public function getOrderPlaceRedirectUrl() {
		   return Mage::getUrl('dragonpay/payment/redirect');
	}

	/**
	 * get checkout session
	 * @return Mage_Checkout_Model_Session
	 */
	public function getCheckout(){
		return Mage::getSingleton('checkout/session');
	}
    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }
    /**
     * [onOrderValidate description]
     * @param  Mage_Sales_Model_Order_Payment $payment [description]
     * @return [type]                                  [description]
     */
    public function onOrderValidate(Mage_Sales_Model_Order_Payment $payment)
    {
       return $this;
    }
	/**
	 * [onInvoiceCreate description]
	 * @param  Mage_Sales_Model_Invoice_Payment $payment [description]
	 * @return [type]                                    [description]
	 */
    public function onInvoiceCreate(Mage_Sales_Model_Invoice_Payment $payment)
    {

    }

}
?>