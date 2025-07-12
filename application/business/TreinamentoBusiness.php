<?php

class TreinamentoBusiness {

    /**
     * Id do contrato
     * @var int 
     */
    public $contratoId;

    /**
     * Id da empresa
     * @var int
     */
    public $empresaId;

    /**
     *
     * @var int
     */
    public $agendaId;

    
    const CODIGO_FIXO_SERVICO_TREINAMENTO = '0003';

    public function __construct($paramContratoId = 0, $paramEmpresaId = 0, $paramAgendaId = 0) {
        $this->contratoId = (int) $paramContratoId;
        $this->empresaId = (int) $paramEmpresaId;
        $this->agendaId = (int) $paramAgendaId;
    }

    /**
     * Verifica se o contrato possui uma proposta aprovada para os servicos de treinamento e palestras 
     * @author Silas Stoffel <silas.stoffel@hiest.com.br>
     * @return boolean TRUE quando há, FALSE quando não há, propostas com o aceito do cliente
     * @throws Exception
     */
    public function possuiPropostaComercialComAceiteDoCliente() {
        $retorno = false;
        if (!is_numeric($this->contratoId) or ! is_numeric($this->empresaId) or (int) $this->contratoId <= 0 or (int) $this->empresaId <= 0) {
            throw new Exception('Os valores para os atributos contrato e empresa devem ser do tipo inteiro maior que zero');
        }
        $codigoCategoriaTreinamento = self::CODIGO_FIXO_SERVICO_TREINAMENTO;
        try {
            $Cnx = Zend_Db_Table::getDefaultAdapter();
            $Comando = new Zend_Db_Select($Cnx);
            $Comando->from('os', array('os_id'))
                    ->join('contratante', 'contratante.fk_contrato_id = os.fk_contrato_id AND contratante.fk_empresa_id AND contratante.contratante_empresa_principal = 1 AND os.os_status = 0 AND os.os_aprovada = 1', array())
                    ->where('contratante.fk_empresa_id = ?', $this->empresaId)
                    ->where('contratante.fk_contrato_id = ?', $this->contratoId)
                    ->where("EXISTS(SELECT * FROM cobrancaos co, categoriadoproduto cp WHERE cp.categoriadoproduto_id = co.fk_categoriadoproduto_id AND co.cobrancaos_status = 0 AND cp.categoriadoproduto_codigo_fixo = '{$codigoCategoriaTreinamento}' )")
                    ->limit(1);
                    //echo $Comando->assemble();
            $ExecutarComando = $Comando->query();
            $ResultadoComando = $ExecutarComando->fetch();
            if (is_array($ResultadoComando) && count($ResultadoComando) > 0) {
                $retorno = true;
            }
        } catch (Exception $ex) {
            throw $ex;
        }
        return $retorno;
    }

}
