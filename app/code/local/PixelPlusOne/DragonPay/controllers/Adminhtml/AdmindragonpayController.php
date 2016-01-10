<?php
	class PixelPlusOne_DragonPay_Adminhtml_AdmindragonpayController extends Mage_Adminhtml_Controller_Action
	{
		public function queryAction(){
	    	$order_id = $this->getRequest()->getParam('order_id');
	    	$_order = Mage::getSingleton('sales/order')->load($order_id);
			$increment_id = $_order->getIncrementId()	;
			$store_id = $_order->getData('store_id');
			$payment_method = $_order->getPayment()->getMethodInstance();
			$api_merchantid = $payment_method->getConfigData('merchant_id',$store_id);
			$sandbox = $payment_method->getConfigData('sandbox');
			$api_url = 'https://gw.dragonpay.ph/MerchantRequest.aspx?';
			if($sandbox==1){
				$api_url = 'http://test.dragonpay.ph/MerchantRequest.aspx?';
			}
			$api_password = $payment_method->getConfigData('merchant_password',$store_id);
			$txnid = "SD".$increment_id;
			$error_msg='';

			if($api_merchantid==''){
				$error_msg .= '- Merchant Id is not set. <br/>';	
			}
			if($api_merchantid == ''){
				$error_msg .= '- API Username is not set. <br/>';
			}
			if($api_password == ''){
				$error_msg .= '- API Password is not set. <br/>';
			}
			if($error_msg != ''){
					//display module parameter errors
					echo '<b>MODULE SETUP ERROR:</b><br/>' . $error_msg ;
					echo '<br/>';
			}else{
				$postUrl = $api_url;
				$postData = "op=GETSTATUS&merchantid=".$api_merchantid."&merchantpwd=".$api_password."&txnid=".$txnid;
			}

			$response = $this->_httpPost($postUrl, $postData);

			$comment = "";
			


				if($response=="S"){
					$comment = "Payment Successful. We are processing your order.";
					$_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $comment, 1)->save(); 
							try {
								if(!$_order->canInvoice())
									{
										Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
									}
								 
									$invoice = Mage::getModel('sales/service_order', $_order)->prepareInvoice();
								 
								if (!$invoice->getTotalQty()) 
									{
										Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
									}
								$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
								$invoice->register();
								$transactionSave = Mage::getModel('core/resource_transaction')
								->addObject($invoice)
								->addObject($invoice->getOrder());
								 
								$transactionSave->save();
							}
							catch (Mage_Core_Exception $e) {
							 
							}
				}
				if($response=="P"){
					$comment = "Pending Payment. We can't process your order right now.";
					$_order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, $comment, 1)->save(); 
				}
				if($response=="U"){
					$comment = "Waiting for Payment Update. ";
					$_order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, $comment, 1)->save(); 
				}
		
	
                $this->_getSession()->addSuccess(
                    Mage::helper('magentostudy_news')->__('Payment status updated')
                );

			$this->_redirect('adminhtml/sales_order/view/',  array('order_id'=>$_order->getId()));

		}

    	protected function _httpPost($postUrl, $postData , $isRequestHeader=false)
	    {    
		    $ch = curl_init();
		    curl_setopt($ch, CURLOPT_URL, $postUrl);
		    curl_setopt($ch, CURLOPT_POST, 1);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
		    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		    curl_setopt($ch, CURLOPT_HEADER, (($isRequestHeader) ? 1 : 0));
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		    $response = curl_exec($ch);	
		    curl_close($ch);
		    return $response;
		}
	protected function _isAllowed()
			{
				return true;

			}

	}
?>