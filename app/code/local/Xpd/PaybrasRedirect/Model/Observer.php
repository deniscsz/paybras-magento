<?php
class Xpd_PaybrasRedirect_Model_Observer
{
    
    protected function _isRedirectCustomerTax($customerData) {
        $cpf = $this->removeCharInvalidos($customerData['taxvat']);
        if(!$cpf) {
            $cpf = $this->removeCharInvalidos($customerData['cpfcnpj']);
        }
        
        if(!$this->validaCPF($cpf) || $this->contemCharInvalidos($customerData['taxvat'],1,1)) {
            return false;
        }
        else {
            return true;
        }
    }
    
    protected function _isRedirectCustomerCpfCnpj($customerData) {
        $cpfcnpj = $this->removeCharInvalidos($customerData['cpfcnpj']);
        
        if(!$this->validaCNPJ($cpfcnpj)) {
            return false;
        }
        else {
            return true;
        }
    }

    public function reedit(Varien_Event_Observer $observer) {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        
        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getModel('customer/customer')->load($customer->getId())->getData();
            
            if($this->_isRedirectCustomerTax($customerData) || $this->_isRedirectCustomerCpfCnpj($customerData)) {
                foreach ($customer->getAddresses() as $address) {
                    $data = $address->toArray();
                    
                    $telefone = $data['telephone'];
                    $telefone = str_replace(')','',str_replace('(','',$telefone));
                    $telefone2 = $this->removeCharInvalidos($telefone); 
                    
                    $zip = $data['postcode'];
                    $zip2 = $this->removeCharInvalidos($zip);
                    
                    if(substr_count($data['street'],chr(10)) < 2) {
                        $msg = Mage::getStoreConfig('payment/paybrasmsgs/addressinvalid');
                        Mage::getSingleton('customer/session')->addError($msg);
                        session_write_close();
                        Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/address'));
                    } 
                    elseif(strlen($telefone2) < 10 && strlen($telefone2) > 11) {
                        $msg = Mage::getStoreConfig('payment/paybrasmsgs/telinvalid');
                        Mage::getSingleton('customer/session')->addError($msg);
                        session_write_close();
                        Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/address'));    
                    }
                    elseif(strlen($zip2) != 8) {
                        $msg = Mage::getStoreConfig('payment/paybrasmsgs/cepinvalid');
                        Mage::getSingleton('customer/session')->addError($msg);
                        session_write_close();
                        Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/address'));
                    }
                }
            }
            else {
                $msg = Mage::getStoreConfig('payment/paybrasmsgs/cpfinvalid');
                Mage::getSingleton('customer/session')->addError($msg);
                session_write_close();
                Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/account/edit/'));
            }
        }
        
        return $this;
    }
    
    public function removeCharInvalidos($str,$traco = NULL,$ponto = NULL) {
        $invalid = array(' '=>'', '-' => $traco ? '-' : '', '{'=>'', '}'=>'', '('=>'', ')'=>'', '_'=>'', '['=>'', ']'=>'', '+'=>'', '*'=>'', '#'=>'', '/'=>'', '|'=>'', "`" => '', "´" => '', "„" => '', "`" => '', "´" => '', "“" => '', "”" => '', "´" => '', "~" => '', "’" => '', "." => $ponto ? '.' : '', 'a' => '', 'a' => '' , 'b' => '' , 'c' => '' , 'd' => '' , 'e' => '' , 'f' => '' , 'g' => '' , 'h' => '' , 'i' => '' , 'j' => '' , 'l' => '' , 'k' => '' , 'm' => '' , 'n' => '' , 'o' => '' , 'p' => '' , 'q' => '' , 'r' => '' , 's' => '' , 't' => '' , 'u' => '' , 'v' => '' , 'x' => '' , 'z' => '' , 'y' => '' , 'w' => '' , 'A' => '' , 'B' => '' , 'C' => '' , 'D' => '' , 'E' => '' , 'F' => '' , 'G' => '' , 'H' => '' , 'I' => '' , 'J' => '' , 'L' => '' , 'K' => '' , 'M' => '' , 'N' => '' , 'O' => '' , 'P' => '' , 'Q' => '' , 'R' => '' , 'S' => '' , 'T' => '' , 'U' => '' , 'V' => '' , 'X' => '' , 'Z' => '' , 'Y' => '' , 'W' => '');
         
        $str = str_replace(array_keys($invalid), array_values($invalid), $str);
         
        return $str;
    }
    
    public function contemCharInvalidos($str,$traco = NULL,$ponto = NULL) {
        $invalid = array(' '=>'', '-' => $traco ? '-' : '', '{'=>'', '}'=>'', '('=>'', ')'=>'', '_'=>'', '['=>'', ']'=>'', '+'=>'', '*'=>'', '#'=>'', '/'=>'', '|'=>'', "`" => '', "´" => '', "„" => '', "`" => '', "´" => '', "“" => '', "”" => '', "´" => '', "~" => '', "’" => '', "." => $ponto ? '.' : '', 'a' => '', 'a' => '' , 'b' => '' , 'c' => '' , 'd' => '' , 'e' => '' , 'f' => '' , 'g' => '' , 'h' => '' , 'i' => '' , 'j' => '' , 'l' => '' , 'k' => '' , 'm' => '' , 'n' => '' , 'o' => '' , 'p' => '' , 'q' => '' , 'r' => '' , 's' => '' , 't' => '' , 'u' => '' , 'v' => '' , 'x' => '' , 'z' => '' , 'y' => '' , 'w' => '' , 'A' => '' , 'B' => '' , 'C' => '' , 'D' => '' , 'E' => '' , 'F' => '' , 'G' => '' , 'H' => '' , 'I' => '' , 'J' => '' , 'L' => '' , 'K' => '' , 'M' => '' , 'N' => '' , 'O' => '' , 'P' => '' , 'Q' => '' , 'R' => '' , 'S' => '' , 'T' => '' , 'U' => '' , 'V' => '' , 'X' => '' , 'Z' => '' , 'Y' => '' , 'W' => '');
         
        if($str == str_replace(array_keys($invalid), array_values($invalid), $str)) {
            return 0;
        }
        else {
            return 1;
        }
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
    
    function validaCNPJ($cnpj){
        $cnpj = str_pad(str_replace(array('.','-','/'),'',$cnpj),14,'0',STR_PAD_LEFT);
        if (strlen($cnpj) != 14) {
            return false;
        }
        else {
            for($t = 12; $t < 14; $t++) {
                for($d = 0, $p = $t - 7, $c = 0; $c < $t; $c++) {
                    $d += $cnpj{$c} * $p;
                    $p = ($p < 3) ? 9 : --$p;
                }
                
                $d = ((10 * $d) % 11) % 10;
                
                if($cnpj{$c} != $d) {
                    return false;
                }
            }
            return true;
        }
    }

}
