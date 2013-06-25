<?php
/**
 * Paybras
 *
 * @category   Payments
 * @package    Xpd_Paybras
 * @license    OSL v3.0
 */
class Xpd_Paybras_Block_Standard_Pagamento extends Mage_Core_Block_Abstract {
    
    /*protected function _construct() {
        parent::_construct();
        $this->setTemplate('xpd/paybras/standard/pagamento.phtml');
    }*/
    
    protected function _toHtml(){
        $standard = Mage::getModel('paybras/standard');
        
        $html = '';
        $html .= '';
        
    }
    
}