<?php

class AcervoDigitalController extends Controller {

    public function __construct(\Zend_Controller_Request_Abstract $request, \Zend_Controller_Response_Abstract $response, array $invokeArgs = array()) {
        parent::__construct($request, $response, $invokeArgs);
    }

    public function init() {
        parent::init();
    }

    public function indexAction() {

        $paramEmpresaId = (int) $this->_getParam('empresa_id', 0);
        $paramContratoId = (int) $this->_getParam('contrato_id', 0);
        $paramFuncionarioId = (int) $this->_getParam('funcionario_id', 0);

        $form = array();
        $colecaoExames = array();
        $colecaoTreinamentos = array();
        $colecaoProntuarioPorEmpresa = array();

        try {
            if ($paramFuncionarioId == 0 or $paramEmpresaId == 0 or $paramEmpresaId == 0) {
                throw new Exception('Os argumentos de entrada não estão presentes na chamada do serviço');
            }
            $form = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('pessoa');
            $ModeloFuncionario = new Application_Model_Funcionario();
            $rst = $ModeloFuncionario->obter($paramFuncionarioId);
            $pessoaId = 0;
            $form = array();
            $form['contrato_numero'] = '';

            if (is_array($rst) && count($rst) > 0) {
                $pessoaId = (int) $rst['pessoa_id'];
                $form = $rst;
            }

            $form['empresa_razao'] = null;
            $form['contrato_numero'] = null;
            $form['empresa_fantasia'] = null;

            $ModeloContrato = new Application_Model_Contrato();
            $Rst = $ModeloContrato->fetchRow(array('contrato_id = ?' => $paramContratoId));

            if (!$Rst or is_null($Rst) or $Rst == false)
                throw new Exception('Não foi possível localizar informações do contrato');
            $form['contrato_numero'] = $Rst->contrato_numero;

            $ModeloEmpresa = new Application_Model_Empresa();
            $Rst = $ModeloEmpresa->fetchRow(array('empresa_id = ?' => $paramEmpresaId));
            if (!$Rst or is_null($Rst) or $Rst == false)
                throw new Exception('Não foi possível localizar informações da empresa');

            $form['empresa_razao'] = $Rst->empresa_razao;
            $form['empresa_fantasia'] = $Rst->empresa_fantasia;

            $colecaoProntuarioPorEmpresa = Application_Model_Prontuario::obterColecaoInformacaoEmpresasComProntuario(true, array(), array(), array($pessoaId));

            // Recupera os registros de medicina
            $colecaoExames = Application_Model_Prontuario::obterColecaoProntuario(true, array('prontuario.prontuario_data DESC'), array(), array($pessoaId), array(), array(Application_Model_Prontuario::FLAG_TIPO_PRONTUARIO_EXAMES), array($paramEmpresaId), array($paramContratoId));

            // Recupera os registros de treinamento
            $colecaoTreinamentos = Application_Model_Prontuario::obterColecaoProntuario(true, array('prontuario.prontuario_data DESC'), array(), array($pessoaId), array(), array(Application_Model_Prontuario::FLAG_TIPO_PRONTUARIO_TREINAMENTO), array($paramEmpresaId), array($paramContratoId));
        } catch (Exception $exc) {
            throw $exc;
        }
        
        $this->view->form = $form;
        $this->view->colecaoGridExames = $colecaoExames;
        $this->view->colecaoGridTreinamentos = $colecaoTreinamentos;
        $this->view->colecaoProntuarioPorEmpresa = $colecaoProntuarioPorEmpresa;
    }

