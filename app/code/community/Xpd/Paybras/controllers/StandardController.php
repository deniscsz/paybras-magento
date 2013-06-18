<?php
/**
 * Paybras
 *
 * @category   Payments
 * @package    Xpd_Paybras
 * @license    OSL v3.0
 */
class Xpd_Paybras_StandardController extends Mage_Core_Controller_Front_Action {

    /**
     * Header de Sessão Expirada
     *
     */
    protected function _expireAjax() {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
            exit;
        }
    }

    /**
     * Retorna singleton do Model do Módulo.
     *
     * @return Xpd_Paybras_Model_Standard
     */
    public function getStandard() {
        return Mage::getSingleton('paybras/standard');
    }
    
    /**
     * Processa pagamento - cria transação via WebService 
     * 
     */
    protected function redirectAction() {
        $paybras = $this->getStandard();
        $session = Mage::getSingleton('checkout/session');
        $order = $paybras->getOrder();        
        $session->unsUrlRedirect();
        
        if($paybras->getEnvironment() == '1') {
            $url = 'https://service.paybras.com/payment/api/criaTransacao';
        }
        else {
            $url = 'https://sandbox.paybras.com/payment/api/criaTransacao';
        }
        
        $orderId = $order->getId();
        if ($orderId) {
            if(!$order->getEmailSent()) {
            	$order->sendNewOrderEmail();
    			$order->setEmailSent(true);
    			$order->save();
                $paybras->log("Email do Pedido $orderId Enviado");
            }
        }

        $payment = $order->getPayment();
        Mage::register('current_order',$order);
        
        if($order->getCustomerId()) {
            $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
        }
        else {
            $customer = false;
        }

        $fields = $paybras->dataTransaction($customer,$order,$payment);
        //var_dump($fields);
        $curlAdapter = new Varien_Http_Adapter_Curl();
        $curlAdapter->setConfig(array('timeout'   => 20));
        $curlAdapter->write(Zend_Http_Client::POST, $url, '1.1', array(), $fields);
        $resposta = $curlAdapter->read();
        $retorno = substr($resposta,strpos($resposta, "\r\n\r\n"));
        $curlAdapter->close();
        
        //echo '<br/>';
//        echo '<br/>';
        var_dump($resposta);
//        echo '<br/>';
//        echo '<br/>';
        
        if(function_exists('json_decode')) {
            $json_php = json_decode($retorno);
            
            if($json_php->{'sucesso'} == '1') {
                $paybras->log('True para consulta');
                $flag = true;
            }
            else {
                if($json_php->{'sucesso'} == '0') {
                    $code_erro = $json_php->{'mensagem_erro'};
                    $error_msg = Mage::helper('paybras')->msgError($code_erro);
                    $paybras->log('False para consulta');
                    $flag = false;
                }
                else {
                    $paybras->log('Null para consulta '. $json_php->{'code'});
                    $flag = NULL;
                }
            }
        }
        else {
            $evolucard->log('[ Function Json_Decode does not exist! Upgrade PHP ]');
        }
        
        if($flag) {
            $transactionId = $json_php->{'transacao_id'};
            $status_codigo = $json_php->{'status_codigo'};
            
            $payment->setPaybrasTransactionId(utf8_encode($transactionId))->save();
            $paybras->processStatus($order,$status_codigo,$transactionId);
            
            $session->setFormaPag($fields['pedido_meio_pagamento']);
            $url_redirect = utf8_decode($json_php->{'url_pagamento'});
            
            if($url_redirect) {
                $session->setUrlRedirect($url_redirect);
                $payment->setPaybrasOrderId($url_redirect)->save();
            }
            
            $url = Mage::getUrl('checkout/onepage/success');
        }
        else {
            $url = Mage::getUrl('checkout/onepage/failure');
        }
        
        $session->setOrderId($orderId);
        $this->getResponse()->setRedirect($url);
        //$session->unsUrlRedirect();
    }
    
    /**
     * Nova tentativa de pagamento
     * 
     */
    public function pagamentoAction() {
        $session = Mage::getSingleton('checkout/session');
        $paybras = Mage::getSingleton('paybras/standard');
        
        if($this->getRequest()->getParam('order_id')) {
            $orderId = $this->getRequest()->getParam('order_id');
            $paybras->log('Tentativa de Repagamento, pedido: '.$orderId);
        }
        else {
            die();
        }
        
        if(strlen((string)$orderId)<9) {
            $order = Mage::getModel('sales/order')->load((int)$orderId);
        }
        else {
            $order = Mage::getModel('sales/order')
                  ->getCollection()
                  ->addAttributeToFilter('increment_id', $orderId)
                  ->getFirstItem();
        }
        
        if($order && $order->getPayment()->getMethod() == $paybras->getCode()) {
            var_dump($order->getState());
            switch ($order->getState()) {
                case 2:
                    Mage::getSingleton("core/session")->setPayOrderId($orderId);
                    $order_redirect = true;
                    break;
                case Mage_Sales_Model_Order::STATE_PENDING_PAYMENT:
                    Mage::getSingleton("core/session")->setPayOrderId($orderId);
                    $order_redirect = true;
                    break;
                default:
                    $order_redirect = false;
                    break;
            }
        }
        else {
            $order_redirect = false;
        }
        
        var_dump($order_redirect);
        
        //if($order_redirect === false) {
//            $this->_redirect('');
//        }
//        else {
            $this->loadLayout();
    		$this->getLayout()->getBlock('root')->setTemplate('page/1column.phtml');
    		//$this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('paybras/standard_pagamento'));
            //Zend_Debug::dump($this->getLayout()->getUpdate()->getHandles());
            $this->renderLayout();
//        }
    }
    
    /**
     * Captura Notificação do Pagamento
     * 
     */
    public function capturaAction() {
        if($this->getRequest()->isPost() && Mage::getStoreConfig('payment/paybras/notification')) {
            $paybras = $this->getStandard();
            $paybras->log($json);
            $json = $_POST['data'];
            
            if(!$json) {
                $json = $_POST;
                $transactionId = $json['transacao_id'];
                $pedidoId = $json['pedido_id'];
                $pedidoIdVerifica = $pedidoId;
                $valor = $json['valor_original'];
                $status_codigo = $json['status_codigo'];
                $status_nome = $json['status_nome'];
                $recebedor_api = $json['recebedor_api_token'];
            }
            else {
                $json = json_decode($json);
                $transactionId = $json->{'transacao_id'};
                $pedidoId = $json->{'pedido_id'};
                $pedidoIdVerifica = $pedidoId;
                $valor = $json->{'valor_original'};
                $status_codigo = $json->{'status_codigo'};
                $status_nome = $json->{'status_nome'};
                $recebedor_api = $json->{'recebedor_api_token'};
            }
            $paybras = $this->getStandard();
            
            //var_dump($transactionId);
//            var_dump($pedidoId);
//            var_dump($valor);
//            var_dump($status_codigo);
//            var_dump($status_nome);
//            var_dump($recebedor_api);
            
            $paybras->log($pedidoId);
            $paybras->log($status_codigo);
            
            if($transactionId && $status_codigo && $pedidoId) {
                if(strpos($pedidoId,'_') !== false) {
                    $pedido = explode("_",$pedidoId);
                    $orderId = $pedido[0];
                }
                else {
                    $orderId = $pedidoId;
                }
                
                $order = Mage::getModel('sales/order')
                  ->getCollection()
                  ->addAttributeToFilter('increment_id', $orderId)
                  ->getFirstItem();
                
                $status = (int)$status_codigo;
                                
                if($paybras->getEnvironment() == '1') {
                    $url = 'https://service.paybras.com/payment/getStatus';
                }
                else {
                    $url = 'https://sandbox.paybras.com/payment/getStatus';
                }
                
                $fields = array(
                    'recebedor_email' => $paybras->getEmailStore(),
                    'recebedor_api_token' => $paybras->getToken(),
                    'transacao_id' => $transactionId,
                    'pedido_id' => $pedidoId
                );
                
                //var_dump($fields);
                $curlAdapter = new Varien_Http_Adapter_Curl();
                $curlAdapter->setConfig(array('timeout'   => 20));
                $curlAdapter->write(Zend_Http_Client::POST, $url, '1.1', array(), $fields);
                $resposta = $curlAdapter->read();
                $retorno = substr($resposta,strpos($resposta, "\r\n\r\n"));
                $curlAdapter->close();
                
                $json = json_decode($retorno);
                if($json->{'sucesso'} == '1') {
                    if($json->{'pedido_id'} == $pedidoIdVerifica && $json->{'valor_total'} == $valor && $json->{'status_codigo'} == $status_codigo) {
                        $result = $paybras->processStatus($order,$status,$transactionId);
                        if($result >= 0) {
                            echo '{"retorno"."OK"}';
                        }
                    }
                }
                else {
                    $paybras->log('Erro resposta de Consulta');
                }
            }
            else {
                $paybras->log('Erro na Captura - Nao foi possivel pergar os dados');
                $paybras->log($json);
                echo 'Erro na Captura - Nao foi possivel pergar os dados';
            }
        }
    }
    
    /**
     * Exibe tela de sucesso após tentativa de repagamento
     * 
     */
    public function successAction() {
        $orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
                                
        if ($order) {
            Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
            $this->_redirect('checkout/onepage/success', array('_secure' => true));
        }     
    }
    
    /**
     * Controller para comparar de nomes via AJAX
     *
     */
    public function comparaAction() {
        $nameCustomer = $this->getRequest()->getParam('nome');
        $nameTitular = $this->getRequest()->getParam('titular');
        $paybras = $this->getStandard();
        
        if($nameCustomer && $nameTitular) {
            echo $paybras->comparaNome($nameCustomer,$nameTitular) ? '1' : '0';
        }
    }
}
