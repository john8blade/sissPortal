<?php

class AgendaController extends Controller {

    public function _indexAction() {
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

    public function indexAction() {
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

    public function adicionarAction() {
        $empresaId = $_SESSION['empresa']['empresa_id'];
        $contratoId = $_SESSION['contrato_id'];
        $atributos = array(
            'tipoExame' => array(),
            'alocacao' => array(),
        );
        $tabelas = array('agenda', 'pessoa', 'funcionario', 'alocacao', 'setor', 'cargo', 'funcao');
        $mes = date('m');
        $ano = date('Y');
        try {
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
        $this->view->atributos = $atributos;
        $this->view->mes = $mes;
        $this->view->ano = $ano;
        $this->renderScript('agenda/formulario.phtml');
    }

    public function alterarAction() {
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

    public function salvarAction() {
        $unidadeId = isset($_SESSION['fk_unidade_id']) ? $_SESSION['fk_unidade_id'] : 0;
        $erro = 2;
        $mensagem = null;
        $corrigir = array();
        $codigoJavascript = null;
        if ($this->getRequest()->isPost()) {

            $parametros = $this->getRequest()->getParams();
            $agendaId = (isset($parametros['agenda_id'])) ? (int) $parametros['agenda_id'] : 0;
            $validarCampos = array(
                'fk_horario_global_id' => ['tipo' => 'texto', 'nome' => 'Horário'],
                'agenda_data_exame' => array('tipo' => 'texto', 'nome' => 'Data Agendamento'),
                'fk_tipoexame_id' => array('tipo' => 'texto', 'nome' => 'Tipo de Exame'),
                'fk_alocacao_id' => array('tipo' => 'texto', 'nome' => 'Nome'),
                'pessoa_identidade' => array('tipo' => 'texto', 'nome' => 'Identidade (<i>*ajuste no cadastro do funcionário)</i>'),
            );
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
                        if ($item['fk_ghe_id'] == $RstAlocacao->fk_ghe_id && $item['fk_funcao_id'] == $RstAlocacao->fk_funcao_id && $item['fk_cargo_id'] == $RstAlocacao->fk_cargo_id && $item['fk_setor_id'] == $RstAlocacao->fk_setor_id) {
                            $alocacaoContidaPcmso = true;
                            break;
                        }
                    }
                }
            } catch (Exception $Exc) {
                $validacao['erros'][] = 'Erro ao validar a existência';
            }

            if ($alocacaoContidaPcmso == false) {
                $validacao['erros'][] = 'Não foi encontrado o cargo, função, setor e GHE no PMCSO atual da empresa <a href="/funcionario/alterar/id/' . $RstAlocacao->fk_funcionario_id . '"> clique aqui para atualizar</a> e depois tente fazer o agendamento novamente';
            }

            $agenda = new Application_Model_Agenda();
            $VerificarAgendaPosterior = $agenda->verificarAgendamentoPosterior($parametros['fk_pessoa_id']);
            if (!empty($VerificarAgendaPosterior)) {
                $validacao['erros'][] = "<i class='icon-remove'></i>&nbsp;O agendamento não pode ser feito pois esse funcionário já possui uma agenda ativa no dia ".Util::dataBR($VerificarAgendaPosterior['agenda_data_exame'])." na unidade ".$VerificarAgendaPosterior['unidade_sigla'].".";
            }

            if (count($validacao['erros']) == 0) {
                // verifica se existe atendimento na data solicitada
                $teraAtentimento = false;
                try {

                    $agenda = new Application_Model_Agenda();
                    $agenda_data_exame = Util::dataBD($parametros['agenda_data_exame']);

                    $dataParametro = $parametros['agenda_data_exame'];
                    // Verifica se tem atendimento
                    $diasAtendimento = new Application_Model_DiasSemAtendimento();
                    $teraAtentimento = $diasAtendimento->temAtendimentoNaData($parametros['agenda_data_exame'], $unidadeId);
                    //var_dump($teraAtentimento);
                    $itemMensagem = array();
                    if ($teraAtentimento == false) {
                        $itemMensagem[] = "<i class='icon-remove'></i>&nbsp;Não haverá atendimento na data selecionada!";
                    }

//                    $diaMaisDois = date('d/m/Y', strtotime("+2 days"));
//                    var_dump($diaMaisDois);
//                    var_dump($parametros['agenda_data_exame']);
//                    die;
//                    if (strtotime($parametros['agenda_data_exame']) < strtotime($diaMaisDois)) {
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

                    $diasAtendimento = new Application_Model_DiasSemAtendimento();
                    $jaCadastrados = $diasAtendimento->vagasJaCadastradas($dataParametro);
                    $teraAtentimentoVaga = $diasAtendimento->controleVagas($unidadeId, $dataParametro);

                    if ($teraAtentimentoVaga == false) {
                        $teraAtentimentoVagaPadrao = $diasAtendimento->controleVagasPadrao($unidadeId);
                        $resultadoVagasPadrao = ((int) $teraAtentimentoVagaPadrao['controleagendamento_quantidade'] - (int) $jaCadastrados["COUNT(*)"]);
                        if ($resultadoVagasPadrao <= 0) {
                            $itemMensagem[] = "<i class='icon-remove'></i>&nbsp;Não há mais vagas na data selecionada!";
                        }
                    } else {
                        $resultadoControleVagas = ((int) $teraAtentimentoVaga['controleagendamento_quantidade'] - (int) $jaCadastrados["COUNT(*)"]);
                        if ($resultadoControleVagas <= 0) {
                            $itemMensagem[] = "<i class='icon-remove'></i>&nbsp;Não há mais vagas na data selecionada!";
                        }
                    }

                    if ($agendaId == 0) {
                        // Verifica se o funcionário já foi agendado
                        $a = new Application_Model_Agenda();
                        $filtro = array(
                            'fk_alocacao_id = ?' => $parametros['fk_alocacao_id'],
                            'agenda_data_exame = ?' => Util::dataBD($parametros['agenda_data_exame']),
                            'agenda_status = ?' => 0
                        );
                        $resutadoConsulta = $a->fetchAll($filtro)->toArray();
                        if (is_array($resutadoConsulta) && count($resutadoConsulta) > 0) {
                            $itemMensagem[] = "<i class='icon-remove'></i>&nbsp;Já existe um agendamento nesta data para este funcionário!";
                        }
                    }

                    if (count($itemMensagem) > 0) {
                        $mensagem = implode('<br/>', $itemMensagem);
                        $teraAtentimento = false;
                    }
                } catch (Exception $ex1) {
                    $erro = 2;
                    $mensagem = "Erro ao executar comando no banco de dados. Detalhes: {$ex1->getMessage()}";
                }

                if ($teraAtentimento) {
                    $empresaId = $_SESSION['empresa']['empresa_id'];
                    $contratoId = $_SESSION['contrato_id'];
                    $tabelaAgenda = array(
                        'agenda_criada_em' => date('Y-m-d H:i:s'),
                        'agenda_inserida_via' => 'ONLINE',
                        'fk_empresa_id' => $empresaId,
                        'fk_contrato_id' => $contratoId,
                        'fk_tipoexame_id' => $parametros['fk_tipoexame_id'],
                        'fk_pessoa_id' => $parametros['fk_pessoa_id'],
                        'fk_alocacao_id' => $parametros['fk_alocacao_id'],
                        'agenda_data_exame' => Util::dataBD($parametros['agenda_data_exame']),
                        'agenda_observacao' => $parametros['agenda_observacao'],
                        'fk_unidade_id' => $unidadeId,
                        'fk_horario_global_id' => $parametros['fk_horario_global_id']
                    );
                    //var_dump($tabelaAgenda);die;
                    try {
                        $conexaoDireta = Zend_Db_Table::getDefaultAdapter();
                        //$conexaoDireta->beginTransaction();
                        try {

                            $agenda = new Application_Model_Agenda();

                            if ($agendaId == 0) {
                                /*
                                 * Resgata qual a ultima OS Aprovada que tenha produto da categoria exame
                                 */
                                /*
                                  $Comando = $conexaoDireta->select();
                                  $Comando->from(array('o' => 'os'), '*')
                                  ->join(array('co' => 'cobrancaos'), 'co.fk_os_id = o.os_id', array())
                                  ->join(array('cp' => 'categoriadoproduto'), 'cp.categoriadoproduto_id = co.fk_categoriadoproduto_id', array())
                                  ->where('o.os_aprovada = ?', 1)
                                  ->where('o.fk_contrato_id = ?', $contratoId)
                                  ->where('cp.categoriadoproduto_codigo_fixo = ?', '0002')
                                  ->where('co.cobrancaos_status = ?', 0)
                                  ->order(array('o.os_id DESC'));
                                  $rst = $Comando->query()->fetch();

                                  if (is_array($rst) && count($rst) > 0) {
                                  $tabelaAgenda['fk_os_id'] = $rst['os_id'];
                                  }
                                 */
                                // Comando de inserção
                                $tabelaAgenda['agenda_criada_em'] = date('Y-m-d H:i:s');
                                $agendaId = $agenda->insert($tabelaAgenda);
                                //$resposta = $this->_salvarOrdemAtendimento($agendaId);
                                $rst = self::_processarApiRegistroSenhaFilaMedicina($agendaId);

                                #############################################
                                # Envio de email
                                if ($agendaId > 0) {
                                    try {
                                        $host = $_SERVER['HTTP_HOST'];            
                                        if ($host == 'portal.htmed.com.br') {
                                            $destinatarios = ['agendamento1.ubh@htmed.com.br' => "Portal do Cliente"];
                                        } else {
                                            $destinatarios = ['thiago@httec.com.br' => "Desenvolvimento HTTEC",'jeferson@httec.com.br' => "Gerente"];
                                        }

                                        $assunto = "AVISO DE AGENDAMENTO ".$_SESSION['empresa']['empresa_razao'].' - '.$_SESSION['empresa']['empresa_fantasia'];
                                        
                                        $mensagem = Util::template('../application/views/scripts/template/email_agendamento.phtml', [
                                            '{DATA}' => date("d/m/Y H:i"),
                                            '{DATAAGENDAMENTO}' => $parametros['agenda_data_exame'],
                                            '{RAZÃOSOCIAL}' => $_SESSION['empresa']['empresa_razao'],
                                            '{OBRA}' => $_SESSION['empresa']['empresa_fantasia']]);

                                        require_once 'mail/mail.php';

                                        $enviado = enviarEmail($assunto , $mensagem, ['naoresponda@httec.com.br', 'Portal HTMED'], $destinatarios);
                                              
                                        if (strlen($enviado) == 0):                   
                                            #die('E-mails ERROR! <script>setTimeout(function(){window.close();},1500);</script>');
                                        else:
                                            #die('E-mails Sucesso! <script>setTimeout(function(){window.close();},1500);</script>');
                                        endif;
                                    } catch (Exception $e) {
                                            die($e->getMessage());
                                    }
                                }
                                
                                #############################################
                                if (is_array($rst)) {
                                    if (isset($rst['Erro']) && $rst['Erro'] == true) {
                                        $agenda->delete(array('agenda_id = ?' => $agendaId));
                                        throw new Exception($rst['Mensagem']);
                                    }
                                }
                            } else {
                                $antigo = $agendaId;
                                // Comando de alteração
                                $tabelaAgenda['agenda_criada_em'] = date('Y-m-d H:i:s');
                                $onde = array('agenda_id = ?' => $agendaId);
                                $agendaId = $agenda->update($tabelaAgenda, $onde);
                                $agendaId = $antigo;
                                if (strtotime(Util::dataBD($parametros['data_antiga'])) == strtotime(Util::dataBD($parametros['agenda_data_exame']))) {

                                } else {
                                    $resposta = $this->_salvarOrdemAtendimento($agendaId);
                                    $json = json_decode($resposta, true);

                                    if (is_array($json) && is_array($json['mensagens'])) {
                                        if ((int) $json['salvou'] == 0) {
                                            $agenda->delete(array('agenda_id = ?' => $agendaId));
                                            throw new Exception("Erro ao salvar ordem de atendimento: " . implode(', ', $json['mensagens']));
                                        }
                                    }
                                }
                            }

                            $Comando = $conexaoDireta->select();
                            $Comando->from(array('o' => 'os'), '*')
                                    ->join(array('co' => 'cobrancaos'), 'co.fk_os_id = o.os_id', array())
                                    ->join(array('cp' => 'categoriadoproduto'), 'cp.categoriadoproduto_id = co.fk_categoriadoproduto_id', array())
                                    ->where('o.os_aprovada = ?', 1)
                                    ->where('o.fk_contrato_id = ?', $contratoId)
                                    ->where('cp.categoriadoproduto_codigo_fixo = ?', '0002')
                                    ->where('co.cobrancaos_status = ?', 0)
                                    ->order(array('o.os_id DESC'));

                            $rst = $Comando->query()->fetch();
                            // Se não achar uma OS lança uma exceção
                            if (count($rst['os_id']) == 0) {
                                $conexaoDireta->update('agenda', array('agenda_status' => 2), array('agenda_id = ?' => $agendaId));
                                $conexaoDireta->update('produto_agenda', array('produto_agenda_status' => 2), array('fk_agenda_id = ?' => $agendaId));
                                throw new Exception('Não foi possível identificar uma Ordem de serviço aprovada para execução deste serviço neste contrato. Por favor entre em contato com setor de atendimento do prestador de serviço!');
                            }
                            $conexaoDireta->update('agenda', array('fk_os_id' => $rst['os_id']), array('agenda_id = ?' => $agendaId));

                            //$conexaoDireta->commit();

                            $erro = 0;
                            $mensagem = 'Agendamento salvo com sucesso! <a href="/agenda/adicionar/">Adicionar</a>';
                            //$mensagem = '<i class="icon-ok"></i>&nbsp;Agendamento salvo com sucesso!&nbsp;<a href="/agenda/adicionar/"><i class="icon-plus-sign"></i>&nbsp;Novo</a>&nbsp;<a href="/agenda/alterar/id/' . $agendaId . '/"><i class="icon-edit"></i>&nbsp;Alterar</a>';
                            $codigoJavascript = 'renderizarModalInformativo()';
                        } catch (Exception $ex2) {
                            //$conexaoDireta->rollBack();
                            $erro = 1;
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
        $feedback = array("erro" => $erro, "msg" => $mensagem, 'corrigir' => $corrigir, 'js' => $codigoJavascript);
        $this->feedback($feedback);
    }

    public function salvarAction2() {

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

                            if ($pessoa_id > 0)
                                $model_pessoa->update($dados_pessoa, array('pessoa_id = ?' => $pessoa_id));
                            else
                                $pessoa_id = $model_pessoa->insert($dados_pessoa);

                            $dados_funcionario["fk_pessoa_id"] = $pessoa_id;

                            try {

                                $m = new Application_Model_Setor();
                                if ($setorid > 0) {
                                    $i = $setorid;
                                    $m->update(array('setor_nome' => $setor), array('setor_id = ?' => $i));
                                } else {
                                    $e = $m->fetchRow(array('setor_nome = ?' => $setor));
                                    if (is_null($e) || !$e)
                                        $i = $m->insert(array('setor_nome' => $setor));
                                    else
                                        $i = $e['setor_id'];
                                }

                                $dados_alocacao['fk_setor_id'] = $i;

                                if ($funcionario_id > 0)
                                    $model_funcionario->update($dados_funcionario, array('funcionario_id = ?' => $funcionario_id));
                                else
                                    $funcionario_id = $model_funcionario->insert($dados_funcionario);

                                $dados_alocacao["fk_funcionario_id"] = $funcionario_id;

                                if ($alocacao_id > 0)
                                    $model_alocacao->update($dados_alocacao, array('alocacao_id = ?' => $alocacao_id));
                                else
                                    $alocacao_id = $model_alocacao->insert($dados_alocacao);

                                $feedback = array(
                                    'erro' => 0,
                                    'msg' => '<i class="icon-ok"></i>&nbsp;Funcionário salvo!&nbsp;<a href="/funcionario/adicionar/"><i class="icon-plus-sign"></i>&nbsp;Novo</a>&nbsp;<a href="/funcionario/alterar/id/' . $funcionario_id . '/"><i class="icon-edit"></i>&nbsp;Alterar</a>'
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

    private static function _processarApiRegistroSenhaFilaMedicina($agendaId) {
        $resultado = null;
        #$ambiente = (strstr($_SERVER['HTTP_HOST'], 'desenv')) ? 'desenvsiss' : 'siss';
        $ambiente = (strstr($_SERVER['HTTP_HOST'], 'develop')) ? 'developportal' : 'portal';
        #$url = "http://$ambiente.hiestgroup.com.br/api/json/serv/registar-evento-fila-atendimento-medicina";
        $url = "http://$ambiente.htmed.com.br/api/json/serv/registar-evento-fila-atendimento-medicina";
        $data = array('agenda_id' => $agendaId);
        $data_string = json_encode($data);
        //$ch = curl_init('http://desenvsiss.hiestgroup.com.br/api/json/serv/registar-evento-fila-atendimento-medicina');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)));
        $r = curl_exec($ch);
        curl_close($ch);
        if (strlen($r) > 0) {
            $resultado = json_decode($r, true);
        }
        return $resultado;
    }

    private function _salvarOrdemAtendimento($agendaID, $conexao = null) {

        // Conexao
        $conexao = is_null($conexao) ? Zend_Db_Table::getDefaultAdapter() : $conexao;
        #$ambiente = (strstr($_SERVER['HTTP_HOST'], 'desenv')) ? 'desenvsiss' : 'siss';
        $ambiente = (strstr($_SERVER['HTTP_HOST'], 'develop')) ? 'developsiss' : 'siss';
        #$url = "http://$ambiente.hiestgroup.com.br/ordem-atendimento/salvar";
        $url = "http://$ambiente.htmed.com.br/ordem-atendimento/salvar";

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
