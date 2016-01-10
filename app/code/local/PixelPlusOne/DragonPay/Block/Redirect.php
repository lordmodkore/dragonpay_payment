<?php 
class PixelPlusOne_Dragonpay_Block_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $dragonpay = Mage::getModel('dragonpay/standard');

        $form = new Varien_Data_Form();
        $form->setAction($dragonpay->getUrl())
            ->setId('dragonpay_checkout')
            ->setName('dragonpay_checkout')
            ->setMethod('post')
            ->setUseContainer(true);
        $html = '<html><body>';
        $html.= $this->__('You will be redirected to the payment gateway in a few seconds.');
        $html.= $form->toHtml();
        $html.= '<script type="text/javascript">document.getElementById("dragonpay_checkout").submit();</script>';
        $html.= '</body></html>';

        return $html;
    }
}

?>