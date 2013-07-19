<?php
class Xpd_PaybrasRedirect_Model_Observer
{

	public function reedit(Varien_Event_Observer $observer)
	{		
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		
		if(Mage::getSingleton('customer/session')->isLoggedIn()) {
			$customerData = Mage::getModel('customer/customer')->load($customer->getId())->getData();
			
			foreach ($customer->getAddresses() as $address) {
				$data = $address->toArray();
                
                if(substr_count($data['street'],chr(10)) <= 3) {
                    $msg = "Seus dados estão desatualizados, por favor atualize seu endereço antes de comprar.";
                    Mage::getSingleton('core/session')->setMsgEditError($msg);
                    //Mage::getSingleton('customer/session')->addError($msg);
                    
                    Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/address'));
                    //$this->_redirect('customer/address');
                }
			}
			
		}
		
		return $this;
	}
		
}
