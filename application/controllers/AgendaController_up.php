<?php

class AgendaController extends Controller
{

    public function _indexAction()
    {
        try {
            $empresaId = $_SESSION['empresa']['empresa_id'];
            $contratoId = $_SESSION['contrato_id'];
            $filtro = " agenda.fk_empresa_id = '{$empresaId}' ";
            $filtro .= " AND agenda.fk_contrato_id = '{$contratoId}' ";
            $filtro .= " AND agenda.agenda_data_exame >= CURDATE() ";
            $filtro .= " AND agenda.agenda_status = 0 ";
            $ordenarPor = "agenda.agenda_data_exame DESC ";
            $Agenda = new Application_Model_Agenda();
            $this->view->itens = $Agenda->buscaCompletaUsandoClausula($filtro, $ordenarPor);
        } catch (Exception $ex) {
            $this->view->erro = $ex->getMessage();
        }
    }

    public function indexAction()
    {
        $buscar = $this->_getParam('like', '');
        $pagina = $this->_getParam('page', 1);
        $empresaId = $_SESSION['empresa']['empresa_id'];
        $contratoId = $_SESSION['contrato_id'];

        $this->view->parametrosPesquisa = json_decode($this->_getParam('filters', ''), true);
        $buscar = $this->view->parametrosPesquisa['filtro-pessoa'];

        $filtro = " pessoa.pessoa_nome LIKE '%{$buscar}%' ";
        $filtro .= " AND agenda.fk_empresa_id = '{$empresaId}' ";
        $filtro .= " AND agenda.fk_contrato_id = '{$contratoId}' ";
        $filtro .= " AND agenda.agenda_data_exame >= CURDATE() ";
        $filtro .= " AND agenda.agenda_status = 0 ";
        $ordenarPor = "agenda.agenda_data_exame DESC ";
        $resultadoPaginado = null;
        $limite = "0,99999999999";
        $imprimirComando = false;
        $resultadoConsulta = array();
        try {
            $agenda = new Application_Model_Agenda();
            $resultadoConsulta = $agenda->buscaCompletaUsandoClausula($filtro, $ordenarPor, $limite, $imprimirComando);
            if (is_array($resultadoConsulta)) {
                $auxiliarResultado = array();
                $alocacao = new Application_Model_Alocacao();
                foreach ($resultadoConsulta as $i => $itemResultado) {
                    $filtro = "alocacao.alocacao_id = {$itemResultado['fk_alocacao_id']}";
                    $resultadoAlocacao = $alocacao->buscaCompletaUsandoClausula($filtro);
                    $itemResultado["alocacao"] = $resultadoAlocacao[0];
                    $auxiliarResultado[$i] = $itemResultado;
                }
                $resultadoConsulta = $auxiliarResultado;
            }
            $resultadoPaginado = Zend_Paginator::factory($resultadoConsulta);
            $resultadoPaginado->setCurrentPageNumber($pagina);
        } catch (Exception $e) {
            $this->_enviarCapturaExcecaoParaView($e->getMessage());
        }
        $this->view->itensPaginados = $resultadoPaginado;
        $this->view->like = $buscar;
    }

    public function adicionarAction()
    {
        $unidadeId = (int) $this->_getParam("unidade", 0);
        $empresaId = $_SESSION['empresa']['empresa_id'];
        $contratoId = $_SESSION['contrato_id'];
        $atributos = ['tipoExame' => [], 'alocacao' => []];
        $tabelas = ['agenda', 'pessoa', 'funcionario', 'alocacao', 'setor', 'cargo', 'funcao'];
        $mes = date('m');
        $ano = date('Y');
        try {
            $unidadeModel = new Application_Model_Unidade();
            $unidade = $unidadeModel->obter($unidadeId);
            $atributos = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor($tabelas);
            $tipoExame = new Application_Model_TipoExame();
            $atributos["tipoExame"] = $tipoExame->obterTodos();
            $funcionario = new Application_Model_Funcionario();
            $filtro = "fk_contrato_id = {$contratoId} AND funcionario.fk_empresa_id = {$empresaId} ";
            $filtro .= " AND funcionario_status = 0";
            $resultadoAlocacao = $funcionario->obterPeloFiltro($filtro);
            $atributos["alocacao"] = $resultadoAlocacao;
        } catch (Exception $ex) {
            $this->_enviarCapturaExcecaoParaView($ex->getMessage());
        }
        $this->view->ano = $ano;
        $this->view->mes = $mes;
        $this->view->unidade = $unidade;
        $this->view->atributos = $atributos;
        $this->renderScript('agenda/formulario.phtml');
    }