    public function adicionarAction() {
        $script = '/acervo-digital/form.phtml';
        $paramForm = strtolower(trim($this->_getParam('frm')));
        $forms = array('dossie-medico', 'dossie-treinamento');
        $formCustomTreinamento = array();
        if (in_array($paramForm, $forms) == false)
            throw new Exception('Verifique se o parametro FRM está presente ou se o valor atribuido esteja em valores delimitados pelo controlador');

        $paramProntuarioId = (int) $this->_getParam('protuario_id', 0);

        $form = array();
        $colecaoProcedimento = array();
        $colecaoAnexosInternos = array();
        try {
            if ($paramProntuarioId == 0) {
                throw new Exception('Os argumentos de entrada não estão presentes na chamada do serviço');
            }
            $ModeloProntuario = new Application_Model_Prontuario();
            $Rst = $ModeloProntuario->fetchRow(array('prontuario_id = ?' => $paramProntuarioId));

            if (!$Rst)
                throw new Exception('Prontuário não encontrado.');

            $paramContratoId = (int) $Rst->fk_contrato_id;
            $paramEmpresaId = (int) $Rst->fk_empresa_id;
            $paramPessoaId = (int) $Rst->fk_pessoa_id;
            $paramFuncionarioId = (int) $Rst->fk_funcionario_id;
            $paramAlocacaoId = (int) $Rst->fk_alocacao_id;
            $paramProntuarioData = $Rst->prontuario_data;
            $paramProntuarioExame = $Rst->prontuario_descricao;
            $paramProntuarioTipo = $Rst->prontuario_tipo;
            $paramProntuarioCodigoAgendamentoExame = (int) $Rst->fk_agenda_exame_id;
            $paramProntuarioCodigoAgendamentoTreinamento = (int) $Rst->fk_agenda_treinamento_id;

            $form = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('pessoa');
            $pessoaId = 0;
            $ModeloPessoa = new Application_Model_Pessoa();
            $Rst = $ModeloPessoa->fetchRow(array('pessoa_id = ?' => $paramPessoaId));

            if ($Rst instanceof Zend_Db_Table_Row_Abstract) {
                $pessoaId = (int) $Rst->pessoa_id;
                $form = $Rst->toArray();
            }

            $form['fk_alocacao_id'] = (int) $paramAlocacaoId;
            $form['contrato_id'] = $paramContratoId;
            $form['contrato_numero'] = null;
            $form['empresa_id'] = $paramEmpresaId;
            $form['empresa_razao'] = null;
            $form['empresa_fantasia'] = null;
            $form['funcionario_localizador_arquivo'] = null;
            $form['funcionario_id'] = null;
            $form['protuario_id'] = (int) $paramProntuarioId;
            $form['protuario_data'] = $paramProntuarioData;
            $form['prontuario_descricao'] = $paramProntuarioExame;
            /*
             * Resgatando informações do contrato e da empresa baseado
             * nos parametros da URL
             */
            $ModeloContrato = new Application_Model_Contrato();
            $Rst = $ModeloContrato->fetchRow(array('contrato_id = ?' => $paramContratoId));

            if (!$Rst or is_null($Rst) or $Rst == false)
                throw new Exception('Não foi possível localizar informações do contrato');
            $form['contrato_numero'] = $Rst->contrato_numero;

            $ModeloEmpresa = new Application_Model_Empresa();
            $Rst = $ModeloEmpresa->fetchRow(array('empresa_id = ?' => $paramEmpresaId));
            if (!$Rst or is_null($Rst) or $Rst == false)
                throw new Exception('Não foi possível localizar informações da empresa');
            $form['empresa_razao'] = $Rst->empresa_razao;
            $form['empresa_fantasia'] = $Rst->empresa_fantasia;

            $ModeloFuncionario = new Application_Model_Funcionario();
            $Rst = $ModeloFuncionario->fetchRow(array('funcionario_id = ?' => $paramFuncionarioId));
            if (!$Rst or is_null($Rst) or $Rst == false)
                throw new Exception('Não foi possível localizar informações do funcionário');
            $form['funcionario_id'] = $Rst->funcionario_id;
            $form['funcionario_localizador_arquivo'] = $Rst->funcionario_localizador_arquivo;

            try {
                $Cnx = Zend_Db_Table::getDefaultAdapter();
                $ModeloProcedimento = new Application_Model_Procedimento();
                $f = array(
                    "fk_prontuario_id = {$paramProntuarioId}",
                    "procedimento_status IN (3)", // status 3: PROCEDIMENTO AVULSO
                );
                $clcRst = $ModeloProcedimento->fetchAll($f, array('procedimento_nome ASC'));
                $colecaoProcedimento = array();
                $colecaoProcedimento = ($clcRst->count() > 0) ? $clcRst->toArray() : array();

                if ($paramProntuarioTipo == Application_Model_Prontuario::FLAG_TIPO_PRONTUARIO_EXAMES) {
                    // ASO
                    $item = array(
                        'descricao' => 'ASO Digital (Sem Assinatura do Médico)',
                        'url' => '/documento-operacional/imprimir-aso/agendaid/' . $paramProntuarioCodigoAgendamentoExame
                    );
                    $colecaoAnexosInternos[] = $item;
                    // Ficha Médica
                    $item = array(
                        'descricao' => 'Ficha Médica (Sem Assinatura do Médico)',
                        'url' => '/documento-operacional/imprimir-ficha-medica/agendaid/' . $paramProntuarioCodigoAgendamentoExame
                    );
                    $colecaoAnexosInternos[] = $item;
                    // Ficha RAC
                    $item = array(
                        'descricao' => 'Ficha RAC (Sem Assinatura do Médico)',
                        'url' => '/documento-operacional/imprimir-alta-rac/agendaid/' . $paramProntuarioCodigoAgendamentoExame
                    );
                    $colecaoAnexosInternos[] = $item;
                    // Ficha RAC
                    $item = array(
                        'descricao' => 'Solicitação de Laudo',
                        'url' => '/documento-operacional/imprimir-solicitacao-laudo/agendaid/' . $paramProntuarioCodigoAgendamentoExame
                    );
                    $colecaoAnexosInternos[] = $item;
                } else {
                    $item = array(
                        'descricao' => 'Certificado',
                        'url' => '/treinamento/imprimir-certificado/agendamento/' . $paramProntuarioCodigoAgendamentoTreinamento
                    );
                    $colecaoAnexosInternos[] = $item;
                }
            } catch (Exception $ex) {
                throw $ex;
            }
        } catch (Exception $exc) {
            throw $exc;
        }
        $this->view->form = $form;
        $this->view->exibirQualFormulario = $paramForm;
        $this->view->colecaoProcedimento = $colecaoProcedimento;
        $this->view->colecaoAnexosInternos = $colecaoAnexosInternos;
        $this->renderScript($script);
    }

