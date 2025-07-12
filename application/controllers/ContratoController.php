<?php

class ContratoController extends Controller {

    public function init() {
        parent::init();
        $this->modulo = 'portal cliente';
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao();
    }

    public function editarAction() {
        $id = $_SESSION['empresa']['empresa_id'];
        $model_empresa = new Application_Model_Empresa();
        $model_endereco = new Application_Model_Endereco();
        $model_telefone = new Application_Model_Telefone();
        $model_empresa_pessoa = new Application_Model_EmpresaPessoa();
        $empresa_pessoa = $model_empresa_pessoa->obter($id);
        $empresa = $model_empresa->obter($id);
        $atributos = array();
        $empresa_financeiro = array();
        $empresa_contrato = array();
        $empresa_responsavel = array();

        $colecao = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('empresa_pessoa');
        $this->view->colecaoResponsaveisEmpresa = array($colecao);
        $this->view->colecaoResponsaveisContrato = array($colecao);
        $this->view->colecaoResponsaveisFinanceiro = array($colecao);

        $colecaoCendenteFaturamento = array();
        try {
            $Modelo = new Application_Model_FaturaCedente();
            $rst = $Modelo->fetchAll(array('fatura_cedente_status = ?' => 0), 'fatura_cedente_nome ASC');
            $colecaoCendenteFaturamento = ($rst->count() > 0) ? $rst->toArray() : array();
        } catch (Exception $exc) {
            throw $exc;
        }
        $this->view->colecaoCendenteFaturamento = $colecaoCendenteFaturamento;


        if ($empresa) {
            /*
             * $colecaoResponsaveisEmpresa = array();
             * $colecaoResponsaveisContrato = array();
             * $colecaoResponsaveisFinanceiro = array();
             *
             */
            foreach ($empresa_pessoa as $key => $value) {

                if ($value['empresa_pessoa_tipo'] === 'FINANCEIRO') {
                    $this->view->financeiro = $value;
                    //$colecaoResponsaveisFinanceiro[] = $value;
                }
                if ($value['empresa_pessoa_tipo'] === 'CONTRATO') {
                    $this->view->contrato = $value;
                    //$colecaoResponsaveisContrato[] = $value;
                }
                if ($value['empresa_pessoa_tipo'] === 'RESPONSAVEL') {
                    $this->view->responsavel = $value;
                    //$colecaoResponsaveisEmpresa[] = $value;
                }
            }


            if ($id > 0) {
                try {
                    $Resultado = $model_empresa_pessoa->fetchAll(array('fk_empresa_id = ?' => $id, 'empresa_pessoa_tipo = ?' => 'FINANCEIRO'));
                    if ($Resultado->count() > 0) {
                        $this->view->colecaoResponsaveisFinanceiro = $Resultado->toArray();
                    }

                    $Resultado = $model_empresa_pessoa->fetchAll(array('fk_empresa_id = ?' => $id, 'empresa_pessoa_tipo = ?' => 'RESPONSAVEL'));
                    if ($Resultado->count() > 0) {
                        $this->view->colecaoResponsaveisEmpresa = $Resultado->toArray();
                    }

                    $Resultado = $model_empresa_pessoa->fetchAll(array('fk_empresa_id = ?' => $id, 'empresa_pessoa_tipo = ?' => 'CONTRATO'));
                    if ($Resultado->count() > 0) {
                        $this->view->colecaoResponsaveisContrato = $Resultado->toArray();
                    }
                } catch (Exception $exc) {
                    $this->view->erro = $exc;
                }
            }

            $this->view->colecaoContextoCadastroEmpresa = array();
            try {
                $this->_enviarParamentrosComunsParaFormulario();
            } catch (Exception $exc) {
                $this->view->erro = $exc;
            }

            $orgaoModel = new Application_Model_OrgaoAvaliador();
            $this->view->orgaoAvaliador = $orgaoModel->buscarUsandoFiltro();
            $endereco = $model_endereco->fetchRow(array("endereco_id = ?" => $empresa["fk_endereco_id"]))->toArray();
            $telefones = $model_telefone->fetchAll(array("fk_empresa_id = ?" => $empresa["empresa_id"]))->toArray();
            $atributos = array_merge($empresa, $endereco);
            $atributos["telefones"] = $telefones;
        }
        $this->view->atributos = $atributos;
        $this->renderScript('contrato/form.phtml');
    }

    public function indexAction() {
        $atributos = $_SESSION;
        $atributos["produtoContratado"] = array();
        try {
            // Resgata o endereço da empresa
            $e = new Application_Model_Endereco();
            $enderecoId = $atributos['empresa']['fk_endereco_id'];
            $enderecoEmpresa = $e->fetchRow("endereco_id = $enderecoId")->toArray();
            $atributos['empresa']['endereco'] = $enderecoEmpresa;

            // Resgata a vigência do contrato
            $v = new Application_Model_Vigencia();
            $vigenciaContrato = $v->fetchRow("fk_contrato_id = {$atributos['contrato_id']} AND vigencia_status = 0");
            $atributos['contrato']['vigencia'] = (is_array($vigenciaContrato) && count($vigenciaContrato) > 0) ? $vigenciaContrato : Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('vigencia');
            $atributos['contrato']['vigencia']['vigencia_data_inicio'] = Util::dataBR($atributos['contrato']['vigencia']['vigencia_data_inicio']);
            $atributos['contrato']['vigencia']['vigencia_data_fim'] = Util::dataBR($atributos['contrato']['vigencia']['vigencia_data_fim']);
            /*
              // Resgata os produtos contratados do contrato
              $contratoId = isset($_SESSION['contrato_id']) ? $_SESSION['contrato_id'] : 0;
              $filtros = " produto_contratado.produto_contratado_status = 0";
              $filtros .= " AND os.fk_contrato_id = {$contratoId}";
              $proCon = new Application_Model_ProdutoContratado();
              $resultadoProCon = $proCon->buscarCompletoUsandoClausula($filtros, "produto_nome ASC");
              $atributos["produtoContratado"] = $resultadoProCon;
             */
            $contratoId = isset($_SESSION['contrato_id']) ? $_SESSION['contrato_id'] : 0;
            $contrato = new Application_Model_Empresa();
            $contato = $contrato->listarContatoHiest($contratoId);

            $colecaoFiltarContratos = array($contratoId);
            $colecaoFiltarOrdens = array();
            $colecaoResultados = Application_Model_CategoriaProduto::obterColecaoDeCategoriaDeProdutosContratados($colecaoFiltarContratos, $colecaoFiltarOrdens);

            $atributos["produtoContratado"] = (is_array($colecaoResultados) && count($colecaoResultados) > 0 ) ? $colecaoResultados : array();
        } catch (Exception $ex) {
            $this->_enviarCapturaExcecaoParaView($ex->getMessage());
        }
        $this->view->atributos = $atributos;
        $this->view->contato = $contato;
    }

}
