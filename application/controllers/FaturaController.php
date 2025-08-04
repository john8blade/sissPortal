<?php

class FaturaController extends Controller {

    public function init() {
        parent::init();
        $this->modulo = 'portal cliente';
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao();
    }

    public function indexAction() {

        $parametroPagina = $this->_getParam('page', 1);

        $parametrosPesquisa = json_decode($this->_getParam('filters', ''), true);

        $empresaId = $_SESSION['empresa']['empresa_id'];
        $contratoId = $_SESSION['contrato_id'];
        $filtro = "fatura.fatura_status = 0";
        $filtro .= " AND  fatura.fk_contrato_id = '{$contratoId}'";
        $filtro .= " AND  fatura.fk_empresa_id = '{$empresaId}'";

        if ((int) $parametrosPesquisa['filtro-status'] > 0)
            $filtro .= " AND fatura.fk_statusfatura_id = '{$parametrosPesquisa['filtro-status']}'";
        if ($parametrosPesquisa['filtro-data-pagamento'])
            $filtro .= " AND fatura.fatura_data_pagamento = '" . Util::dataBD($parametrosPesquisa['filtro-data-pagamento']) . "'  ";
        if ($parametrosPesquisa['filtro-data-vencimento'])
            $filtro .= " AND fatura.fatura_data_vencimento = '" . Util::dataBD($parametrosPesquisa['filtro-data-vencimento']) . "'  ";
        if ($parametrosPesquisa['filtro-data-periodo'])
            $filtro .= " AND (STR_TO_DATE('" . $parametrosPesquisa['filtro-data-periodo'] . "', '%d/%m/%Y') BETWEEN fatura.fatura_data_inicio_apuracao AND fatura.fatura_data_fim_apuracao) ";

        $ordenarPor = "fatura.fatura_data_vencimento DESC";
        $resultado = array();
        $statusFatura = array();
        try {
            $f = new Application_Model_Fatura();
            $resultadoConsulta = $f->buscaCompletaUsandoClausula($filtro, $ordenarPor);
            $inadimplecia = $f->inadimplecia($_SESSION['empresa']['empresa_cnpj']);

            if (count($inadimplecia)>0) {
                $resultado = Zend_Paginator::factory($inadimplecia);
            }else{
                $resultado = Zend_Paginator::factory($resultadoConsulta);
            }
            
            $resultado->setCurrentPageNumber($parametroPagina);
            $sf = new Application_Model_StatusFatura();
            $statusFatura = $sf->fetchAll()->toArray();

            
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        $inicioUrl = substr($_SERVER['HTTP_HOST'], 0, 6);
        $sistema = (strtolower($inicioUrl) == 'develop') ? 'developsiss' : 'siss';
        $urlAcessoFatura = "http://{$sistema}.hiestgroup.com.br";

        $this->view->inadimplecia = $inadimplecia;
        $this->view->statusFatura = $statusFatura;
        $this->view->parametrosPesquisa = $parametrosPesquisa;
        $this->view->itensPaginados = $resultado;
        $this->view->urlAcessoFatura = $urlAcessoFatura;
    }

    public function baixarPagamentoAction() {
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao('Financeiro', 'Faturamento', 'Baixa de Pagamentos');
        $id = (int) $this->_getParam('faturaid', 0);
        $baixaId = (int) $this->_getParam('baixaid', 0);
        $fatura = null;
        $colecaoBaixasEfetuadas = $colecaoStatusFatura = $colecaoFormaPagamento = $colecaoContas = $form = array();
        require_once(APPLICATION_PATH . '/business/FaturamentoBusiness.php');
        try {

            $F = new FaturamentoBusiness();
            $fatura = $F->obterColecaoProdutosFaturados($id);
            if (!$fatura) {
                throw new Exception('Fatura não localizada');
            }
            $ModeloBaixa = new Application_Model_BaixaFatura();
            $Rst = $ModeloBaixa->fetchRow(array('baixa_fatura_id = ?' => $baixaId));
            $form = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('baixa_fatura');
            $form['baixa_fatura_id'] = 0;
            $form['fk_fatura_id'] = $id;
            $form['baixa_fatura_valor_pago'] = null;
            $form['baixa_fatura_juros'] = '0.00';
            $form['baixa_fatura_multas'] = '0.00';
            $form['baixa_fatura_descontos'] = '0.00';
            $form['baixa_fatura_outros_acrescimos'] = '0.00';
            $form['baixa_fatura_outros_descontos'] = '0.00';
            $form['baixa_fatura_outros_descontos'] = '0.00';
            $form['pessoa_nome'] = '';
            if ($Rst) {
                $form = $Rst->toArray();
                $ModeloPessoa = new Application_Model_Pessoa();
                $ModeloUsuario = new Application_Model_Usuario();
                $RstUsu = $ModeloUsuario->fetchRow(array('usuario_id = ?' => $Rst->fk_usuario_id));
                $RstPes = $ModeloPessoa->fetchRow(array('pessoa_id = ?' => $RstUsu->fk_pessoa_id));
                $form['pessoa_nome'] = $RstPes->pessoa_nome;
            }

            $ModeloStatus = new Application_Model_StatusFatura();
            $Rst = $ModeloStatus->fetchAll(array('statusfatura_status = ?' => 0), array('statusfatura_nome ASC'));
            $colecaoStatusFatura = ($Rst->count() > 0) ? $Rst->toArray() : array();

            $ModeloConta = new Application_Model_ContaBancaria();
            $Rst = $ModeloConta->fetchAll(array('conta_bancaria_status = ?' => 0), array('conta_bancaria_inst_financeira ASC'));
            $colecaoContas = ($Rst->count() > 0) ? $Rst->toArray() : array();

            $ModeloPagamento = new Application_Model_FormaPagamento();
            $Rst = $ModeloPagamento->fetchAll(array('formapagamento_status = ?' => 0), array('formapagamento_nome ASC'));
            $colecaoFormaPagamento = ($Rst->count() > 0) ? $Rst->toArray() : array();

            $Cnx = Zend_Db_Table::getDefaultAdapter();
            $Cmd = $Cnx->select()
                    ->from(array('bf' => 'baixa_fatura'))
                    ->join(array('us' => 'usuario'), 'us.usuario_id = bf.fk_usuario_id', array())
                    ->join(array('pe' => 'pessoa'), 'pe.pessoa_id = us.fk_pessoa_id')
                    ->joinLeft(array('fo' => 'formapagamento'), 'fo.formapagamento_id = bf.fk_formapagamento_id')
                    ->where('bf.baixa_fatura_status = ?', 0)
                    ->where('bf.fk_fatura_id = ?', $id)
                    ->order(array('bf.baixa_fatura_dh_registro'));

            $rst = $Cmd->query()->fetchAll();
            $colecaoBaixasEfetuadas = (count($rst) > 0) ? $rst : array();
        } catch (Exception $ex) {
            throw $ex;
        }
        $this->view->form = $form;
        $this->view->fatura = $fatura;
        $this->view->colecaoStatusFatura = $colecaoStatusFatura;
        $this->view->colecaoContas = $colecaoContas;
        $this->view->colecaoFormasPagamentos = $colecaoFormaPagamento;
        $this->view->colecaoBaixas = $colecaoBaixasEfetuadas;
    }

}