    public function visualizarAction() {
        $this->_enviarMapeamentoDoAcessoParaCamadaVisualizacao('Cadastro', 'Acervo Digital', 'Visualizar Exames e Treinamentos');
        $script = '/acervo-digital/visualizar.phtml';
        $paramForm = strtolower(trim($this->_getParam('frm')));
        $forms = array('dossie-medico', 'dossie-treinamento');
        if (in_array($paramForm, $forms) == false)
            throw new Exception('Verifique se o parametro FRM está presente ou se o valor atribuido esteja em valores delimitados pelo controlador');

        $paramId = (int) $this->_getParam('id', 0);
        $t = $m = false;
        $t = (in_array('/treinamento-digital/alterar', $this->view->acesso) or in_array('/treinamento-digital/adicionar', $this->view->acesso));
        $m = (in_array('/prontuario-digital/alterar', $this->view->acesso) or in_array('/prontuario-digital/adicionar', $this->view->acesso));

        if (!$t or ! $m) {
            $this->redirect('/index/acesso-negado');
            exit(0);
        }

        if ($paramId <= 0)
            throw new Exception('Faltando o parametro de entrada');

        $form = array();
        $colecaoProcedimento = array();
        try {
            $form = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor('pessoa');
            $ModeloPessoa = new Application_Model_Pessoa();

            $Cnx = Zend_Db_Table::getDefaultAdapter();
            $ComandoCustom = $Cnx->select();
            $ComandoCustom->from(array('pron' => 'prontuario'))
                    ->join(array('p' => 'pessoa'), 'p.pessoa_id = pron.fk_pessoa_id')
                    ->joinLeft(array('a' => 'alocacao'), 'a.alocacao_id = pron.fk_alocacao_id', array())
                    ->where('pron.prontuario_id = ?', $paramId);
            $rst = $ComandoCustom->query()->fetch();
            (count($rst) > 0 ) ? $form = $rst : null;

            // Resgata os procedimentos de medicina
            if ($paramForm == 'dossie-medico') {
                try {
                    $ModProcedimento = new Application_Model_Procedimento();
                    $filtro = array('fk_prontuario_id = ?' => $paramId);
                    $Rst = $ModProcedimento->fetchAll($filtro);
                    $colecaoProcedimento = ($Rst->count() > 0) ? $Rst->toArray() : array();
                    $tmp = array();
                    $ModeloAnexo = new Application_Model_AnexoProcedimento();
                    foreach ($colecaoProcedimento as $procedimento) {
                        $itm = $procedimento;

                        $Rst = $ModeloAnexo->fetchRow(array('fk_procedimento_id = ?' => $procedimento['procedimento_id'], 'anx_proc_status = ?' => 0, 'anx_proc_tipo = ?' => Application_Model_AnexoProcedimento::FLAG_TIPO_PROCEDIMENTO_EXAME));
                        $itm['exame_arquivo_upload_id'] = (isset($Rst->anx_proc_id)) ? $Rst->fk_arquivo_upload_id : 0;

                        $Rst = $ModeloAnexo->fetchRow(array('fk_procedimento_id = ?' => $procedimento['procedimento_id'], 'anx_proc_status = ?' => 0, 'anx_proc_tipo = ?' => Application_Model_AnexoProcedimento::FLAG_TIPO_PROCEDIMENTO_LAUDO));
                        $itm['laudo_arquivo_upload_id'] = (isset($Rst->anx_proc_id)) ? $Rst->fk_arquivo_upload_id : 0;
                        $tmp[] = $itm;
                    }
                    $colecaoProcedimento = $tmp;
                } catch (Exception $ex) {
                    throw $ex;
                }
            }
            // Resgata os procedimentos de treinamento
            if ($paramForm == 'dossie-treinamento') {
                try {
                    $ModProcedimento = new Application_Model_Procedimento();
                    $filtro = array('fk_prontuario_id = ?' => $paramId);
                    $Rst = $ModProcedimento->fetchAll($filtro);
                    $colecaoProcedimento = ($Rst->count() > 0) ? $Rst->toArray() : array();
                    $tmp = array();
                    $ModeloAnexo = new Application_Model_AnexoProcedimento();
                    foreach ($colecaoProcedimento as $procedimento) {
                        $itm = $procedimento;
                        $Rst = $ModeloAnexo->fetchRow(array('fk_procedimento_id = ?' => $procedimento['procedimento_id'], 'anx_proc_status = ?' => 0));
                        $itm['arquivo_upload_id'] = (isset($Rst->anx_proc_id)) ? $Rst->fk_arquivo_upload_id : 0;
                        $tmp[] = $itm;
                    }
                    $colecaoProcedimento = $tmp;
                } catch (Exception $ex) {
                    throw $ex;
                }
            }
        } catch (Exception $exc) {
            throw $exc;
        }
        $this->view->exibirQualFormulario = $paramForm;
        $this->view->form = $form;
        $this->view->colecaoProcedimento = $colecaoProcedimento;
        $this->renderScript($script);
    }

