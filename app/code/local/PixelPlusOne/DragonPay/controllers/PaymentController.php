<?php

class PixelPlusOne_DragonPay_PaymentController extends Mage_Core_Controller_Front_Action {
	// The redirect action is triggered when someone places an order
	public function redirectAction() {
   		$session = Mage::getSingleton('checkout/session');
        $this->getResponse()->setBody($this->getLayout()->createBlock('dragonpay/redirect')->toHtml());
        $session->unsQuoteId();
	}
	
	// The response action is triggered when your gateway sends back a response after processing the customer's payment
	public function returnAction() {
			/* Your gateway's code to make sure the reponse you
			/* just got is from the gatway and not from some weirdo.
			/* This generally has some checksum or other checks,
			/* and is provided by the gateway.
			/* For now, we assume that the gateway's response is valid
			*/
			$data = $this->getRequest()->getParams();

			$orderId = 	substr($data['txnid'],2); // Generally sent by gateway
			$status = $data['status'];
			$payRef = "reference number";
			if(!empty($orderId)){
				$validated = true;
			}
			if($validated) {
				// Payment was successful, so update the order's state, send order email and move to the success page
				$order = Mage::getSingleton('sales/order');
				$order->loadByIncrementId($orderId);
				switch ($status){
					case "S":
						$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Gateway has authorized the payment.');
						break;
					case "P":
						$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, 'Awaiting Payment.');
						break;
					default:
						$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, 'Pending');
				}				
				$order->sendNewOrderEmail();
				$order->setEmailSent(true);
				$order->save();
				Mage::getSingleton('checkout/session')->unsQuoteId();
				Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure'=>true));

			}
			else {
				// There is a problem in the response we got
				$this->cancelAction();
				Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure'=>true));
			}
	
	}
	// The cancel action is triggered when an order is to be cancelled
	public function cancelAction() {
        if (Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
            if($order->getId()) {
				// Flag the order as 'cancelled' and save it
				$order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Gateway has declined the payment.')->save();
			}
        }
	}

	public function ipnAction() {

		if(!$this->getRequest()->isPost()){
			return;
		}
		$data = $this->getRequest()->getPost();
		$txnid 		= $data['txnid'];
		$orderId 	= substr($txnid,2);
		$status 	= $data['status'];
		$refno 		= $data['refno'];
		$message	= $data['message'];
		$digest 	= $data['digest'];
		$order = Mage::getSingleton('sales/order');
		$order->loadByIncrementId($orderId);
		$amount = number_format($order->getGrandTotal(), 2, '.', '');
		$email = $order->getCustomerEmail();
		$description="Order #".$orderId." payment";
		$ccy=$order->getBaseCurrencyCode();
		$merchantId = Mage::getStoreConfig('payment/dragonpay/merchant_id');
		$merchantPass = Mage::getStoreConfig('payment/dragonpay/merchant_password');
		$checksum = Mage::getSingleton('dragonpay/standard')->generatePaymentDigest($merchantId,$txnid,$amount,$ccy,$description,$email,$passwd);

		if(!empty($checksum)){
			switch ($status){
				case "S":
					$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true,"Reference Num:".$refno." Message:".$message);
					break;
				case "P":
					$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, "Reference Num:".$refno." Message:".$message);
					break;
				default:
					$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, "Reference Num:".$refno." Message:".$message);
			}	

			$order->sendNewOrderEmail();
			$order->setEmailSent(true);
			$order->save();
		}

		return true;
	}

}