    public function alterarAction()
    {
        $empresaId = $_SESSION['empresa']['empresa_id'];
        $contratoId = $_SESSION['contrato_id'];
        $atributos = array(
            'tipoExame' => array(),
            'alocacao' => array(),
        );
        $tabelas = array('agenda', 'pessoa', 'funcionario', 'alocacao', 'setor', 'cargo', 'funcao');
        $agendaId = $this->_getParam("id", 0);
        try {
            // Recupera itens agenda
            $agenda = new Application_Model_Agenda();
            $filtro = "agenda.agenda_id = {$agendaId}";
            $resultadoAgenda = $agenda->buscaCompletaUsandoClausula($filtro);
            $atributos = (count($resultadoAgenda) > 0) ? $resultadoAgenda[0] : array();
            $atributos['agenda_data_exame'] = Util::dataBR($atributos['agenda_data_exame']);
            //var_dump(Util::dataBR($atributos['agenda_data_exame']));
            $tipoExame = new Application_Model_TipoExame();
            $atributos["tipoExame"] = $tipoExame->obterTodos();

            $funcionario = new Application_Model_Funcionario();
            $filtro = "fk_contrato_id = {$contratoId} AND funcionario.fk_empresa_id = {$empresaId} ";
            $filtro .= " AND funcionario_status = 0";
            $resultadoAlocacao = $funcionario->obterPeloFiltro($filtro);
            $atributos["alocacao"] = $resultadoAlocacao;
        } catch (Exception $ex) {
            $this->_enviarCapturaExcecaoParaView($ex->getMessage());
        }
        $this->view->atributos = $atributos;
        //var_dump($this->view->atributos['agenda_observacao']);die;
        $this->renderScript('/agenda/formulario.phtml');
    }

