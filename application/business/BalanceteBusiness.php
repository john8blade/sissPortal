<?php

class ContaBalancete {

    public $valorTotal;
    public $valorEmAtraso;

    public function __construct() {
        $this->valorTotal = 0.00;
    }

}

class ContaAPagar extends ContaBalancete {

    public $valorAPagar;
    public $valorPago;

    public function __construct() {
        parent::__construct();
    }

}

class ContaAReceber extends ContaBalancete {

    public $valorRecebido = 0;
    public $valorRecebidoBasePrevisto = 0;
    public $valorInadimplenciaBasePrevisto = 0;
    public $valorPerdaBasePrevisto = 0;
    public $valorAReceber = 0;
    public $valorPrevisto = 0;

    public function __construct() {
        parent::__construct();
    }

}

class BalanceteBusiness {

    public $aPagar;
    public $aReceber;

    /**
     * Relatório consolidade de contas a pagar e receber.
     * @param date $dataInicio Data de início do período avaliado
     * @param date $dataTermino Data de termino do período avaliado 
     * @param array $unidades Ids das unidades que devem aparecer na contabilização
     * @return \BalanceteBusiness
     * @throws Exception
     * @author Silas Stoffel <silas.stoffel@hiest.com.br>
     */
    public static function relatorioDeContas($dataInicio, $dataTermino, array $unidades) {
        $Balancete = null;
        try {

            $Balancete = new BalanceteBusiness();

            // Contas a receber
            $rst = Application_Model_Relatorio::demonstrativoFaturamentoGeralConsolidado($unidades, $dataInicio, $dataTermino);

            $CReceber = new ContaAReceber();
            $CReceber->valorPrevisto = (isset($rst['valor_total_previsto'])) ? (float) $rst['valor_total_previsto'] : 0;
            $CReceber->valorAReceber = (isset($rst['valor_a_receber'])) ? (float) $rst['valor_a_receber'] : 0;
            $CReceber->valorRecebidoBasePrevisto = (isset($rst['valor_recebido_com_base_previsto'])) ? (float) $rst['valor_recebido_com_base_previsto'] : 0;
            $CReceber->valorInadimplenciaBasePrevisto = (isset($rst['valor_inadimplencia_com_base_previsto'])) ? (float) $rst['valor_inadimplencia_com_base_previsto'] : 0;
            $CReceber->valorEmAtraso = $CReceber->valorInadimplenciaBasePrevisto;
            $CReceber->valorTotal = (isset($rst['valor_total_recebido_ate_data_atual'])) ? (float) $rst['valor_total_recebido_ate_data_atual'] : 0;
            $CReceber->valorPerdaBasePrevisto = (isset($rst['valor_perda_com_base_previsto'])) ? (float) $rst['valor_perda_com_base_previsto'] : 0;
            $Balancete->aReceber = $CReceber;

            // Contas a pagar
            $CPagar = new ContaAPagar();
            $CPagar->valorAPagar = Application_Model_OrdemPagamento::contabilizarValorPrevistoPagamento($dataInicio, $dataTermino, $unidades);

            $Balancete->aPagar = $CPagar;
        } catch (Exception $ex) {
            throw $ex;
        }
        return $Balancete;
    }
    /**
     * Calcula o valor de baixa agrupado por conta bancária.
     * @param date $dataInicio Data de início
     * @param date $dataTermino Date de Termino
     * @param array $unidades IDs da unidades
     * @return array Associativo contendo od seguinte index: 'conta_bancaria_id', 'conta_bancaria_agencia', 'conta_bancaria_agencia_dig', 'conta_bancaria_numero', 'conta_bancaria_numero_dig', 'conta_bancaria_inst_financeira', 'conta_bancaria_titular', 'conta_bancaria_tipo_pessoa', 'conta_bancaria_cpf_cnpj', 'conta_bancaria_status', 'somatorio'.<br/>
     * Para resgatar:<br/>
     * $resultado = relatorioDeBaixaPorContaBancaria(2000-01-01, 2000-01-15, array(1))<br/>
     * echo $resultado[0]['conta_bancaria_inst_financeira']
     * @throws Exception
     */
    public static function relatorioDeBaixaPorContaBancaria($dataInicio, $dataTermino, array $unidades) {
        $colunasCB = array('conta_bancaria_id', 'conta_bancaria_agencia', 'conta_bancaria_agencia_dig', 'conta_bancaria_numero', 'conta_bancaria_numero_dig', 'conta_bancaria_inst_financeira', 'conta_bancaria_titular', 'conta_bancaria_tipo_pessoa', 'conta_bancaria_cpf_cnpj', 'conta_bancaria_status');
        $resultados = array();
        try {
            $Cnx = Zend_Db_Table::getDefaultAdapter();
            $Cmd = $Cnx->select()
                    ->from(array('bf' => 'baixa_fatura'), array('somatorio' => new Zend_Db_Expr('SUM(baixa_fatura_valor_pago)')))
                    ->join(array('f' => 'fatura'), 'f.fatura_id = bf.fk_fatura_id', array())
                    ->join(array('c' => 'contrato'), 'c.contrato_id = f.fk_contrato_id', array())
                    ->join(array('cb' => 'conta_bancaria'), 'cb.conta_bancaria_id = bf.fk_conta_bancaria_id', $colunasCB)
                    ->where('f.fatura_status = ?', 0)
                    ->where('bf.baixa_fatura_status = ?', 0)
                    ->where('f.fk_statusfatura_id <> ?', 7)
                    ->where('bf.baixa_fatura_dt_pagamento >= ?', $dataInicio)
                    ->where('bf.baixa_fatura_dt_pagamento <= ?', $dataTermino)
                    ->where(new Zend_Db_Expr("c.fk_unidade_id IN(" . implode(',', $unidades) . ")"))
                    ->group($colunasCB);
            $resultados = $Cmd->query()->fetchAll();
        } catch (Exception $ex) {
            throw $ex;
        }        
        return $resultados;
    }

}
