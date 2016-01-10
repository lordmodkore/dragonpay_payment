<?php 
	class PixelPlusOne_DragonPay_Block_Payment_Info extends Mage_Payment_Block_Info
	{
	    protected function _construct()
	    {
	        parent::_construct();
	        $this->setTemplate('dragonpay/info.phtml');
	    }
	} 
