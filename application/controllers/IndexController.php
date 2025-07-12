<?php

class IndexController extends Controller {

    public function init() {
        parent::init();
        $this->modulo = 'portal cliente';
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao();
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
            $atributos['contrato']['vigencia'] = (!is_null($vigenciaContrato) && count($vigenciaContrato) > 0) ? $vigenciaContrato : Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('vigencia');
            $atributos['contrato']['vigencia']['vigencia_data_inicio'] = Util::dataBR($atributos['contrato']['vigencia']['vigencia_data_inicio']);
            $atributos['contrato']['vigencia']['vigencia_data_fim'] = Util::dataBR($atributos['contrato']['vigencia']['vigencia_data_fim']);

            // Resgata os produtos contratados do contrato
            $contratoId = isset($_SESSION['contrato_id']) ? $_SESSION['contrato_id'] : 0;
            $filtros = " produto_contratado.produto_contratado_status = 0";
            $filtros .= " AND os.fk_contrato_id = {$contratoId}";
            $proCon = new Application_Model_ProdutoContratado();
            $resultadoProCon = $proCon->buscarCompletoUsandoClausula($filtros, "produto_nome ASC");
            $atributos["produtoContratado"] = $resultadoProCon;

            $contrato = new Application_Model_Empresa();
            $contato = $contrato->listarContatoHiest($contratoId);

            $ModelUserPortal = new Application_Model_UsuarioPortal();
            $termos = $ModelUserPortal->obterTermos($contratoId);

            $ModelAloc = new Application_Model_Alocacao();
            $qtdAloc = $ModelAloc->dadosAlocacao($contratoId);

            $ModelFatura = new Application_Model_Fatura();
            $faturastatus = $ModelFatura->inadimplecia($_SESSION['empresa']['empresa_cnpj']);
            $_SESSION['inadimplecia'] = $faturastatus;

            $f = new Application_Model_Funcionario();
            $FuncAtivos = 0;
            $where = " WHERE list.func_status IN('ATIVO')";
            $ResAtv = $f->RelacFunc($where);
            $FuncAtivos = count($ResAtv);

            $PerAvencer = 0;
            $where = " WHERE list.func_status IN('ATIVO') AND list.periodico_status LIKE '%A vencer%'";
            $ResAvenc = $f->RelacFunc($where);
            $PerAvencer = count($ResAvenc);

            $PerVencido = 0;
            $where = " WHERE list.func_status IN('ATIVO') AND list.periodico_status LIKE '%Vencido%'";
            $ResVenc = $f->RelacFunc($where);
            $PerVencido = count($ResVenc);
                        
        } catch (Exception $ex) {
            $this->_enviarCapturaExcecaoParaView($ex->getMessage());
        }
        
        $this->view->faturastatus = $faturastatus;
        $this->view->qtd = count($qtdAloc);
        $this->view->termos = $termos;
        $this->view->atributos = $atributos;
        $this->view->contato = @$contato;
        $this->view->perAvencer = $PerAvencer;
        $this->view->perVencido = $PerVencido;
        $this->view->funcAtivos = $FuncAtivos;
    }

}