    private static function _estruturarAnexoUpload($file_post) {
        $file_ary = array();
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);
        for ($i = 0; $i < $file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_ary[$i][$key] = $file_post[$key][$i];
            }
        }
        return $file_ary;
    }

    private function _processarUploadDossieMedico() {
        self::$_habilitarRegistrarLog = false;
        $erro = 1;
        $mensagens = array();
        $corrigirCampos = array();
        if ($this->getRequest()->isPost()) {
            $form = $this->getRequest()->getPost();
            $obrigatorios = array(
                'anx_proc_tipo' => array('tipo' => 'texto', 'nome' => 'Tipo de Procedimento'),
                'fk_procedimento_id' => array('tipo' => 'texto', 'nome' => 'Código do Procedimento')
            );
            $validacao = Util::validaCampos($obrigatorios, $form);
            $mensagens = $validacao["erros"];
            $corrigirCampos = $validacao["corrigir"];
            $anexo = $_FILES['anexo'];
            $visibilidadeMedico = isset($form['procedimento_visib_externa_medico']) ? (int) $form['procedimento_visib_externa_medico'] : 0;
            $anx_proc_id = isset($form['anx_proc_id']) ? (int) $form['anx_proc_id'] : 0;
            unset($form['anx_proc_id']);
            unset($form['procedimento_visib_externa_medico']);
            if (!isset($anexo['name']) or ( isset($anexo['name']) && strlen($anexo['name']) == 0)) {
                $mensagens[] = 'selecione um arquivo para o anexo';
            }
            if (count($mensagens) == 0) {
                require_once('../application/business/ArquivoUploadBusiness.php');
                $procedimentoId = $form['fk_procedimento_id'];
                try {
                    $Cnx = Zend_Db_Table::getDefaultAdapter();
                    $Cnx->beginTransaction();
                    try {
                        $ModAnxProcedimento = new Application_Model_AnexoProcedimento();
                        $ModeloProcedimento = new Application_Model_Procedimento();
                        $NegocioUpload = new ArquivoUploadBusiness();
                        $anx_proc = $form;
                        $id = 0;
                        if (isset($anexo['name']) && strlen($anexo['name']) > 0) {
                            $NegocioUpload->observacao = 'Upload feito pelo usuário na tela de Acervo Digital / Prontuário  Eletrônico - Upload do exame/treinamento';
                            $NegocioUpload->descricao = 'procedimento.procedimento_id=' . $procedimentoId;
                            $id = $NegocioUpload->armazenar($anexo);
                            $anx_proc['fk_arquivo_upload_id'] = $id;
                            $anx_proc_id = $ModAnxProcedimento->insert($anx_proc);
                            if ($visibilidadeMedico == 0) {
                                $ModeloProcedimento->update(array('procedimento_visib_externa_medico' => 0), array('procedimento_id = ?' => $procedimentoId));
                            }
                        }
                        $Cnx->commit();
                        $erro = 0;
                        $this->_log->logTabelaNome = 'anx_proc';
                        $this->_log->logTabelaColunaNome = 'anx_proc_id';
                        $this->_log->logTabelaColunaValor = $anx_proc_id;
                        $this->_log->logDetalhe = 'Tentativa de inserir um anexo de procedimento de ' . $form['anx_proc_tipo'];
                        $mensagens[] = 'Anexo salvo com sucesso!';
                        self::$_habilitarRegistrarLog = true;
                    } catch (Exception $e) {
                        $Cnx->rollBack();
                        throw $e;
                    }
                } catch (Exception $exc) {
                    $mensagens[] = "Erro ao executar comando no banco de dados. Detalhes: " . $exc->getMessage() . '<br/>' . $exc->getTraceAsString();
                }
            }
        }
        // Montando um retorno customizado para o modal de upload
        $feedback = array(
            'mensagem' => implode('<br/>', $mensagens),
            'erro' => $erro
        );
        $this->_helper->layout->disableLayout();
        $this->view->resposta = $feedback;
        // Chama uma "view" totalmente customizada
        $this->renderScript("/acervo-digital/resposta-modal.phtml");
    }

    private function _processarFormularioDossieTreinamentos() {
        self::$_habilitarRegistrarLog = false;
        $erro = 1;
        $mensagens = array();
        $corrigirCampos = array();
        if ($this->getRequest()->isPost()) {
            $form = $this->getRequest()->getPost();
            $obrigatorios = array(
                'prontuario_localizador' => array('tipo' => 'texto', 'nome' => 'Código Localizador'),
                'prontuario_data' => array('tipo' => 'texto', 'nome' => 'Data'),
                'prontuario_descricao' => array('tipo' => 'texto', 'nome' => 'Tipo de Exame')
            );

            $validacao = Util::validaCampos($obrigatorios, $form);
            $mensagens = $validacao["erros"];
            $corrigirCampos = $validacao["corrigir"];

            if (count($mensagens) == 0) {
                $procedimentos = (isset($form['procedimento_nome']) && is_array($form['procedimento_nome'])) ? $form['procedimento_nome'] : array();
                $datasRealizacao = (isset($form['procedimento_data']) && is_array($form['procedimento_data'])) ? $form['procedimento_data'] : array();
                $datasValidade = (isset($form['procedimento_data_validade']) && is_array($form['procedimento_data_validade'])) ? $form['procedimento_data_validade'] : array();
                // $visibilidade = (isset($form['procedimento_visib_externa_medico']) && is_array($form['procedimento_visib_externa_medico'])) ? $form['procedimento_visib_externa_medico'] : array();
                $AnexoTreinamento = (isset($_FILES['anexo_treinamento']) && is_array($_FILES['anexo_treinamento'])) ? $_FILES['anexo_treinamento'] : array();
                // Organiza um array de post enviado usando upload multiplo.
                $AnexoTreinamento = self::_estruturarAnexoUpload($AnexoTreinamento);

                $prontuario = array(
                    'prontuario_localizador' => trim($form['prontuario_localizador']),
                    'prontuario_data' => Util::dataBD($form['prontuario_data']),
                    'prontuario_descricao' => trim($form['prontuario_descricao']),
                    'prontuario_tipo' => Application_Model_Prontuario::FLAG_TIPO_PRONTUARIO_TREINAMENTO,
                    'prontuario_status' => 0,
                    'fk_pessoa_id' => (int) $form['pessoa_id'],
                    'fk_alocacao_id' => (isset($form['fk_alocacao_id']) && (int) $form['fk_alocacao_id'] > 0) ? (int) $form['fk_alocacao_id'] : null,
                    'fk_contrato_id' => (isset($form['fk_contrato_id']) && (int) $form['fk_contrato_id'] > 0) ? (int) $form['fk_contrato_id'] : null,
                    'fk_empresa_id' => (isset($form['fk_empresa_id']) && (int) $form['fk_empresa_id'] > 0) ? (int) $form['fk_empresa_id'] : null,
                    'prontuario_dh_criacao' => date('Y-m-d H:i:s')
                );
                require_once('../application/business/ArquivoUploadBusiness.php');
                try {
                    $Cnx = Zend_Db_Table::getDefaultAdapter();
                    $Cnx->beginTransaction();
                    try {
                        $ModProntuario = new Application_Model_Prontuario();
                        $prontuarioId = $ModProntuario->insert($prontuario);
                        $ModProcedimento = new Application_Model_Procedimento();
                        $ModAnxProcedimento = new Application_Model_AnexoProcedimento();
                        $NegocioUpload = new ArquivoUploadBusiness();
                        $ordemTipagemAnexo = array(
                            0 => Application_Model_AnexoProcedimento::FLAG_TIPO_PROCEDIMENTO_CERTIFICADO,
                            1 => Application_Model_AnexoProcedimento::FLAG_TIPO_PROCEDIMENTO_AVALIACAO
                        );
                        $quantidadeTipagem = count($ordemTipagemAnexo);
                        foreach ($procedimentos as $chave => $procedimento) {
                            if ($chave > $quantidadeTipagem) {
                                throw new Exception('Verifique o parâmetro que define a ordem do anexo juntamente com a quantidade, neste momento são aceitos apenas ' . $quantidadeTipagem . ' anexos');
                            }
                            $data = (isset($datasRealizacao[$chave]) && strlen($datasRealizacao[$chave]) == 10) ? Util::dataBD($datasRealizacao[$chave]) : null;
                            $validade = (isset($datasValidade[$chave]) && strlen($datasValidade[$chave]) == 10) ? Util::dataBD($datasValidade[$chave]) : null;
                            $params = array(
                                'procedimento_nome' => $procedimento,
                                'procedimento_data' => $data,
                                'procedimento_data_validade' => $validade,
                                'procedimento_status' => 0,
                                'procedimento_visib_externa' => 1,
                                'procedimento_visib_externa_medico' => 0,
                                'fk_prontuario_id' => $prontuarioId
                            );
                            $procedimentoId = $ModProcedimento->insert($params);
                            $anx_proc = array();
                            $anx_proc['anx_proc_status'] = 0;
                            $anx_proc['fk_procedimento_id'] = $procedimentoId;
                            if (isset($AnexoTreinamento[$chave]['name']) && strlen($AnexoTreinamento[$chave]['name']) > 0) {
                                $anx_proc['anx_proc_tipo'] = $ordemTipagemAnexo[$chave];
                                $NegocioUpload->observacao = 'Upload feito pelo usuário na tela de Acervo Digital / Prontuário  Eletrônico - Upload de treinamento';
                                $NegocioUpload->descricao = 'procedimento.procedimento_id=' . $procedimentoId;
                                $id = $NegocioUpload->armazenar($AnexoTreinamento[$chave]);
                                $anx_proc['fk_arquivo_upload_id'] = $id;
                                $ModAnxProcedimento->insert($anx_proc);
                            }
                        }
                        $Cnx->commit();
                        $erro = 0;
                        $this->_log->logTabelaNome = 'prontuario';
                        $this->_log->logTabelaColunaNome = 'prontuario_id';
                        $this->_log->logTabelaColunaValor = $prontuarioId;
                        $this->_log->logDetalhe = 'Tentativa de inserir um prontuário de treinamento';
                        $url = '/acervo-digital/index/cpf/' . $form['pessoa_cpf'];
                        $html = 'Prontuário registrado com sucesso!&nbsp;<a href="javascript:;" onclick="window.location.reload(true)"><i class="icon-refresh"></i>&nbsp;Atualizar</a>';
                        $html .= '&nbsp;&nbsp;<a href="' . $url . '"><i class="icon-chevron-left"></i>&nbsp;Voltar</a>';
                        $mensagens[] = $html;
                        self::$_habilitarRegistrarLog = true;
                    } catch (Exception $e) {
                        $Cnx->rollBack();
                        throw $e;
                    }
                } catch (Exception $exc) {
                    $mensagens[] = "Erro ao executar comando no banco de dados. Detalhes: " . $exc->getMessage() . '<br/>' . $exc->getTraceAsString();
                }
            }
        }
        // Monta a resposta enviar em formato JSON para o cliente
        $feedback = array(
            'erro' => $erro,
            'msg' => implode("<br/>", $mensagens),
            'corrigir' => $corrigirCampos
        );
        $this->feedback($feedback);
    }

    public function salvarAction() {
        $form = strtolower($this->_getParam('frm'));
        $forms = array('dossie-medico', 'dossie-treinamento');
        /*
         * Como existe mais de um formulário para ser processado nesse
         * controller precisei criar um parâmetro atribuido na URL (Query String)
         * que define qual formulário deve ser processado
         */
        $t = (in_array('/treinamento-digital/adicionar', $this->view->acesso) or in_array('/treinamento-digital/alterar', $this->view->acesso));
        $m = (in_array('/prontuario-digital/adicionar', $this->view->acesso) or in_array('/prontuario-digital/alterar', $this->view->acesso));
        if (in_array($form, $forms) && ($t or $m) && $this->getRequest()->isPost()) {
            if ($form == 'dossie-medico')
                $this->_processarUploadDossieMedico();
            if ($form == 'dossie-treinamento')
                $this->_processarFormularioDossieTreinamentos();
        } else {
            $feedback = array(
                'erro' => 1,
                'msg' => 'O formulário que você tentou enviar não está autorizado ou não possui o parametro que o identifica',
                'corrigir' => null
            );
            $this->feedback($feedback);
        }
    }

}