    public function salvarAction()
    {
        $erro = 2;
        $mensagem = null;
        $corrigir = [];
        $codigoJavascript = null;
        if ($this->getRequest()->isPost()) {
            $itemMensagem = [];
            $parametros = $this->getRequest()->getParams();
            $unidadeId = (int) $parametros['fk_unidade_id'];
            $agendaId = (isset($parametros['agenda_id'])) ? (int) $parametros['agenda_id'] : 0;
            $validarCampos = [
                'fk_horario_global_id' => ['tipo' => 'texto', 'nome' => 'Horário'],
                'agenda_data_exame' => ['tipo' => 'texto', 'nome' => 'Data Agendamento'],
                'fk_tipoexame_id' => ['tipo' => 'texto', 'nome' => 'Tipo de Exame'],
                'fk_alocacao_id' => ['tipo' => 'texto', 'nome' => 'Nome'],
                'pessoa_identidade' => ['tipo' => 'texto', 'nome' => 'Identidade (<i>*ajuste no cadastro do funcionário)</i>'],
            ];
            $validacao = Util::validaCampos($validarCampos, $parametros);

            // Ao agendar um funcionário, o SISS deverá verificar se existe alguma função atribuída para o funcionário, caso não haja, o SISS deverá direcionar o usuário para o cadastro de funcionário, e selecionar a função, setor.
            $empresaId = $_SESSION['empresa']['empresa_id'];
            $contratoId = $_SESSION['contrato_id'];
            $alocacaoContidaPcmso = false;
            try {
                $alocacaoId = isset($parametros['fk_alocacao_id']) ? (int) $parametros['fk_alocacao_id'] : 0;
                $ModeloAlocacao = new Application_Model_Alocacao();
                $RstAlocacao = $ModeloAlocacao->fetchRow(array('alocacao_id = ?' => $alocacaoId));
                if ($RstAlocacao) {
                    $ModeloItemPcmso = new Application_Model_ItemPcmso();
                    $colecaoComResultados = $ModeloItemPcmso->buscarColecaoItensDoPcmsoMaisAtual($contratoId, $empresaId);
                    foreach ($colecaoComResultados as $item) {
                        #util::dump($RstAlocacao->fk_item_pcmso_id);
                        #util::dump($item);
                        /*
                        if ($item['fk_ghe_id'] == $RstAlocacao->fk_ghe_id && $item['fk_funcao_id'] == $RstAlocacao->fk_funcao_id && $item['fk_cargo_id'] == $RstAlocacao->fk_cargo_id && $item['fk_setor_id'] == $RstAlocacao->fk_setor_id) {
                            $alocacaoContidaPcmso = true;
                            break;
                        }
                        */
                        if ($item['item_pcmso_id'] == $RstAlocacao->fk_item_pcmso_id) {
                            $alocacaoContidaPcmso = true;
                            break;
                        }
                    }
                }
            } catch (Exception $Exc) {
                $validacao['erros'][] = 'Erro ao validar a existência';
            }

            if ($alocacaoContidaPcmso == false) {
                //$validacao['erros'][] = 'Não foi encontrado o cargo, função, setor e GHE no PCMSO atual da empresa <a href="/funcionario/alterar/id/' . $RstAlocacao->fk_funcionario_id . '"> clique aqui para atualizar</a> e depois tente fazer o agendamento novamente';
                $validacao['erros'][] = 'Alocação do funcionário precisa ser atualizada <a href="/funcionario/alterar/id/' . $RstAlocacao->fk_funcionario_id . '"> clique aqui para atualizar</a> e depois tente fazer o agendamento novamente';
            }

            $agenda = new Application_Model_Agenda();
            $VerificarAgendaPosterior = $agenda->verificarAgendamentoPosterior($parametros['fk_pessoa_id']);
            if (!empty($VerificarAgendaPosterior)) {
                $validacao['erros'][] = "<i class='icon-remove'></i>&nbsp;O agendamento não pode ser feito pois esse funcionário já possui uma agenda ativa no dia ".Util::dataBR($VerificarAgendaPosterior['agenda_data_exame'])." na unidade ".$VerificarAgendaPosterior['unidade_sigla'].".";
            }

            if (count($validacao['erros']) == 0) {
                // verifica se existe atendimento na data solicitada
                $teraAtentimento = true;
                try {

                    // $qtd_max = $parametros['turno'] === 'M' ? (int) Application_Model_Parametro::obterValor('Portal.QtdMaxAgendamentoManha', $unidadeId) : (int) Application_Model_Parametro::obterValor('Portal.QtdMaxAgendamentoTarde', $unidadeId);
                    // $disponibilidade = new Application_Model_DisponibilidadeAtendimento();
                    $agenda = new Application_Model_Agenda();

                    $agenda_data_exame = Util::dataBD($parametros['agenda_data_exame']);

                    // $resultado = $disponibilidade->obterDisponibilidadeAtendimentoPorTurno($agenda_data_exame, $parametros['turno'], $unidadeId);
                    // $quantidade = isset($resultado['quantidade']) ? (int) $resultado['quantidade'] : null;

                    // $disp = is_numeric($quantidade) ? $quantidade : $qtd_max;
                    // $agendados = $agenda->contarAgendaPorDataTurno($agenda_data_exame, $parametros['turno'], $unidadeId);
                    // $disp -= $agendados;

                    if (Util::eregFimDeSemana($parametros['agenda_data_exame'])) {
                        $itemMensagem[] = "<i class='icon-remove'></i>&nbsp;Não atendemos aos finais de semana.";
                    }

                    if (Util::calculaDiasEntre(date('d/m/Y'), $parametros['agenda_data_exame']) < 1) {
                        $itemMensagem[] = "<i class='icon-remove'></i>&nbsp;O exame tem que ser programado com pelo menos 1 (um) dia de antecedência!";
                    }

                    $disponivel = $agenda->vagasDisponiveis($unidadeId, $agenda_data_exame, $parametros['fk_horario_global_id']);

                    if ($disponivel < 1) {
                        $itemMensagem[] = "<i class='icon-remove'></i>&nbsp;Não há mais vagas na data selecionada!";
                    }

                    if ($agendaId == 0) {
                        // Verifica se o funcionário já foi agendado
                        $a = new Application_Model_Agenda();
                        $filtro = [
                            'agenda_status = ?' => 0,
                            'fk_alocacao_id = ?' => $parametros['fk_alocacao_id'],
                            'agenda_data_exame = ?' => Util::dataBD($parametros['agenda_data_exame']),
                        ];
                        $resutadoConsulta = $a->fetchAll($filtro)->toArray();
                        if (is_array($resutadoConsulta) && count($resutadoConsulta) > 0) {
                            $itemMensagem[] = "<i class='icon-remove'></i>&nbsp;Já existe um agendamento nesta data para este funcionário!";
                        }
                    }

                    if ($agendaId == 0) {
                        // Verifica se o funcionário está com o mesmo tipo de exame agendado para dia sequente
                        $a = new Application_Model_Agenda();
                        $resutadoConsulta = $a->agendaDuplicidadeSequente($parametros['fk_alocacao_id'], Util::dataBD($parametros['agenda_data_exame']), $parametros['fk_tipoexame_id'], $parametros['fk_horario_global_id']);
                        //util::dump($parametros);

                        if (is_array($resutadoConsulta) && count($resutadoConsulta) > 0) {
                            #Se a data do novo agendamento for maior ou igual ao ultimo exame
                            $itemMensagem[] = "<i class='icon-remove'></i>&nbsp;Já existe uma agenda ativa para o funcionário ". $resutadoConsulta['pessoa_nome'] ." para exame ". $resutadoConsulta['tipoexame_nome'] ." no dia ". Util::dataBR($resutadoConsulta['agenda_data_exame']) .". Exclua o agendamento existente para poder escolher uma data diferente.";
                        }

                    }

                    if (count($itemMensagem) > 0) {
                        $mensagem = implode('<br/>', $itemMensagem);
                        $teraAtentimento = false;
                    }
                } catch (Exception $ex1) {
                    $erro = 2;
                    $mensagem = "Erro ao executar comando no banco de dadosdd. Detalhes: {$ex1->getMessage()}";
                }

                if ($teraAtentimento) {
                    $empresaId = $_SESSION['empresa']['empresa_id'];
                    $contratoId = $_SESSION['contrato_id'];
                    $tabelaAgenda = [
                        'agenda_criada_em' => date('Y-m-d H:i:s'),
                        'agenda_inserida_via' => 'ONLINE',
                        'fk_empresa_id' => $empresaId,
                        'fk_contrato_id' => $contratoId,
                        'fk_tipoexame_id' => $parametros['fk_tipoexame_id'],
                        'fk_pessoa_id' => $parametros['fk_pessoa_id'],
                        'fk_alocacao_id' => $parametros['fk_alocacao_id'],
                        'agenda_data_exame' => Util::dataBD($parametros['agenda_data_exame']),
                        'agenda_observacao' => $parametros['agenda_observacao'],
                        // 'turno' => $parametros['turno'],
                        'fk_unidade_id' => $unidadeId,
                        'fk_horario_global_id' => $parametros['fk_horario_global_id']
                    ];

                    try {
                        $conexaoDireta = Zend_Db_Table::getDefaultAdapter();
                        try {

                            $agenda = new Application_Model_Agenda();

                            if ($agendaId == 0) {
                                // Comando de inserção
                                $tabelaAgenda['agenda_criada_em'] = date('Y-m-d H:i:s');
                                $agendaId = $agenda->insert($tabelaAgenda);
                                #$rst = self::_processarApiRegistroSenhaFilaMedicina($agendaId);
                                 // Gera um registro em fila (senha para atendimento)
                                require_once("../application/business/FilaMedicinaBusiness.php");
                                FilaMedicinaBusiness::registrar($agendaId, 'SISTEMA_WEB_INTERNO', null, null, null, false);

                                ##############################################################
                                # Registrando no Log
                                ##############################################################
                                /**/
                                $post = json_encode($parametros);
                                $get = json_encode($_GET);
                                $Obs = json_encode($_POST); 
                                $log = array(
                                    'log_evento' => 'salvar',
                                    'log_tabela_nome' => 'agenda',
                                    'log_tabela_coluna_nome' => 'agenda_id',
                                    'log_tabela_coluna_valor' => "{$agendaId}",
                                    'log_usuario_codigo' => "{$_SESSION['usuario_portal_id']}",
                                    'log_usuario_login' => "{$_SESSION['usuario_portal_login']}",
                                    'log_usuario_nome' => "{$_SESSION['contrato_responsavel_nome']}",
                                    'log_endereco_captura' => "{$_SERVER['HTTP_REFERER']}",
                                    'log_ip' => "{$_SERVER['SERVER_ADDR']}",
                                    'log_data_hora' => date('Y-m-d H:i:s'),
                                    'log_codigo_sessao_acesso' => "{$_SERVER['HTTP_COOKIE']}",
                                    'log_detalhe' => 'Inserindo agendamento via portal do cliente',
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
                            } else {
                                $antigo = $agendaId;
                                // Comando de alteração
                                $tabelaAgenda['agenda_criada_em'] = date('Y-m-d H:i:s');
                                $onde = ['agenda_id = ?' => $agendaId];
                                $agendaId = $agenda->update($tabelaAgenda, $onde);
                                $agendaId = $antigo;
                                if (strtotime(Util::dataBD($parametros['data_antiga'])) == strtotime(Util::dataBD($parametros['agenda_data_exame']))) {

                                } else {
                                    $resposta = $this->_salvarOrdemAtendimento($agendaId);
                                    $json = json_decode($resposta, true);

                                    if (is_array($json) && is_array($json['mensagens'])) {
                                        if ((int) $json['salvou'] == 0) {
                                            $agenda->delete(['agenda_id = ?' => $agendaId]);
                                            throw new Exception("Erro ao salvar ordem de atendimento: " . implode(', ', $json['mensagens']));
                                        }
                                    }
                                }
                            }

                            $Comando = $conexaoDireta->select();
                            $Comando->from(['o' => 'os'], '*')
                                ->join(['co' => 'cobrancaos'], 'co.fk_os_id = o.os_id', [])
                                ->join(['cp' => 'categoriadoproduto'], 'cp.categoriadoproduto_id = co.fk_categoriadoproduto_id', array())
                                ->where('o.os_aprovada = ?', 1)
                                ->where('o.fk_contrato_id = ?', $contratoId)
                                ->where('cp.categoriadoproduto_codigo_fixo = ?', '0002')
                                ->where('co.cobrancaos_status = ?', 0)
                                ->order(['o.os_id DESC']);

                            $rst = $Comando->query()->fetch();
                            // Se não achar uma OS lança uma exceção
                            if (count($rst['os_id']) == 0) {
                                $conexaoDireta->update('agenda', ['agenda_status' => 2], ['agenda_id = ?' => $agendaId]);
                                $conexaoDireta->update('produto_agenda', ['produto_agenda_status' => 2], ['fk_agenda_id = ?' => $agendaId]);
                                throw new Exception('Não foi possível identificar uma Ordem de serviço aprovada para execução deste serviço neste contrato. Por favor entre em contato com setor de atendimento do prestador de serviço!');
                            }
                            $conexaoDireta->update('agenda', ['fk_os_id' => $rst['os_id']], ['agenda_id = ?' => $agendaId]);

                            $erro = 0;
                            $mensagem = 'Agendamento salvo com sucesso! <a href="/agenda/adicionar/unidade/'.$unidadeId.'">Adicionar</a>';
                            $codigoJavascript = 'renderizarModalInformativo()';
                        } catch (Exception $ex2) {
                            $erro = 1;
                            #$mensagem = "Erro ao executar comando no banco de dados. Detalhes2: " . $ex2->getTraceAsString();
                            $mensagem = "Erro ao executar comando no banco de dados. Detalhes2: " . $ex2->getMessage();
                        }
                    } catch (Exception $ex3) {
                        $erro = 1;
                        $mensagem = "Erro ao iniciar transação no banco de dados1. Detalhes: " . $ex3->getMessage();
                    }
                }
            } else {
                $mensagem = implode("<br/>", $validacao['erros']);
                $corrigir = $validacao['corrigir'];
            }
        }
        $feedback = ["erro" => $erro, "msg" => $mensagem, 'corrigir' => $corrigir, 'js' => $codigoJavascript];
        $this->feedback($feedback);
    }

    public function salvarAction2()
    {

        if ($this->getRequest()->isPost()) {
            $feedback = array("erro" => 1, "msg" => "");

            $post = $this->getRequest()->getPost();

            $post["pessoa_cpf"] = preg_replace('/\D/', '', $post["pessoa_cpf"]);
            $post["pessoa_data_nascimento"] = Util::dataBD($post["pessoa_data_nascimento"]);
            $post["funcionario_data_admissao"] = Util::dataBD($post["funcionario_data_admissao"]);

            $funcionario_id = (int) $post["funcionario_id"];
            $pessoa_id = (int) $post["fk_pessoa_id"];
            $alocacao_id = (int) $post["alocacao_id"];

            $model_funcionario = new Application_Model_Funcionario();
            $model_pessoa = new Application_Model_Pessoa();
            $model_alocacao = new Application_Model_Alocacao();

            $existe = $model_funcionario->verificarAlocacaoPeloCpf($post["pessoa_cpf"], (int) $post["fk_empresa_id"]);

            $setor = $post['setor_nome'];
            $setorid = (int) $post['setor_id'];
            unset($post['setor_id']);

            if ($funcionario_id < 1 && !empty($existe)) {
                $feedback = array(
                    'erro' => 2,
                    'msg' => "Este CPF já está alocado nesta empresa.");
            }

            if ($feedback['erro'] != 2) {

                $validacao = Util::validaCampos(array(
                    'fk_empresa_id' => array('tipo' => 'texto', 'nome' => 'Empresa'),
                    'fk_contrato_id' => array('tipo' => 'texto', 'nome' => 'Contrato'),
                    'pessoa_cpf' => array('tipo' => 'texto', 'nome' => 'CPF'),
                    'pessoa_nome' => array('tipo' => 'texto', 'nome' => 'Nome'),
                    'pessoa_data_nascimento' => array('tipo' => 'texto', 'nome' => 'Data de Nascimento'),
                    'setor_nome' => array('tipo' => 'texto', 'nome' => 'Setor'),
                    'fk_cargo_id' => array('tipo' => 'texto', 'nome' => 'Cargo'),
                    'fk_funcao_id' => array('tipo' => 'texto', 'nome' => 'Funcao')), $post);

                if (count($validacao["erros"]) === 0) {

                    $atributos_funcionario = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor("funcionario");
                    $atributos_pessoa = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor("pessoa");
                    $atributos_alocacao = Application_Model_DbUtil::obterAtributosTabelaComoChaveDoVetor("alocacao");

                    $dados_funcionario = array();
                    $dados_pessoa = array();
                    $dados_alocacao = array();

                    foreach ($post as $campo => $valor) {
                        if (array_key_exists($campo, $atributos_funcionario)) {
                            $dados_funcionario[$campo] = $valor;
                        } else if (array_key_exists($campo, $atributos_pessoa)) {
                            $dados_pessoa[$campo] = $valor;
                        } else if (array_key_exists($campo, $atributos_alocacao)) {
                            $dados_alocacao[$campo] = $valor;
                        }
                    }

                    try {

                        $adapter = Zend_Db_Table::getDefaultAdapter();
                        $adapter->beginTransaction();

                        try {

                            if ($pessoa_id > 0) {
                                $model_pessoa->update($dados_pessoa, array('pessoa_id = ?' => $pessoa_id));
                            } else {
                                $pessoa_id = $model_pessoa->insert($dados_pessoa);
                            }

                            $dados_funcionario["fk_pessoa_id"] = $pessoa_id;

                            try {

                                $m = new Application_Model_Setor();
                                if ($setorid > 0) {
                                    $i = $setorid;
                                    $m->update(array('setor_nome' => $setor), array('setor_id = ?' => $i));
                                } else {
                                    $e = $m->fetchRow(array('setor_nome = ?' => $setor));
                                    if (is_null($e) || !$e) {
                                        $i = $m->insert(array('setor_nome' => $setor));
                                    } else {
                                        $i = $e['setor_id'];
                                    }

                                }

                                $dados_alocacao['fk_setor_id'] = $i;

                                if ($funcionario_id > 0) {
                                    $model_funcionario->update($dados_funcionario, array('funcionario_id = ?' => $funcionario_id));
                                } else {
                                    $funcionario_id = $model_funcionario->insert($dados_funcionario);
                                }

                                $dados_alocacao["fk_funcionario_id"] = $funcionario_id;

                                if ($alocacao_id > 0) {
                                    $model_alocacao->update($dados_alocacao, array('alocacao_id = ?' => $alocacao_id));
                                } else {
                                    $alocacao_id = $model_alocacao->insert($dados_alocacao);
                                }

                                $feedback = array(
                                    'erro' => 0,
                                    'msg' => '<i class="icon-ok"></i>&nbsp;Funcionário salvo!&nbsp;<a href="/funcionario/adicionar/"><i class="icon-plus-sign"></i>&nbsp;Novo</a>&nbsp;<a href="/funcionario/alterar/id/' . $funcionario_id . '/"><i class="icon-edit"></i>&nbsp;Alterar</a>',
                                );

                                $adapter->commit();
                            } catch (Exception $eInsFunc) {
                                $feedback = array(
                                    'erro' => 1,
                                    'msg' => "<b>Erro ao registrar o funcionário:</b> " . $eInsFunc->getMessage());
                            }
                        } catch (Exception $eInsPes) {
                            $feedback = array(
                                'erro' => 1,
                                'msg' => "<b>Erro ao registrar o funcionário:</b> " . $eInsPes->getMessage());
                        }
                    } catch (Exception $eBegTra) {
                        $adapter->rollBack();
                        $feedback = array(
                            'erro' => 1,
                            'msg' => "<b>Erro na comunicação com o banco de dados:</b> " . $eBegTra->getMessage());
                    }
                } else {

                    $feedback = array(
                        'erro' => 2,
                        'msg' => implode("<br/>", $validacao["erros"]),
                        'corrigir' => $validacao["corrigir"]);
                }
            }

            $this->feedback($feedback);
        }
    }

    private static function _processarApiRegistroSenhaFilaMedicina($agendaId)
    {
        $resultado = null;
        if ($_SERVER['HTTP_HOST'] == 'siss') {
            $ambiente = $_SERVER['HTTP_HOST'];
            $url = "http://$ambiente.hiestgroup.com.br/api/json/serv/registar-evento-fila-atendimento-medicina";
        }else{
            $ambiente = $_SERVER['HTTP_HOST'];
            $url = "http://$ambiente/api/json/serv/registar-evento-fila-atendimento-medicina";
        }
        #$ambiente = (strstr($_SERVER['HTTP_HOST'], 'portalsiss')) ? 'portalsiss' : 'siss';

        /*
         * Edite a variável $ambiente (linha abaixo do comentário) para teste, pois o sistema
         * não foi desenvolvido de forma parametrizável
         * $ambiente = (strstr($_SERVER['HTTP_HOST'], 'endereco_do_portal')) ? 'endereco_do_siss' : 'siss';
         * exemplo:
         * $ambiente = (strstr($_SERVER['HTTP_HOST'], 'desenv13')) ? 'desenv2' : 'siss';
         */
        // ATENÇÃO DESCOMENTE PARA TESTE E EDITE CONFORME SEU AMBIENTE
        // $ambiente = (strstr($_SERVER['HTTP_HOST'], 'desenv13')) ? 'desenv2' : 'siss';
        #$url = "http://$ambiente.hiestgroup.com.br/api/json/serv/registar-evento-fila-atendimento-medicina";
        $data = ['agenda_id' => $agendaId];
        $data_string = json_encode($data);
        //$ch = curl_init('http://desenvsiss.hiestgroup.com.br/api/json/serv/registar-evento-fila-atendimento-medicina');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)]
        );
        $r = curl_exec($ch);
        curl_close($ch);
        if (strlen($r) > 0) {
            $resultado = json_decode($r, true);
        }
        return $resultado;
    }

