<?php

require_once("../application/business/TreinamentoBusiness.php");

class TreinamentoController extends Controller {

    public function init() {
        parent::init();
    }
 
    public function indexAction() {
        $treinamentoModel = new Application_Model_Treinamento();
        $usuarioModel = new Application_Model_Usuario();
        $treinamentos = $treinamentoModel->obterTreinamentos();
        $instrutores = $usuarioModel->listarInstrutoresDeTreinamento();
        $modalidade = array(array(0, 'A Distância'), array(1, 'Presencial'), array(2, 'Semipresencial'));

        $pagina = $this->_getParam('page', 1);
        $modal = trim($this->_getParam('paramModalidade'));
        $data = trim($this->_getParam('paramData'));
        $hinicio = trim($this->_getParam('paramHInicio'));
        $htermino = trim($this->_getParam('paramHTermino'));
        $cargah = trim($this->_getParam('paramCargah'));
        $treinamento = trim($this->_getParam('paramTreinamento'));
        $instrutor = trim($this->_getParam('paramInstrutor'));

        $form = array(
            'paramModalidade' => $modal,
            'paramData' => $data,
            'paramHInicio' => $hinicio,
            'paramHTermino' => $htermino,
            'paramCargah' => $cargah,
            'paramTreinamento' => $treinamento,
            'paramInstrutor' => $instrutor
        );

        $dt = null;
        if (strlen($data) >= 10) {
                $dt = Util::dataBD($data);
        }

        $resultadoComando = array();
        $resultadoPaginado = array();
        
        // Definindo os parametros de busca.
        /*
        $customizarComando = " treinamento_agenda.treinamento_agenda_status = 0 ";
        $customizarComando .= "AND ( ";
        $customizarComando .= "treinamento_agenda_data_inicio LIKE '%{$dt}%' ";
        $customizarComando .= " AND produto_nome LIKE '%{$treinamento}%'";
        $customizarComando .= " AND pessoa_nome LIKE '%{$instrutor}%'";
        $customizarComando .= " AND treinamento_agenda_hora_inicio LIKE '%{$hinicio}%'";
        $customizarComando .= " AND treinamento_agenda_hora_fim LIKE '%{$htermino}%'";
        $customizarComando .= " AND treinamento_agenda_carga_horaria LIKE '%{$cargah}%'";
        $customizarComando .= ')';
        */       

        #$agendas = $treinamentoModel->obterAgendasIndex($customizarComando);

        try {
            #$consulta = $treinamentoModel->obterAgendasIndex($customizarComando);
            $consulta = $treinamentoModel->obterAgendasIndex($dt, $treinamento, $instrutor, $hinicio, $htermino, $cargah, $modal);
            $resultadoPaginado = Zend_Paginator::factory($consulta);
            $resultadoPaginado->setCurrentPageNumber($pagina);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        
        #$this->view->treinamentos = $treinamentos;
        #$this->view->lista = $agendas;
        $this->view->itensPaginados = $resultadoPaginado;
        $this->view->like = '';
        $this->view->form = $form;
        
    }

    public function alterarAction() {
        $treinamentoModel = new Application_Model_Treinamento();
        $usuarioModel = new Application_Model_Usuario();
        $treinamentos = $treinamentoModel->obterTreinamentos();
        $instrutores = $usuarioModel->listarInstrutoresDeTreinamento();
        $modalidade = array(array(0, 'A Distância'), array(1, 'Presencial'), array(2, 'Semipresencial'));
        $agendas = $treinamentoModel->obterAgendas();
        $locais = $treinamentoModel->obterLocais();
        $agenda = $treinamentoModel->obterAgenda($this->_getParam('agenda', 0));
        $pesquisa = $treinamentoModel->obterPesquisaDaAgenda($this->_getParam('agenda', 0));
        $this->view->modalidade = $modalidade;
        $this->view->pesquisa = $pesquisa;
        $this->view->agenda = $agenda;
        $this->view->treinamentos = $treinamentos;
        $this->view->instrutores = $instrutores;
        $this->view->locais = $locais;
        $this->view->lista = $agendas;
        $this->renderScript('treinamento/index.phtml');
    }

    public function agendamentoAction() {

        $treinamentoModel = new Application_Model_Treinamento();                

        $alunos = $treinamentoModel->obterAlunosDaAgenda($this->_getParam('agenda', 0), $_SESSION['empresa']['empresa_id']);
        $agenda = $treinamentoModel->obterAgenda($this->_getParam('agenda', 0));

        $funcionario = new Application_Model_Funcionario();
        $filtro = "fk_contrato_id = {$_SESSION['contrato_id']} AND funcionario.fk_empresa_id = {$_SESSION['empresa']['empresa_id']} ";
        $resultadoAlocacao = $funcionario->obterPeloFiltro($filtro);
        $funcionarios = $resultadoAlocacao;

        $this->view->funcionarios = $funcionarios;
        $this->view->alunos = $alunos;
        $this->view->agenda = $agenda;
    }

    public function imprimirCertificadoAction() {
        $agenda = (int) $this->_getParam('agenda');
        $agendamento = (int) $this->_getParam('agendamento');
        $treinamentoModel = new Application_Model_Treinamento();
        $aprovados = $treinamentoModel->obterAprovados($agendamento, $agenda);
        $this->view->aprovados = $aprovados;
        $this->renderScript('documento-operacional/treinamento/imprimir-certificado.phtml');
        $this->_helper->layout->disableLayout();
    }

    public function salvarAgendaAction() {
        $feedback = array('erro' => 2, 'msg' => 'Desculpe, parece que nada foi alterado. Tente novamente. <a href=""><i class="icon-refresh"></i></a>');

        # INICIO

        if ($this->getRequest()->isPost()) {

            $dados = array();
            $erros = array();

            $id = (int) $this->_getParam('agenda');
            $dados['treinamento_modalidade'] = (int) $this->_getParam('modalidade');
            $dados['treinamento_modalidade_outro'] = $this->_getParam('treinamento_modalidade_outro');
            $dados['treinamento_agenda_data_criacao'] = date('Y-m-d H:i:s');
            $dados['treinamento_agenda_data_inicio'] = Util::validaData($this->_getParam('data1')) ? Util::dataBD($this->_getParam('data1')) : null;
            $dados['treinamento_agenda_data_fim'] = Util::validaData($this->_getParam('data2')) ? Util::dataBD($this->_getParam('data2')) : null;
            $dados['treinamento_agenda_vagas'] = (int) $this->_getParam('vagas');
            $dados['treinamento_agenda_hora_inicio'] = $this->_getParam('hora-inicio');
            $dados['treinamento_agenda_hora_fim'] = $this->_getParam('hora-fim');
            $dados['treinamento_agenda_carga_horaria'] = $this->_getParam('carga-horaria');
            $dados['treinamento_agenda_carga_horaria_min'] = $this->_getParam('carga-horariam');
            $dados['treinamento_agenda_observacoes'] = $this->_getParam('observacoes');
            $dados['treinamento_agenda_indice_aprovacao'] = (float) $this->_getParam('indice_aprovacao');
            $dados['fk_produto_id'] = (int) $this->_getParam('treinamento');
            $dados['fk_usuario_id_instrutor'] = (int) $this->_getParam('instrutor');
            $dados['fk_usuario_id_instrutor_2'] = (int) $this->_getParam('instrutor2');
            $dados['fk_usuario_id_instrutor_responsavel_tecnico'] = (int) $this->_getParam('resptecnico');
            $dados['fk_treinamento_local_id'] = (int) $this->_getParam('local');
            $dados['fk_treinamento_sala_id'] = (int) $this->_getParam('sala');
            $dados['fk_treinamento_local_sala_id'] = (int) $this->_getParam('local');           
            $dados['fk_unidade_id'] = (int) Controller::$unidadeIdEmContexto;

            //util::dump($dados);exit();

            if ($dados['fk_usuario_id_instrutor'] == 0) {
                $erros[] = 'Informe um instrutor.';
            }

            if ($dados['fk_produto_id'] == 0) {
                $erros[] = 'Informe o treinamento.';
            }

            if ($dados['fk_treinamento_local_id'] == 1 && $dados['fk_treinamento_sala_id'] == 0) {
                $erros[] = 'Informe o local e sala.';
            }

            if ($dados['treinamento_agenda_vagas'] == 0) {
                $erros[] = 'O número mínimo de vagas é 1 (uma).';
            }

            if (Util::validaData($dados['treinamento_agenda_data_inicio'])) {
                $erros[] = 'A data do treinamento é inválida.';
            }

            if (empty($erros)) {
                try {
                    $treinamentoModel = new Application_Model_Treinamento();
                    if ($id == 0) {
                        $treinamentoModel->insert($dados);
                    } else {
                        $treinamentoModel->update($dados, array('treinamento_agenda_id = ?' => $id));
                    }
                    $feedback['erro'] = 0;
                    $feedback['msg'] = 'Agenda salva com sucesso. <meta http-equiv="refresh" content = "0"/><a href=""><i class="icon-refresh"></i></a>';
                } catch (Zend_Exception $ze) {
                    $feedback['erro'] = 1;
                    $feedback['msg'] = $ze->getMessage();
                }
            } else {
                $feedback['erro'] = 2;
                $feedback['msg'] = implode('<br/>', $erros);
            }
        }

        # FIM

        $this->view->feedback = $feedback;
        $this->renderScript('feedback.phtml');
        $this->_helper->layout->disableLayout();
    }

    public function salvarAgendamentoAction() {
        $feedback = array('erro' => 2, 'msg' => 'Desculpe, parece que nada foi alterado. Tente novamente. <meta http-equiv="refresh" content = "3"/>');


        if ($this->getRequest()->isPost()) {

            $form = $this->getRequest()->getPost();
            $treinamentoModel = new Application_Model_Treinamento();
            $funcionario = new Application_Model_Funcionario();
            $filtro = "fk_contrato_id = {$_SESSION['contrato_id']} AND funcionario.fk_empresa_id = {$_SESSION['empresa']['empresa_id']} AND alocacao.alocacao_id = {$form['fk_alocacao_id']}";
            $resultadoAlocacao = $funcionario->obterPeloFiltro($filtro);

            $dados = array();
            $erros = array();

            $dados['fk_treinamento_agenda_id'] = (int) $this->_getParam('treinamento_agenda_id');
            $dados['fk_pessoa_id'] = (int) $resultadoAlocacao[0]['pessoa_id'];
            $dados['fk_empresa_id'] = (int) $this->_getParam('fk_empresa_id');
            $dados['fk_contrato_id'] = (int) $this->_getParam('fk_contrato_id');

            if ($dados['fk_treinamento_agenda_id'] == 0) {
                $erros[] = 'Desculpe, não conseguimos identificar para qual agenda era esta requisição. Recarregue a página e tente novamente.<meta http-equiv="refresh" content = "3"/>';
            }

            if ($dados['fk_pessoa_id'] == 0) {
                $erros[] = 'Informe o funcionário.';
            }

             # ID: 2015-03-16 10:40:00
             # Verifica se a empresa está com inadimplencia atribuída pelo sistema
             
            require_once("../application/business/EmpresaBusiness.php");
            try {
                $ModeloEmpresa = new Application_Model_Empresa();
                $idEmpresa = $dados['fk_empresa_id'];
                $ResultadoComando = $ModeloEmpresa->fetchRow(array('empresa_id = ?' => $idEmpresa));
                $estaNegativada = $estaInadiplente = false;
                if ($ResultadoComando) {
                    $paramEmpresaCnpj = $ResultadoComando->empresa_cnpj;
                    $NegocioEmpresa = new EmpresaBusiness();
                    $estaNegativada = $NegocioEmpresa->possuiRestricaoDeCredito($paramEmpresaCnpj);
                    $estaInadiplente = EmpresaBusiness::estaInadimplente(array($dados['fk_contrato_id']), array($ResultadoComando->empresa_cnpj));
                }
                if ($estaInadiplente) {
                    $erros[] = 'Empresa temporariamente com registro(s) de inadimplência';
                }
                if ($estaNegativada) {
                    $erros[] = 'Empresa com restrição de crédito(negativada)';
                }
            } catch (Exception $exc) {
                $erros[] = 'Erro ao executar comando de avaliação de inadiplencia da empresa/cnpj.';
            }
            // FIM ID: 2015-03-16 10:40:00

            $adapter = Zend_Db_Table::getDefaultAdapter();
            $data = date('Y-m-d'); 

            $sql = "
                SELECT 
                    f.fichamedica_id, 
                    f.fichamedica_liberado_trabalho_altura, 
                    f.fichamedica_liberado_espaco_confinado 
                FROM agenda a
                    JOIN fichamedica f ON f.fk_agenda_id = a.agenda_id AND f.fichamedica_status = 0
                WHERE a.agenda_status = 0 
                    AND f.fichamedica_data_proximo_exame >= '{$data}' 
                    AND a.fk_pessoa_id = {$dados['fk_pessoa_id']}
                    AND f.fichamedica_resultado_aptidao = 1 
                ORDER BY f.fichamedica_data_proximo_exame DESC LIMIT 1";
            $prepare = $adapter->prepare($sql);
            $prepare->execute();
            $ASO = $prepare->fetchAll();
            if ($ASO) {
                $dados['fk_fichamedica_id'] = $ASO[0]['fichamedica_id'];
                $dados['liberacao_trabalho_altura'] = $ASO[0]['fichamedica_liberado_trabalho_altura'];
                $dados['liberacao_espaco_confinado'] = $ASO[0]['fichamedica_liberado_espaco_confinado'];
            }else{
                $dados['fk_fichamedica_id'] = NULL;
                $dados['liberacao_trabalho_altura'] = NULL;
                $dados['liberacao_espaco_confinado'] = NULL;
            }

            if (empty($erros)) {
                $agenda = $treinamentoModel->obterAgenda($dados['fk_treinamento_agenda_id']);
                $alunos = $treinamentoModel->obterAlunosDaAgenda($agenda['treinamento_agenda_id']);
                if (count($alunos) >= $agenda['treinamento_agenda_vagas']) {
                    $erros[] = 'Todas as vagas já foram preenchidas.';
                }
                
                 # Regra para verificar se o contrato a empresa possui uma proposta de
                 # treinamento e palestras aprovada pelo cliente.
                 
                try {
                    $paramContratoId = (int) $dados['fk_contrato_id'];
                    $paramEmpresaId = (int) $dados['fk_empresa_id'];
                    $NegocioTreinamento = new TreinamentoBusiness($paramContratoId, $paramEmpresaId);
                    $contratouServico = $NegocioTreinamento->possuiPropostaComercialComAceiteDoCliente();
                    if (!$contratouServico) {
                        $erros[] = 'Este contrato e empresa ainda não possui uma proposta comercial aprovada para serviços de treinamento e palestras! <meta http-equiv="refresh" content = "3"/>';
                    }
                } catch (Exception $exc) {
                    $erros[] = 'Aconteceu um erro no instante de validação de proposta de treinamento e palestras aprovada pelo cliente! <meta http-equiv="refresh" content = "3"/>';
                }
            }

            if (empty($erros)) {
                $existe = $treinamentoModel->verificarAlunoNaAgenda($dados['fk_pessoa_id'], $dados['fk_treinamento_agenda_id']);
                if ($existe) {
                    $erros[] = 'Este aluno já está na lista. <meta http-equiv="refresh" content = "3"/>';
                }
            }

            if (empty($erros)) {
                try {

                    $treinamento_agendado_id = $treinamentoModel->salvarAgendamento($dados);
                    $treinamento_agendado_dados = $treinamentoModel->obterTrainamentoAgendadoId($treinamento_agendado_id);
                    ##############################################################
                    # Registrando no Log
                    ##############################################################
                    /**/
                    $post = json_encode($dados);
                    $get = json_encode($_GET);
                    $Obs = json_encode($treinamento_agendado_dados); 
                    $log = array(
                        'log_evento' => 'salvar',
                        'log_tabela_nome' => 'treinamento_agendado',
                        'log_tabela_coluna_nome' => 'treinamento_agendado_id',
                        'log_tabela_coluna_valor' => "{$treinamento_agendado_id}",
                        'log_usuario_codigo' => "{$_SESSION['usuario_portal_id']}",
                        'log_usuario_login' => "{$_SESSION['usuario_portal_login']}",
                        'log_usuario_nome' => "{$_SESSION['contrato_responsavel_nome']}",
                        'log_endereco_captura' => "{$_SERVER['HTTP_REFERER']}",
                        'log_ip' => "{$_SERVER['SERVER_ADDR']}",
                        'log_data_hora' => date('Y-m-d H:i:s'),
                        'log_codigo_sessao_acesso' => "{$_SERVER['HTTP_COOKIE']}",
                        'log_detalhe' => 'Inserindo funcionário na agenda de treinamentos via portal do cliente',
                        'log_observacao' => "{$Obs}",
                        'log_colecao_parametros_enviados_via_post' => "{$post}",
                        'log_colecao_parametros_enviados_via_get' => "{$get}"
                    );
                    try {
                        $ModeloLog = new Application_Model_Log();
                        $ModeloLog->insert($log);
                    } catch (Exception $exlog) {
                        $erro = 1;
                        $mensagem = "Erro ao executar comando no banco de dados. Log: " . $exlog->getMessage();
                    }
                    
                    ##############################################################

                    $feedback['erro'] = 0;
                    $feedback['msg'] = 'Agendamento salvo com sucesso. <meta http-equiv="refresh" content = "1"/>';
                } catch (Exception $ze) {
                    $feedback['erro'] = 1;
                    $feedback['msg'] = $ze->getMessage();
                }
            } else {
                $feedback['erro'] = 2;
                $feedback['msg'] = implode('<br/>', $erros);
            }
        }

        $this->view->feedback = $feedback;
        $this->renderScript('feedback.phtml');
        $this->_helper->layout->disableLayout();
    }

}
