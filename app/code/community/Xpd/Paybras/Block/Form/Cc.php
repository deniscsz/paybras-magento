<?php
/**
 * Paybras
 *
 * @category   Payments
 * @package    Xpd_Paybras
 * @license    OSL v3.0
 */
class Xpd_Paybras_Block_Form_Cc extends Mage_Payment_Block_Form_Cc {

    /**
     * Especifica template.
     */
    protected function _construct() {
        parent::_construct();
        $this->setTemplate('xpd/paybras/form/cc.phtml');
    }

	public function getSourceModel() {
		return Mage::getSingleton('paybras/source_cartoes');
    }

    /**
     * Retorna os tipos de cartões de créditos 
     *
     * @return array
     */
    public function getCcAvailableTypes() {
        $arrayCartoes = $this->getSourceModel()->toOptionArray();

        $types = array();
        foreach($arrayCartoes as $cartao) {
            $types[$cartao['value']] = $cartao['label'];
        }

        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('cctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);

                foreach ($types as $code=>$name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }

        return $types;
    }


    /**
     * Retreive payment method form html
     *
     * @return string
     */
    public function getMethodFormBlock() {
        return $this->getLayout()->createBlock('payment/form_cc')
                        ->setMethod($this->getMethod());
    }

    public function getParcelas() {
        $enabled = Mage::getStoreConfig('payment/paybras/installments');
        $paybras = Mage::getSingleton('paybras/standard');
                
        if($enabled) {
            //$retorno = file_get_contents(Mage::getBaseUrl().'paybras/standard/parcelamento');
            $retorno = Mage::getSingleton('core/session')->getMyParcelamento();
            
            if(function_exists('json_decode')) {
                $json_php = json_decode($retorno);
                //var_dump($json_php->{'sucesso'});
                if($json_php->{'sucesso'} == 1) {
                    $return_parcelas = array();
                    foreach($json_php as $param => $parcelas) {
                        if($param != 'sucesso') {
                            $return_parcelas[$param] = $parcelas->{'valor_parcela'};
                        }
                    }
                    return $return_parcelas;
                }
                else {
                    $paybras->log('Mensagem de Erro do Parcelamento: '.$json_php->{'mensagem_erro'});
                    return array('1' => 0);
                }
            }
            else {
                $paybras->log('Sua versao do PHP e antiga. Por favor, atualize.');
                return array('1' => 0);
            }
        }
        else {
            return array('1' => $total = Mage::getSingleton('checkout/cart')->getQuote()->getGrandTotal());
        }
    }
    
    public function removeInvalidos($palavra) {
        return preg_replace("/[^a-zA-Z0-9_]/", "", strtr($palavra, "áàãâéêíóôõúüçñÁÀÃÂÉÊÍÓÔÕÚÜÇÑ ", "aaaaeeiooouucnAAAAEEIOOOUUCN_")); 
    }
    
    public function comparaNome($nomecartao, $nomepessoa) {
        $acertos = 1;
        $nomecartao = $this->removeInvalidos($nomecartao);
        $nomepessoa = $this->removeInvalidos($nomepessoa);
        // Com intuito de melhorar a comparação:
        // Retiram-se espaços duplos, triplos etc., espaços nas laterais, e convertem-se caracteres para minúsculo
        // Convertem-se ainda para arrays
        $nomecartao = explode(" ", strtolower(trim(preg_replace('/\s+/', ' ', $nomecartao))));
        $nomepessoa = explode(" ", strtolower(trim(preg_replace('/\s+/', ' ', $nomepessoa))));
    
        // Número de comparações que devem ser atendidas com tolerância de 1 falha.
        // Este número corresponde ao tamanho do menor array, ou seja, menor quantidade de strings dos nomes (cartao ou pessoa)
        $objetivo_comparacoes = (count($nomecartao) > count($nomepessoa)) ? count($nomepessoa) : count($nomecartao);
        //echo "1 " . $nomecartao[0] ." ". $nomepessoa[0] . "<br>";
        // o primeiro nome deve coincidir
        if ($nomecartao[0] != $nomepessoa[0]) {
            return false;
        }
    
        // depois do primeiro nome, a validacao é feita pela quantidade
        // de caracteres abreviado do nome (Ex.: s - Silva) e deve
        // ser procurado em todo o sobrenome, não necessariamente na ordem em que são escritos
        // Ex.: daniel s a - daniel silva almeida (true) - 3 acertos
        //      daniel a s - daniel silva almeida (true) - 3 acertos
        //      daniel s   - daniel silva almeida (true) - 2 acertos - tolerancia de 1
        //      daniel a   - daniel silva almeida (true) - 2 acertos - tolerancia de 1
        //      daniel d b - daniel silva almeida (false) - 1 acerto - tolerancia de 1 - ainda faltou 1 acerto.
        //      daniel d   - daniel almeida       (false) - 1 acerto - tolerancia de 1 - ainda faltou 1 acerto.
    
        for ($i = 1; $i < count($nomecartao); $i++) {
            $encontrou = false;
    
            for ($j = 1; $j < count($nomepessoa) && !$encontrou; $j++) {
                // compara quantidade de caracteres iguais
                // se no sobrenome havia uma letra (s) do sobrenome completo
                // "silva" pegamos apenas o primeiro caracter para comparacao
    
                if (strlen($nomecartao[$i]) == 1) {
    
                    if ($nomecartao[$i] == $nomepessoa[$j][0]) {
                        $encontrou = true;
                        $acertos++;
                    }
                } else if (strlen($nomecartao[$i]) > 1) {
                    if ($this->percentStringCompare($nomecartao[$i], $nomepessoa[$j]) > 50) {
                        $encontrou = true;
                        $acertos++;
                    }
                }
            }
        }
    
        if ($acertos == $objetivo_comparacoes || $acertos == $objetivo_comparacoes - 1) {
            return true;
        }
    }

}