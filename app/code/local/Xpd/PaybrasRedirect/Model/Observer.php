<?php
class Xpd_PaybrasRedirect_Model_Observer
{
    protected function _isRedirectCustomer($customerData) {
        $cpf = $this->removeCharInvalidos($customerData['taxvat']);
        if(!$this->validaCPF($cpf)) {
            return false;
        }
        return true;
    }

	public function reedit(Varien_Event_Observer $observer) {
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		
		if(Mage::getSingleton('customer/session')->isLoggedIn()) {
			$customerData = Mage::getModel('customer/customer')->load($customer->getId())->getData();
			
            if($this->_isRedirectCustomer($customerData)) {
    			foreach ($customer->getAddresses() as $address) {
    				$data = $address->toArray();
                    $telefone = $data['telephone'];
                    
                    $telefone = $this->removeCharInvalidos($telefone); 
                    if(substr($telefone,0,1) == '0') {
                        $telefone = substr($telefone,1);
                    }
                    
                    $zip = $data['postcode'];
                    $zip = $this->removeCharInvalidos($zip); 
                    
                    if(substr_count($data['street'],chr(10)) < 2 || strlen($telefone) < 10 || strlen($zip) < 8) {
                        $msg = "Seus dados de endereço estão desatualizados, por favor atualize seu endereço antes de comprar.";
                        Mage::getSingleton('customer/session')->addError($msg);
                        session_write_close();
                        Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/address'));
                    }
    			}
			}
            else {
                $msg = "Seu CPF está incorreto. Por favor atualize seus dados.";
                Mage::getSingleton('customer/session')->addError($msg);
                session_write_close();
                Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/account/edit/'));
            }
		}
		
		return $this;
	}
    
    public function removeCharInvalidos($str) {
        $invalid = array(' '=>'', '-'=>'', '{'=>'', '}'=>'', '('=>'', ')'=>'', '_'=>'', '['=>'', ']'=>'', '+'=>'', '*'=>'', '#'=>'', '/'=>'', '|'=>'', "`" => '', "´" => '', "„" => '', "`" => '', "´" => '', "“" => '', "”" => '', "´" => '', "~" => '', "’" => '', "." => '');
         
        $str = str_replace(array_keys($invalid), array_values($invalid), $str);
         
        return $str;
    }
    
    function validaCPF($cpf) {
        $cpf = str_pad(preg_replace('[^0-9]', '', $cpf), 11, '0', STR_PAD_LEFT);
    	
        if (strlen($cpf) != 11 || $cpf == '00000000000' || $cpf == '11111111111' || $cpf == '22222222222' || $cpf == '33333333333' || $cpf == '44444444444' || $cpf == '55555555555' || $cpf == '66666666666' || $cpf == '77777777777' || $cpf == '88888888888' || $cpf == '99999999999') {
    	   return false;
        }
    	else {
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf{$c} * (($t + 1) - $c);
                }
     
                $d = ((10 * $d) % 11) % 10;
     
                if ($cpf{$c} != $d) {
                    return false;
                }
            }
     
            return true;
        }
    }
    
    
		
}
