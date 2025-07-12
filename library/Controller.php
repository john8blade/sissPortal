<?php

class Controller extends Zend_Controller_Action {

    public $modulo = 'portal do cliente';
    public $controlador = null;
    public $acao = null;
    public static $paginaComBarraLateralImpressaoMenu = true;
    public static $paginaComBarraLateralImpressaoImagem = false;
    public $itensMenuLateral = array();

    public function init() {
        $cont = $this->getRequest()->getControllerName();
        $acao = $this->getRequest()->getActionName();

        $params = $this->getAllParams();

        if ((!Util::usuarioAceitouTermos() && $cont != 'index')) 
        {
            header("location: http://".$_SERVER['HTTP_HOST']."/");
        }

        unset($params['controller']);
        unset($params['action']);
        unset($params['module']);
        $this->params = $params;

        $this->controlador = $cont;
        $this->acao = $acao;
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao();
        $this->_criarItensMenuLateral();
        $this->view->itensMenuLateral = $this->itensMenuLateral;
        $this->view->paginaComBarraLateralImpressaoMenu = self::$paginaComBarraLateralImpressaoMenu;
        $this->view->paginaComBarraLateralImpressaoImagem = self::$paginaComBarraLateralImpressaoImagem;
        $this->view->paginaHTMLRodapeComUnidades = Application_Model_DbUtil::obterUnidadesFormatadasEmHtmlParaRodapeDocumentos();
    }

    public function feedback($feedback = null) {
        $acoesDeResposta = array("salvar");
        if (in_array($this->getRequest()->getActionName(), $acoesDeResposta)) {
            $this->_helper->layout->disableLayout();
            $this->view->feedback = is_null($feedback) ? array("erro" => 1, "msg" => "") : $feedback;
            $this->renderScript("feedback.phtml");
        }
    }

    public function json($array) {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        echo json_encode($array);
    }

    public function eco($string) {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        echo (string) $string;
    }

    public function sweetalert($feedback = null)
    {
        $acoesDeResposta = array("salvar", "excluir");
        if (in_array($this->getRequest()->getActionName(), $acoesDeResposta)) {
            $this->view->feedback = is_null($feedback) ? array("erro" => 1, "msg" => "") : $feedback;
            $this->_helper->layout->disableLayout();
            $this->renderScript("sweetalert.phtml");
            exit;
        }
    }

    /**
     * Envia os atributos do mapeamento de acesso para camada de visualização.
     * Os atributos são enviado com o indentificador "mapeamentoAcesso", este é o nome
     * que permite acessá-lo e resgatar informações nos arquivos de visualização.
     * Os atributos de "mapeamentoAcesso" é vetor associativo com os seguintes índices:
     * <ul>
     * <li>$this->mapeamentoAcesso['controlador'] Nome do controler acessado</li>
     * <li>$this->mapeamentoAcesso['acao'] Nome da ação acessado</li>
     * <li>$this->mapeamentoAcesso['modulo'] Nome do módulo atribuído</li>
     * </ul>
     * @author Silas Stoffel <silas.ugv@hiest.com.br>
     * @category Controller
     * @access protected
     * @return void
     * @param String $modulo
     * @param String $controlador
     * @param String $acao
     */
    protected function _enviarMapeamentoDoAcessoParaCamadaVisualizacao($modulo = null, $controlador = null, $acao = null, $params = []) {
        if ($acao == null) {
            $acao = $this->acao;
        }
        if ($controlador == null) {
            $controlador = $this->controlador;
        }
        if ($modulo == null) {
            $modulo = $this->modulo;
        }
        if ($params == null) {
            $params = $this->params;
        }
        $this->view->mapeamentoAcesso = array(
            'controlador' => $controlador,
            'acao' => $acao,
            'modulo' => $modulo,
            'params' => $params
        );
    }

    /**
     * Desabilita o carregamento de template do <b>Zend Framework</b>
     * @author Silas Stoffel <silas.ugv@hiest.com.br>
     * @category Controller
     * @access protected
     * @return void
     */
    protected function _desabilitarCarregamentoDoTemplate() {
        $this->_helper->layout->disableLayout();
    }

    /**
     * Desabilita a renderização do script de renderização <b>("na camada view)"</b> de um método definido em controller.
     * @author Silas Stoffel <silas.ugv@hiest.com.br>
     * @category Controller
     * @access protected
     * @return void
     *
     */
    protected function _desabilitarCarregamentoVisualizacao() {
        $this->_helper->viewRenderer->setNoRender();
    }

    /**
     * Desabilita a renderização do script de renderização <b>("na camada view)"</b> e o <b>template</b> definido em uma "Action" do controller.
     * @author Silas Stoffel <silas.ugv@hiest.com.br>
     * @category Controller
     * @access protected
     * @return void
     *
     */
    protected function _desabilitarTodoCarregamentoDeVisualizacao() {
        $this->_desabilitarCarregamentoDoTemplate();
        $this->_desabilitarCarregamentoVisualizacao();
    }

    protected function _enviarCapturaExcecaoParaView($e) {
        $this->view->erro = $e;
    }


    private function _criarItensMenuLateral() {
        $this->itensMenuLateral = array(
            '/contrato/' => '<i class="icon-briefcase"></i>&nbsp;Meu Contrato',
            '/agenda/' => '<i class="icon-calendar"></i>&nbsp;Minha Agenda',
            '/funcionario/' => '<i class="icon-user"></i>&nbsp;Meus Funcionários',
            '/fatura/' => '<i class="icon-file"></i>&nbsp;Minhas Faturas',
            '/documento/' => '<i class="icon-folder-close"></i>&nbsp;Meus Documentos',
            '/relatorio/' => '<i class=" icon-th-large"></i>&nbsp;Meus Relatórios',
        );
    }

}