    private function _salvarOrdemAtendimento($agendaID, $conexao = null)
    {

        // Conexao
        $conexao = is_null($conexao) ? Zend_Db_Table::getDefaultAdapter() : $conexao;
        $ambiente = (strstr($_SERVER['HTTP_HOST'], 'develop')) ? 'developsiss' : 'siss';
        $url = "http://$ambiente.hiestgroup.com.br/ordem-atendimento/salvar";

        // Resgata a agenda
        $sql = "SELECT

                    pe.pessoa_cpf AS ordem_atendimento_cpf,
                    pe.pessoa_identidade AS ordem_atendimento_identidade,
                    pe.pessoa_data_nascimento AS ordem_atendimento_data_nascimento,
                    pe.pessoa_nome AS ordem_atendimento_funcionario,
                    ca.cargo_nome AS ordem_atendimento_funcao,
                    te.tipoexame_nome AS ordem_atendimento_tipo_exame,
                    ag.agenda_data_exame AS ordem_atendimento_data_exame,
                    em.empresa_razao AS ordem_atendimento_empresa,
                    '{$_SERVER['HTTP_HOST']}' AS ordem_atendimento_dominio_agendamento,
                    co.contrato_numero AS ordem_atendimento_contrato,
                    'PTL-$agendaID' AS ordem_atendimento_codigo,
                    GROUP_CONCAT(pr.produto_nome) AS item_ordem_atendimento_exame

                FROM agenda ag

                    JOIN tipoexame te ON te.tipoexame_id = ag.fk_tipoexame_id
                    JOIN pessoa pe ON pe.pessoa_id = ag.fk_pessoa_id
                    JOIN alocacao al ON al.alocacao_id = ag.fk_alocacao_id
                    JOIN cargo ca ON ca.cargo_id = al.fk_cargo_id
                    JOIN empresa em ON em.empresa_id = ag.fk_empresa_id
                    JOIN contrato co ON co.contrato_id = ag.fk_contrato_id

                    LEFT JOIN funcionario fu ON fu.fk_pessoa_id = pe.pessoa_id
                        AND fu.fk_empresa_id = em.empresa_id
                        AND fu.fk_contrato_id = co.contrato_id
                    LEFT JOIN funcao fn ON fn.funcao_id = al.fk_funcao_id
                    LEFT JOIN setor se ON se.setor_id = al.fk_setor_id
                    LEFT JOIN ghe gh ON gh.ghe_id = al.fk_ghe_id
                    -- LEFT JOIN pcmso pc ON pc.fk_contrato_id = co.contrato_id AND pc.pcmso_status = 0
                    LEFT JOIN (
                        SELECT * FROM pcmso pc
                            WHERE pc.pcmso_status = 0
                            AND pc.pcmso_id = (
                                SELECT pc2.pcmso_id FROM pcmso pc2
                                WHERE pc2.fk_empresa_id = pc.fk_empresa_id AND pc2.fk_contrato_id = pc.fk_contrato_id
                                ORDER BY pc2.pcmso_id DESC, pc2.pcmso_data_validade DESC
                                LIMIT 1
                            )
                    ) pc ON pc.fk_contrato_id = co.contrato_id AND pc.fk_empresa_id = em.empresa_id
                    LEFT JOIN item_pcmso ip ON ip.item_pcmso_status = 0 AND ip.fk_pcmso_id = pc.pcmso_id AND ip.fk_cargo_id = al.fk_cargo_id AND ip.fk_funcao_id = al.fk_funcao_id AND ip.fk_ghe_id = gh.ghe_id -- AND ip.fk_setor_id = al.fk_setor_id
                    LEFT JOIN item_pcmso_produto ipp ON ipp.fk_item_pcmso_id = ip.item_pcmso_id AND ipp.fk_tipoexame_id = te.tipoexame_id
                    LEFT JOIN produto pr ON pr.produto_id = ipp.fk_produto_id

                WHERE ag.agenda_id = '$agendaID'";

        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        $fields = $stmt->fetch();

        if (isset($fields) && count($fields) > 0) {
            $comando = "SELECT age.fk_empresa_id, age.fk_contrato_id, age.fk_tipoexame_id,alo.fk_funcao_id, alo.fk_cargo_id, alo.fk_setor_id
                        FROM agenda age, alocacao alo
                        WHERE alo.alocacao_id = age.fk_alocacao_id
                               AND age.agenda_id = ?";
            try {
                $Cnx = Zend_Db_Table::getDefaultAdapter();
                $rstComando = $Cnx->fetchRow($comando, array($agendaID));
                if (count($rstComando) > 0) {
                    $tipoExameId = (int) $rstComando['fk_tipoexame_id'];
                    $setorId = (int) $rstComando['fk_setor_id'];
                    $funcaoId = (int) $rstComando['fk_funcao_id'];
                    $cargoId = (int) $rstComando['fk_cargo_id'];
                    $contratoId = (int) $rstComando['fk_contrato_id'];
                    $empresaId = (int) $rstComando['fk_empresa_id'];
                    /*
                     * Invoca um procedimento de banco de dados (Stored Procidure)
                     * Consulte a documentação do procedimento no diretorio: docs/stored-procedure/doc-sp-obter-exame-pcmso-recente.docx
                     * SpObterColecaoExameParaFuncaoDoPcmsoRecente(IN paramContratoId INT , IN paramEmpresaId INT, IN paramCargoId INT, IN paramFuncaoId INT, paramSetorId INT, IN paramTopoExameId INT)
                     */
                    $comando = "CALL SpObterColecaoExameParaFuncaoDoPcmsoRecente({$contratoId}, {$empresaId}, {$cargoId}, {$funcaoId}, {$setorId}, {$tipoExameId})";
                    $Comando = $Cnx->query($comando);
                    $rstClcRegistro = $Comando->fetchAll();
                    $exames = array();
                    if (is_array($rstClcRegistro) && count($rstClcRegistro) > 0) {
                        foreach ($rstClcRegistro as $item) {
                            $exames[] = $item['produto_nome'];
                        }
                    }
                    $fields['item_ordem_atendimento_exame'] = implode(',', $exames);
                }
            } catch (Exception $ex) {
                throw $ex;
            }
        }

        // Cria a string da requisicao (cp1=vl1&cp2=vl2...)
        $fields_string = '';
        foreach ($fields as $key => $value) {
            if ($key == 'ordem_atendimento_tipo_exame') {
                $value = str_ireplace(array('ç', 'ã'), array('c', 'a'), $value);
            }
            $fields_string .= $key . '=' . urlencode($value) . '&';
        }
        $fields_string = rtrim($fields_string, '&');

        // Abre a conexao
        $ch = curl_init();

        // Configura a conexao passando os campos
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Executa a requisicao
        $result = curl_exec($ch);

        // Fecha a conexao
        curl_close($ch);

        return $result;
    }

}
